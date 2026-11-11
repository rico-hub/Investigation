-- =====================================================================
-- SPORTSYSTEEM - database.sql
-- Leden schrijven zich in voor sportlessen.
-- =====================================================================

DROP DATABASE IF EXISTS sportsysteem;
CREATE DATABASE sportsysteem CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sportsysteem;

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

CREATE TABLE sports (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_sports_name (name)
) ENGINE=InnoDB;

CREATE TABLE lessons (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sport_id          INT UNSIGNED NOT NULL,
    title             VARCHAR(150) NOT NULL,
    trainer           VARCHAR(100) NOT NULL,
    lesson_date       DATE NOT NULL,
    start_time        TIME NOT NULL,
    max_participants  TINYINT UNSIGNED NOT NULL DEFAULT 10,
    status            ENUM('gepland', 'geannuleerd') NOT NULL DEFAULT 'gepland',
    deleted_at        DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_lessons_sport
        FOREIGN KEY (sport_id) REFERENCES sports (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_lessons_max
        CHECK (max_participants BETWEEN 1 AND 50)
) ENGINE=InnoDB;

-- Koppeltabel (veel-op-veel): 1 user -> veel lessen, 1 les -> veel users
CREATE TABLE registrations (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    lesson_id   INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registration (user_id, lesson_id),     -- je kunt je maar 1x inschrijven voor dezelfde les
    CONSTRAINT fk_registrations_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE,             -- lid weg = inschrijvingen weg
    CONSTRAINT fk_registrations_lesson
        FOREIGN KEY (lesson_id) REFERENCES lessons (id)
        ON UPDATE CASCADE ON DELETE RESTRICT             -- les met inschrijvingen kan niet hard weg
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
-- Datums zijn relatief (CURDATE() + INTERVAL) zodat er altijd toekomstige lessen zijn
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',    'admin@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Bram Bakker',   'bram@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 'Cleo de Vries', 'cleo@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(4, 'Daan Smit',     'daan@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO sports (id, name) VALUES
(1, 'Voetbal'),
(2, 'Tennis'),
(3, 'Yoga'),
(4, 'Zwemmen'),
(5, 'Fitness');

INSERT INTO lessons (id, sport_id, title, trainer, lesson_date, start_time, max_participants, status) VALUES
(1, 3, 'Yoga voor beginners',  'Sara',  CURDATE() + INTERVAL 1 DAY,  '09:00', 10, 'gepland'),
(2, 3, 'Power yoga',           'Sara',  CURDATE() + INTERVAL 3 DAY,  '19:00', 2,  'gepland'),   -- wordt VOL
(3, 1, 'Voetbaltraining',      'Mo',    CURDATE() + INTERVAL 2 DAY,  '18:30', 20, 'gepland'),
(4, 2, 'Tennisles gevorderd',  'Lisa',  CURDATE() + INTERVAL 5 DAY,  '10:00', 4,  'gepland'),
(5, 4, 'Banenzwemmen',         'Kees',  CURDATE() + INTERVAL 4 DAY,  '07:00', 15, 'geannuleerd'),
(6, 5, 'Bootcamp',             'Mo',    CURDATE() + INTERVAL 6 DAY,  '20:00', 12, 'gepland'),
(7, 5, 'Spinning',             'Lisa',  CURDATE() - INTERVAL 3 DAY,  '19:00', 12, 'gepland'),   -- afgelopen
(8, 2, 'Tennis voor kids',     'Lisa',  CURDATE() + INTERVAL 7 DAY,  '14:00', 6,  'gepland');

INSERT INTO registrations (user_id, lesson_id) VALUES
(2, 1), (3, 1),
(2, 2), (3, 2),          -- les 2: max 2 -> vol
(4, 3),
(2, 7), (4, 7);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(2, 'checkout', 'Ingeschreven voor les #1, #2', '127.0.0.1');

-- Test UNIQUE (moet FOUT geven, #1062 Duplicate entry):
--   INSERT INTO registrations (user_id, lesson_id) VALUES (2, 1);
-- Aantal inschrijvingen per les (LEFT JOIN + COUNT + GROUP BY):
--   SELECT l.id, l.title, l.max_participants, COUNT(r.id) AS ingeschreven
--   FROM lessons l LEFT JOIN registrations r ON r.lesson_id = l.id GROUP BY l.id;
