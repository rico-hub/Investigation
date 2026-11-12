-- =====================================================================
-- DIERENASIEL - database.sql   (database met gekoppelde tabellen)
-- Importeren: phpMyAdmin > tabblad "Importeren" > dit bestand kiezen
--         of: phpMyAdmin > tabblad "SQL" > alles plakken > Starten
-- LET OP: dit script verwijdert de database eerst (DROP) = alles opnieuw.
--
-- Database naam: dierenasiel (zelfde als DB_NAME in config/config.php).
--
-- Zo hangen de tabellen aan elkaar (pijl = foreign key, wijst naar de parent):
--
--   animal_types  <----  animals  <----  appointments  ---->  users  <----  action_log
--   (parent)            (child)        (koppeltabel)       (parent)     (child)
--
-- Minimaal 3 gekoppelde tabellen nodig? animal_types + animals + appointments (+ users).
-- Foreign key later toevoegen, weghalen of tijdelijk uitzetten: zie database_fk.sql
-- =====================================================================

DROP DATABASE IF EXISTS dierenasiel;
CREATE DATABASE dierenasiel CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE dierenasiel;

-- ---------------------------------------------------------------------
-- 1. TABELLEN
--    VOLGORDE IS BELANGRIJK: eerst de parent, dan de child.
--    Parent = tabel waar naar verwezen wordt (users, animal_types)
--    Child  = tabel met de FOREIGN KEY (animals, appointments, action_log)
-- ---------------------------------------------------------------------

-- UNIVERSEEL: users (nodig voor login). Niet aanpassen.
CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,                 -- hash is 60+ tekens, 255 = toekomstvast
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),                                  -- PRIMARY KEY: uniek nummer per rij
    UNIQUE KEY uq_users_email (email)                  -- UNIQUE: e-mail mag maar 1 keer bestaan
) ENGINE=InnoDB;

-- PARENT tabel (voor de dropdown)
CREATE TABLE animal_types (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_animal_types_name (name)
) ENGINE=InnoDB;

-- CHILD tabel: elk dier hoort bij 1 diersoort
-- Kolom erbij of weg? Zoek in *.php op age en doe op elke plek hetzelfde.
CREATE TABLE animals (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    animal_type_id  INT UNSIGNED NOT NULL,                -- MOET exact hetzelfde type zijn als animal_types.id
    name         VARCHAR(150) NOT NULL,                -- NOT NULL: verplicht veld
    age          TINYINT UNSIGNED NOT NULL DEFAULT 0,  -- leeftijd in jaren (heel getal)
    status       ENUM('beschikbaar', 'gereserveerd', 'geadopteerd') NOT NULL DEFAULT 'beschikbaar',
    deleted_at   DATETIME NULL DEFAULT NULL,           -- SOFT DELETE: NULL = niet verwijderd
    PRIMARY KEY (id),
    CONSTRAINT fk_animals_animal_type                       -- naam: fk_<child>_<parent>
        FOREIGN KEY (animal_type_id) REFERENCES animal_types (id)
        ON UPDATE CASCADE                              -- id van diersoort wijzigt? hier ook
        ON DELETE RESTRICT,                            -- diersoort met dieren kan niet weg
    CONSTRAINT chk_animals_age
        CHECK (age <= 40)                              -- CHECK: leeftijd 0 t/m 40 jaar
) ENGINE=InnoDB;

-- KOPPELTABEL: wie (user) maakte een afspraak voor welk dier (animal). Twee foreign keys.
CREATE TABLE appointments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    animal_id     INT UNSIGNED NOT NULL,
    booking_date  DATE NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,                            -- user met afspraken kan niet weg
    CONSTRAINT fk_appointments_animal
        FOREIGN KEY (animal_id) REFERENCES animals (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT                             -- dier met afspraken kan niet hard weg
) ENGINE=InnoDB;

-- UNIVERSEEL: logboek (wie deed wat en wanneer). Niet aanpassen.
CREATE TABLE action_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,                     -- NULL mag: bv. mislukte login
    action      VARCHAR(50) NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL                             -- user weg? log blijft, user_id wordt NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DUMMY DATA (zelfde volgorde: eerst parents, dan children)
-- Wachtwoord van ALLE dummy accounts = password
-- Eigen hash maken: open tools/hash_maker_reader.php in de browser
-- ---------------------------------------------------------------------

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',    'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Paul Bakker',   'paul@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 'Cleo de Vries', 'cleo@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO animal_types (id, name) VALUES
(1, 'Hond'),
(2, 'Kat'),
(3, 'Konijn');

-- animal_type_id moet een id zijn dat in animal_types BESTAAT (anders fout #1452)
INSERT INTO animals (id, animal_type_id, name, age, status) VALUES
(1, 1, 'Rex',      4, 'beschikbaar'),
(2, 1, 'Luna',     2, 'beschikbaar'),
(3, 2, 'Minoes',   7, 'gereserveerd'),
(4, 2, 'Tijger',   1, 'geadopteerd'),
(5, 3, 'Snuffel',  3, 'beschikbaar'),
(6, 3, 'Pluis',    0, 'beschikbaar');

-- Soft-deleted dier (staat in de prullenbak, niet in de lijst)
INSERT INTO animals (id, animal_type_id, name, age, status, deleted_at) VALUES
(7, 1, 'Bobby', 12, 'geadopteerd', NOW());

INSERT INTO appointments (user_id, animal_id, booking_date) VALUES
(2, 1, CURDATE() + INTERVAL 5 DAY),
(2, 5, CURDATE() + INTERVAL 1 DAY),
(3, 2, CURDATE() + INTERVAL 3 DAY);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1, 'login',    'Dummy data',         '127.0.0.1'),
(2, 'checkout', '1 dier(en), afspraak voor: #1', '127.0.0.1');

-- ---------------------------------------------------------------------
-- 3. TESTEN OF DE KOPPELING WERKT (los uitvoeren in phpMyAdmin > SQL)
-- ---------------------------------------------------------------------
-- Dieren met hun diersoort (JOIN via de foreign key):
--   SELECT i.id, i.name, c.name AS diersoort FROM animals i INNER JOIN animal_types c ON c.id = i.animal_type_id;
-- Alle 3 de tabellen aan elkaar:
--   SELECT o.id, u.name, i.name, c.name FROM appointments o
--   INNER JOIN users u ON u.id = o.user_id
--   INNER JOIN animals i ON i.id = o.animal_id
--   INNER JOIN animal_types c ON c.id = i.animal_type_id;
-- Test de foreign key (MOET een fout geven, #1452: diersoort 999 bestaat niet):
--   INSERT INTO animals (animal_type_id, name) VALUES (999, 'Test');
-- Test RESTRICT (MOET een fout geven, #1451: er hangen dieren aan):
--   DELETE FROM animal_types WHERE id = 1;
-- Test CHECK (MOET een fout geven, #4025):
--   INSERT INTO animals (animal_type_id, name, age) VALUES (1, 'Test', 99);
