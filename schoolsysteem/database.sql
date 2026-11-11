-- =====================================================================
-- SCHOOLSYSTEEM (Cijfers en Rapport) - database.sql
-- admin = docent, user = leerling
-- =====================================================================

DROP DATABASE IF EXISTS schoolsysteem;
CREATE DATABASE schoolsysteem CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE schoolsysteem;

-- Eerst classes: users verwijst ernaar (parent voor child)
CREATE TABLE classes (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name    VARCHAR(20) NOT NULL,                       -- bv. 'H4A'
    mentor  VARCHAR(100) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_name (name)
) ENGINE=InnoDB;

CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id    INT UNSIGNED NULL,                      -- docenten hebben geen klas -> NULL
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    CONSTRAINT fk_users_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON UPDATE CASCADE ON DELETE SET NULL             -- klas weg? leerling blijft, zonder klas
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code  VARCHAR(10) NOT NULL,                         -- bv. 'NE'
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_code (code)
) ENGINE=InnoDB;

CREATE TABLE grades (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id  INT UNSIGNED NOT NULL,
    subject_id  INT UNSIGNED NOT NULL,
    teacher_id  INT UNSIGNED NULL,                      -- wie heeft het cijfer ingevoerd
    description VARCHAR(100) NOT NULL,                  -- bv. 'Toets H3'
    grade       DECIMAL(3,1) NOT NULL,                  -- 1 cijfer achter de komma: 7.5
    weight      TINYINT UNSIGNED NOT NULL DEFAULT 1,    -- weging 1 t/m 5
    test_date   DATE NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at  DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_grades_student
        FOREIGN KEY (student_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,            -- leerling met cijfers kan niet weg
    CONSTRAINT fk_grades_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_grades_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_grades_grade
        CHECK (grade BETWEEN 1.0 AND 10.0),
    CONSTRAINT chk_grades_weight
        CHECK (weight BETWEEN 1 AND 5)
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
INSERT INTO classes (id, name, mentor) VALUES
(1, 'H4A', 'Dhr. Jansen'),
(2, 'H4B', 'Mevr. de Boer'),
(3, 'V5A', 'Dhr. Visser');

INSERT INTO users (id, class_id, name, email, password, role) VALUES
(1, NULL, 'Docent Anja',   'admin@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 1,    'Bram Bakker',   'bram@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 1,    'Cleo de Vries', 'cleo@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(4, 2,    'Daan Smit',     'daan@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(5, 2,    'Eva Mulder',    'eva@test.nl',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(6, 3,    'Finn Bos',      'finn@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO subjects (id, code, name) VALUES
(1, 'NE', 'Nederlands'),
(2, 'EN', 'Engels'),
(3, 'WI', 'Wiskunde'),
(4, 'BI', 'Biologie'),
(5, 'IN', 'Informatica');

INSERT INTO grades (student_id, subject_id, teacher_id, description, grade, weight, test_date) VALUES
(2, 1, 1, 'Leesverslag',     7.5, 1, CURDATE() - INTERVAL 40 DAY),
(2, 1, 1, 'Toets H1',        6.0, 2, CURDATE() - INTERVAL 20 DAY),
(2, 3, 1, 'Toets H2',        4.8, 2, CURDATE() - INTERVAL 15 DAY),
(2, 5, 1, 'Project website', 8.9, 3, CURDATE() - INTERVAL 5 DAY),
(3, 1, 1, 'Leesverslag',     8.2, 1, CURDATE() - INTERVAL 40 DAY),
(3, 3, 1, 'Toets H2',        9.1, 2, CURDATE() - INTERVAL 15 DAY),
(3, 2, 1, 'Listening',       5.2, 1, CURDATE() - INTERVAL 10 DAY),
(4, 2, 1, 'Listening',       6.7, 1, CURDATE() - INTERVAL 10 DAY),
(4, 4, 1, 'Practicum',       5.4, 1, CURDATE() - INTERVAL 8 DAY),
(5, 4, 1, 'Practicum',       7.0, 1, CURDATE() - INTERVAL 8 DAY),
(5, 3, 1, 'Toets H2',        3.9, 2, CURDATE() - INTERVAL 15 DAY),
(6, 5, 1, 'Project website', 9.5, 3, CURDATE() - INTERVAL 5 DAY);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1, 'grade_create', 'Dummy data', '127.0.0.1');

-- Rapport-query (gewogen gemiddelde per vak voor leerling 2):
--   SELECT s.name, COUNT(*) AS aantal, ROUND(SUM(g.grade * g.weight) / SUM(g.weight), 1) AS gemiddelde
--   FROM grades g INNER JOIN subjects s ON s.id = g.subject_id
--   WHERE g.student_id = 2 AND g.deleted_at IS NULL
--   GROUP BY s.id, s.name;
-- Test CHECK (moet FOUT geven): INSERT INTO grades (student_id, subject_id, description, grade, test_date) VALUES (2, 1, 'x', 11, CURDATE());
