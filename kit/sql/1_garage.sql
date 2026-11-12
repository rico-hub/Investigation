-- =====================================================================
-- SCENARIO 1: GARAGE  (alle 3 de vragen = JA)
--   1. Het ding?            auto            -> cars
--   2. Kies uit een lijst?  merk            -> brands        (cars krijgt brand_id)
--   3. User doet iets?      afspraak maken  -> appointments  (user_id + car_id)
--
--   brands  <--  cars  <--  appointments  -->  users
--
-- In de kit: categories = brands, items = cars, orders = appointments
-- Importeren: phpMyAdmin > Importeren, of alles plakken in tabblad SQL
-- =====================================================================

DROP DATABASE IF EXISTS garage;
CREATE DATABASE garage CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE garage;

-- ---- PARENTS eerst ----------------------------------------------------
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

CREATE TABLE brands (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_brands_name (name)
) ENGINE=InnoDB;

-- ---- CHILDREN daarna ----------------------------------------------------
CREATE TABLE cars (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand_id       INT UNSIGNED NOT NULL,             -- de auto KIEST een merk
    model          VARCHAR(100) NOT NULL,
    license_plate  VARCHAR(10) NOT NULL,
    build_year     SMALLINT UNSIGNED NOT NULL,
    price          DECIMAL(9,2) NOT NULL,
    status         ENUM('beschikbaar', 'in_onderhoud', 'verkocht') NOT NULL DEFAULT 'beschikbaar',
    deleted_at     DATETIME NULL DEFAULT NULL,        -- soft delete
    PRIMARY KEY (id),
    UNIQUE KEY uq_cars_plate (license_plate),         -- kenteken maar 1 keer
    CONSTRAINT fk_cars_brand
        FOREIGN KEY (brand_id) REFERENCES brands (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_cars_year  CHECK (build_year BETWEEN 1950 AND 2100),
    CONSTRAINT chk_cars_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id           INT UNSIGNED NOT NULL,          -- wie
    car_id            INT UNSIGNED NOT NULL,          -- welke auto
    appointment_date  DATETIME NOT NULL,
    note              VARCHAR(255) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_appointments_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_appointments_car
        FOREIGN KEY (car_id) REFERENCES cars (id)
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

-- ---- DUMMY DATA (zelfde volgorde) -------------------------------------
-- wachtwoord = password
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',  'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Paul Bakker', 'paul@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO brands (id, name) VALUES
(1, 'Toyota'), (2, 'Volkswagen'), (3, 'Kia');

INSERT INTO cars (id, brand_id, model, license_plate, build_year, price, status) VALUES
(1, 1, 'Yaris',  'AB-123-C', 2019, 12500.00, 'beschikbaar'),
(2, 2, 'Golf',   'XY-987-Z', 2021, 21950.00, 'in_onderhoud'),
(3, 3, 'Picanto','KL-456-M', 2018,  8750.00, 'verkocht');

INSERT INTO appointments (user_id, car_id, appointment_date, note) VALUES
(2, 1, NOW() + INTERVAL 3 DAY, 'Proefrit'),
(2, 2, NOW() + INTERVAL 7 DAY, NULL);

-- ---- CONTROLE: alles aan elkaar (JOIN via de foreign keys) -------------
-- SELECT a.id, u.name AS klant, b.name AS merk, c.model, a.appointment_date
-- FROM appointments a
-- INNER JOIN users u  ON u.id = a.user_id
-- INNER JOIN cars c   ON c.id = a.car_id
-- INNER JOIN brands b ON b.id = c.brand_id;
