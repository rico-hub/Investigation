-- =====================================================================
-- SCENARIO 2: SPORTSCHOOL  (vraag 2 = NEE, geen lijst om uit te kiezen)
--   1. Het ding?            les             -> lessons
--   2. Kies uit een lijst?  nee             -> geen parent-tabel
--   3. User doet iets?      inschrijven     -> registrations (user_id + lesson_id)
--
--   lessons  <--  registrations  -->  users
--
-- In de kit: items = lessons, orders = registrations, categories WEG
--   (haal in de kit weg: INNER JOIN categories, het categorie-filter,
--    de check category_exists en de dropdown-rij in het formulier)
-- =====================================================================

DROP DATABASE IF EXISTS sportschool;
CREATE DATABASE sportschool CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sportschool;

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

-- Het ding heeft GEEN foreign key: het kiest nergens uit
CREATE TABLE lessons (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(100) NOT NULL,
    lesson_date       DATE NOT NULL,
    start_time        TIME NOT NULL,
    max_participants  TINYINT UNSIGNED NOT NULL DEFAULT 10,
    status            ENUM('gepland', 'geannuleerd') NOT NULL DEFAULT 'gepland',
    deleted_at        DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT chk_lessons_max CHECK (max_participants BETWEEN 1 AND 50)
) ENGINE=InnoDB;

-- Koppeltabel: wie schrijft zich in voor welke les
CREATE TABLE registrations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    lesson_id   INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration (user_id, lesson_id),  -- niet 2x voor dezelfde les
    CONSTRAINT fk_registrations_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registrations_lesson
        FOREIGN KEY (lesson_id) REFERENCES lessons (id)
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

INSERT INTO lessons (id, name, lesson_date, start_time, max_participants) VALUES
(1, 'Yoga',     CURDATE() + INTERVAL 1 DAY, '09:00', 12),
(2, 'Spinning', CURDATE() + INTERVAL 2 DAY, '19:30', 2),
(3, 'Boksen',   CURDATE() + INTERVAL 3 DAY, '20:00', 8);

INSERT INTO registrations (user_id, lesson_id) VALUES
(1, 2),
(2, 2),
(2, 1);

-- ---- CONTROLE --------------------------------------------------------
-- Aantal inschrijvingen per les, ook 0 (LEFT JOIN + COUNT(kolom)):
-- SELECT l.name, l.max_participants, COUNT(r.id) AS ingeschreven
-- FROM lessons l
-- LEFT JOIN registrations r ON r.lesson_id = l.id
-- GROUP BY l.id, l.name, l.max_participants;
-- Volle lessen (HAVING = filter na GROUP BY):
--   ... GROUP BY l.id, l.name, l.max_participants HAVING COUNT(r.id) >= l.max_participants;
