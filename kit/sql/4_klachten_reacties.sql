-- =====================================================================
-- SCENARIO 4: KLACHTEN MET REACTIES  (iets hangt ONDER het ding)
--   1. Het ding?            klacht          -> complaints (user_id = wie diende hem in)
--   2. Kies uit een lijst?  categorie       -> categories
--   3. User doet iets?      reageren        -> comments (complaint_id + user_id)
--   Extra: "behandeld door" een admin (mag leeg)  -> handled_by, ON DELETE SET NULL
--
--   categories  <--  complaints  <--  comments  -->  users
--                         |                            ^
--                         +---- user_id, handled_by ---+
--
-- ON DELETE CASCADE: klacht hard verwijderd = reacties automatisch ook weg
-- =====================================================================

DROP DATABASE IF EXISTS klachten;
CREATE DATABASE klachten CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE klachten;

CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE complaints (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,              -- wie diende hem in (verplicht)
    category_id  INT UNSIGNED NOT NULL,
    handled_by   INT UNSIGNED NULL DEFAULT NULL,     -- welke admin (MAG leeg)
    title        VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    status       ENUM('open', 'in_behandeling', 'gesloten') NOT NULL DEFAULT 'open',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at   DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_complaints_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_handled_by              -- 2e foreign key naar users: ANDERE naam
        FOREIGN KEY (handled_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL         -- admin weg? dan wordt het NULL
) ENGINE=InnoDB;

CREATE TABLE comments (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    message       TEXT NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_comments_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (id)
        ON UPDATE CASCADE ON DELETE CASCADE,         -- klacht weg = reacties weg
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE action_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(50) NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',  'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Paul Bakker', 'paul@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (id, name) VALUES
(1, 'Levering'), (2, 'Product'), (3, 'Service');

INSERT INTO complaints (id, user_id, category_id, handled_by, title, description, status) VALUES
(1, 2, 1, 1,    'Pakket te laat',  'Al 2 weken onderweg.', 'in_behandeling'),
(2, 2, 2, NULL, 'Kapot geleverd',  'Scherm gebarsten.',    'open');

INSERT INTO comments (complaint_id, user_id, message) VALUES
(1, 1, 'We zoeken het uit.'),
(1, 2, 'Dank je!');

-- ---- CONTROLE --------------------------------------------------------
-- Zelfde tabel (users) 2x joinen: andere bijnaam (u en a). LEFT JOIN want handled_by mag NULL.
-- SELECT k.id, k.title, u.name AS ingediend_door, a.name AS behandeld_door, COUNT(c.id) AS reacties
-- FROM complaints k
-- INNER JOIN users u   ON u.id = k.user_id
-- LEFT JOIN users a    ON a.id = k.handled_by
-- LEFT JOIN comments c ON c.complaint_id = k.id
-- GROUP BY k.id, k.title, u.name, a.name;
-- CASCADE testen: DELETE FROM complaints WHERE id = 1;  daarna  SELECT * FROM comments;  (leeg)
