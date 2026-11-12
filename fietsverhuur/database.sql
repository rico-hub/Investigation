-- =====================================================================
-- KIT - database.sql   (PUZZELSTUK: database met gekoppelde tabellen)
-- Importeren: phpMyAdmin > tabblad "Importeren" > dit bestand kiezen
--         of: phpMyAdmin > tabblad "SQL" > alles plakken > Starten
-- LET OP: dit script verwijdert de database eerst (DROP) = alles opnieuw.
--
-- AANPASSEN: vervang "fietsverhuur" 3x hieronder door de naam van jouw database
--            (zelfde naam als DB_NAME in config/config.php).
--
-- Zo hangen de tabellen aan elkaar (pijl = foreign key, wijst naar de parent):
--
--   bike_types  <----  bikes  <----  rentals  ---->  users  <----  action_log
--   (parent)          (child)      (koppeltabel)   (parent)        (child)
--
-- Minimaal 3 gekoppelde tabellen nodig? bike_types + bikes + rentals (+ users).
-- Foreign key later toevoegen, weghalen of tijdelijk uitzetten: zie database_fk.sql
-- =====================================================================

DROP DATABASE IF EXISTS fietsverhuur;
CREATE DATABASE fietsverhuur CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE fietsverhuur;

-- ---------------------------------------------------------------------
-- 1. TABELLEN
--    VOLGORDE IS BELANGRIJK: eerst de parent, dan de child.
--    Parent = tabel waar naar verwezen wordt (users, bike_types)
--    Child  = tabel met de FOREIGN KEY (bikes, rentals, action_log)
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
CREATE TABLE bike_types (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bike_types_name (name)
) ENGINE=InnoDB;

-- CHILD tabel: elke fiets hoort bij 1 type
CREATE TABLE bikes (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bike_type_id  INT UNSIGNED NOT NULL,               -- MOET exact hetzelfde type zijn als bike_types.id
    name          VARCHAR(150) NOT NULL,               -- NOT NULL: verplicht veld
    frame_number  VARCHAR(20) NOT NULL,                -- framenummer
    price         DECIMAL(8,2) NOT NULL DEFAULT 0.00,  -- prijs per dag
    status        ENUM('beschikbaar', 'in_reparatie', 'uit_gebruik') NOT NULL DEFAULT 'beschikbaar',
    deleted_at    DATETIME NULL DEFAULT NULL,          -- SOFT DELETE (prullenbak): NULL = actief
    PRIMARY KEY (id),
    UNIQUE KEY uq_bikes_frame_number (frame_number),   -- UNIQUE: framenummer maar 1 keer
    CONSTRAINT fk_bikes_bike_type                      -- naam: fk_<child>_<parent>
        FOREIGN KEY (bike_type_id) REFERENCES bike_types (id)
        ON UPDATE CASCADE                              -- id van het type wijzigt? hier ook
        ON DELETE RESTRICT,                            -- type met fietsen kan niet weg
    CONSTRAINT chk_bikes_price
        CHECK (price >= 0)                             -- CHECK: prijs mag niet negatief
) ENGINE=InnoDB;

-- KOPPELTABEL: wie (user) huurt welke fiets (bike), van - tot. Twee foreign keys.
CREATE TABLE rentals (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    bike_id     INT UNSIGNED NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT chk_rentals_dates CHECK (end_date > start_date),
    CONSTRAINT fk_rentals_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,                            -- user met bestellingen kan niet weg
    CONSTRAINT fk_rentals_bike
        FOREIGN KEY (bike_id) REFERENCES bikes (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT                             -- bike met bestellingen kan niet hard weg
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

INSERT INTO bike_types (id, name) VALUES
(1, 'Stadsfiets'),
(2, 'E-bike'),
(3, 'Mountainbike');

-- bike_type_id moet een id zijn dat in bike_types BESTAAT (anders fout #1452)
INSERT INTO bikes (id, bike_type_id, name, frame_number, price, status) VALUES
(1, 1, 'Gazelle Orange',      'GZ-1001', 12.50, 'beschikbaar'),
(2, 1, 'Batavus Fonk',        'BT-2002', 10.00, 'beschikbaar'),
(3, 2, 'Sparta M7',           'SP-3003', 25.00, 'in_reparatie'),
(4, 2, 'Stella Livorno',      'ST-4004', 22.50, 'beschikbaar'),
(5, 3, 'Trek Marlin',         'TR-5005', 18.00, 'beschikbaar'),
(6, 3, 'Giant Talon',         'GI-6006', 17.50, 'uit_gebruik');

-- Soft-deleted fiets (staat in de prullenbak, niet in de lijst)
INSERT INTO bikes (id, bike_type_id, name, frame_number, price, status, deleted_at) VALUES
(7, 1, 'Oude Union', 'UN-7007', 5.00, 'uit_gebruik', NOW());

INSERT INTO rentals (user_id, bike_id, start_date, end_date) VALUES
(2, 1, CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 8 DAY),
(2, 5, CURDATE() + INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY),
(3, 2, CURDATE() + INTERVAL 3 DAY, CURDATE() + INTERVAL 4 DAY);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1, 'login',    'Dummy data',         '127.0.0.1'),
(2, 'checkout', 'Fiets #1 gehuurd',   '127.0.0.1');

-- ---------------------------------------------------------------------
-- 3. TESTEN OF DE KOPPELING WERKT (los uitvoeren in phpMyAdmin > SQL)
-- ---------------------------------------------------------------------
-- Bikes met hun categorie (JOIN via de foreign key):
--   SELECT i.id, i.name, c.name AS categorie FROM bikes i INNER JOIN bike_types c ON c.id = i.bike_type_id;
-- Alle 3 de tabellen aan elkaar:
--   SELECT o.id, u.name, i.name, c.name FROM rentals o
--   INNER JOIN users u ON u.id = o.user_id
--   INNER JOIN bikes i ON i.id = o.bike_id
--   INNER JOIN bike_types c ON c.id = i.bike_type_id;
-- Test de foreign key (MOET een fout geven, #1452: categorie 999 bestaat niet):
--   INSERT INTO bikes (bike_type_id, name) VALUES (999, 'Test');
-- Test RESTRICT (MOET een fout geven, #1451: er hangen bikes aan):
--   DELETE FROM bike_types WHERE id = 1;
-- Test CHECK (MOET een fout geven, #4025):
--   INSERT INTO bikes (bike_type_id, name, price) VALUES (1, 'Test', -5);
