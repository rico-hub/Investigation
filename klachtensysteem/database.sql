-- =====================================================================
-- KLACHTENSYSTEEM - database.sql
-- Importeren via phpMyAdmin > Importeren (of SQL tab > plakken)
-- =====================================================================

DROP DATABASE IF EXISTS klachtensysteem;
CREATE DATABASE klachtensysteem CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE klachtensysteem;

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
    user_id      INT UNSIGNED NOT NULL,                 -- wie heeft de klacht ingediend
    category_id  INT UNSIGNED NOT NULL,
    assigned_to  INT UNSIGNED NULL,                     -- welke admin behandelt hem (mag leeg)
    title        VARCHAR(150) NOT NULL,
    description  TEXT NOT NULL,
    priority     ENUM('laag', 'normaal', 'hoog') NOT NULL DEFAULT 'normaal',
    status       ENUM('open', 'in_behandeling', 'gesloten') NOT NULL DEFAULT 'open',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME NULL DEFAULT NULL,            -- soft delete
    PRIMARY KEY (id),
    CONSTRAINT fk_complaints_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_complaints_assigned
        FOREIGN KEY (assigned_to) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,           -- admin weg? klacht blijft, niemand toegewezen
    CONSTRAINT chk_complaints_title
        CHECK (CHAR_LENGTH(title) >= 3)
) ENGINE=InnoDB;

-- Reacties op een klacht. ON DELETE CASCADE: klacht HARD verwijderd = reacties ook weg.
CREATE TABLE reactions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    complaint_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    message       TEXT NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_reactions_complaint
        FOREIGN KEY (complaint_id) REFERENCES complaints (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_reactions_user
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
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- DUMMY DATA - wachtwoord van alle accounts = password
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',    'admin@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Admin Ahmed',   'admin2@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(3, 'Bram Bakker',   'bram@test.nl',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(4, 'Cleo de Vries', 'cleo@test.nl',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (id, name) VALUES
(1, 'Product'),
(2, 'Service'),
(3, 'Bezorging'),
(4, 'Website'),
(5, 'Overig');

INSERT INTO complaints (id, user_id, category_id, assigned_to, title, description, priority, status, created_at) VALUES
(1, 3, 3, NULL, 'Pakket niet ontvangen',     'Mijn pakket zou dinsdag komen maar is nog steeds niet bezorgd.', 'hoog',    'open',           NOW() - INTERVAL 2 DAY),
(2, 3, 1, 1,    'Product kapot',             'De mok was gebroken bij aankomst.',                              'normaal', 'in_behandeling', NOW() - INTERVAL 5 DAY),
(3, 4, 4, 2,    'Inloggen lukt niet',        'Ik krijg steeds een foutmelding bij het inloggen.',              'hoog',    'in_behandeling', NOW() - INTERVAL 1 DAY),
(4, 4, 2, 1,    'Onvriendelijke medewerker', 'Aan de telefoon werd ik niet netjes geholpen.',                 'laag',    'gesloten',       NOW() - INTERVAL 20 DAY),
(5, 3, 5, NULL, 'Factuur klopt niet',        'Er staat een product op dat ik niet besteld heb.',               'normaal', 'open',           NOW() - INTERVAL 3 HOUR),
(6, 4, 3, NULL, 'Verkeerde maat geleverd',   'Ik had M besteld maar L gekregen.',                              'laag',    'open',           NOW() - INTERVAL 7 DAY);

INSERT INTO reactions (complaint_id, user_id, message, created_at) VALUES
(2, 1, 'Vervelend! We sturen een nieuwe mok op.',           NOW() - INTERVAL 4 DAY),
(2, 3, 'Dank je, ik wacht het af.',                          NOW() - INTERVAL 4 DAY),
(4, 1, 'Excuses, we hebben dit met het team besproken.',   NOW() - INTERVAL 18 DAY);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(3, 'complaint_create', 'Klacht #1', '127.0.0.1'),
(1, 'complaint_status', 'Klacht #2 -> in_behandeling', '127.0.0.1');

-- Test CASCADE: verwijder klacht 2 -> reacties van klacht 2 zijn ook weg
--   DELETE FROM complaints WHERE id = 2;  SELECT * FROM reactions;
-- Test SET NULL: verwijder admin 2 -> klacht 3 heeft assigned_to = NULL
--   (lukt alleen als admin 2 geen klachten/reacties als user_id heeft)
