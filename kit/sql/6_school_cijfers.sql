-- =====================================================================
-- SCENARIO 6: SCHOOL MET CIJFERS  (de koppeltabel IS het ding)
--   1. Het ding?            cijfer          -> grades
--   2. Kies uit een lijst?  leerling EN vak -> students + subjects (grades krijgt student_id EN subject_id)
--      en een leerling kiest zelf weer een klas -> classes (students krijgt class_id)
--   3. User doet iets?      de docent (admin) voert cijfers in -> grades.teacher_id
--
--   classes  <--  students  <--+
--                              +--  grades  -->  users (docent)
--   subjects  <----------------+
--
-- Een rij kan dus naar 3 tabellen wijzen. Dat is gewoon 3 foreign keys.
-- =====================================================================

DROP DATABASE IF EXISTS school;
CREATE DATABASE school CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE school;

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

CREATE TABLE classes (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_name (name)
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_name (name)
) ENGINE=InnoDB;

CREATE TABLE students (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id        INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    student_number  VARCHAR(20) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_number (student_number),
    CONSTRAINT fk_students_class
        FOREIGN KEY (class_id) REFERENCES classes (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE grades (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id  INT UNSIGNED NOT NULL,
    subject_id  INT UNSIGNED NOT NULL,
    teacher_id  INT UNSIGNED NULL,                   -- docent weg? cijfer blijft (SET NULL)
    grade       DECIMAL(3,1) NOT NULL,               -- 7.5
    weight      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    test_date   DATE NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_grades_student
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_grades_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_grades_teacher
        FOREIGN KEY (teacher_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_grades_grade  CHECK (grade BETWEEN 1.0 AND 10.0),
    CONSTRAINT chk_grades_weight CHECK (weight BETWEEN 1 AND 5)
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
(1, 'Docent Anja', 'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Paul Bakker', 'paul@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO classes (id, name) VALUES (1, '1A'), (2, '1B');
INSERT INTO subjects (id, name) VALUES (1, 'Nederlands'), (2, 'Wiskunde'), (3, 'Engels');

INSERT INTO students (id, class_id, name, student_number) VALUES
(1, 1, 'Sara Smit',   'S1001'),
(2, 1, 'Tom de Boer', 'S1002'),
(3, 2, 'Lina Jansen', 'S1003');

INSERT INTO grades (student_id, subject_id, teacher_id, grade, weight, test_date) VALUES
(1, 1, 1, 7.5, 1, CURDATE() - INTERVAL 10 DAY),
(1, 2, 1, 5.2, 2, CURDATE() - INTERVAL 5 DAY),
(2, 2, 1, 8.0, 2, CURDATE() - INTERVAL 5 DAY),
(3, 3, 1, 6.4, 1, CURDATE() - INTERVAL 2 DAY);

-- ---- CONTROLE --------------------------------------------------------
-- Alle cijfers met klas, leerling en vak (3 JOINs):
-- SELECT k.name AS klas, s.name AS leerling, v.name AS vak, g.grade
-- FROM grades g
-- INNER JOIN students s ON s.id = g.student_id
-- INNER JOIN classes k  ON k.id = s.class_id
-- INNER JOIN subjects v ON v.id = g.subject_id;
-- Gewogen gemiddelde per leerling per vak:
-- SELECT s.name, v.name, ROUND(SUM(g.grade * g.weight) / SUM(g.weight), 1) AS gemiddelde
-- FROM grades g
-- INNER JOIN students s ON s.id = g.student_id
-- INNER JOIN subjects v ON v.id = g.subject_id
-- GROUP BY s.id, s.name, v.id, v.name;
