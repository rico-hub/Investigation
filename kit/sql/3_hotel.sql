-- =====================================================================
-- SCENARIO 3: HOTEL  (alle 3 = JA, met datums van-tot)
--   1. Het ding?            kamer           -> rooms
--   2. Kies uit een lijst?  kamertype       -> room_types (rooms krijgt room_type_id)
--   3. User doet iets?      boeken          -> bookings   (user_id + room_id)
--
--   room_types  <--  rooms  <--  bookings  -->  users
--
-- In de kit: categories = room_types, items = rooms, orders = bookings
-- =====================================================================

DROP DATABASE IF EXISTS hotel;
CREATE DATABASE hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE hotel;

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

CREATE TABLE room_types (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_room_types_name (name)
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_type_id     INT UNSIGNED NOT NULL,
    room_number      VARCHAR(10) NOT NULL,
    price_per_night  DECIMAL(7,2) NOT NULL,
    status           ENUM('vrij', 'schoonmaak', 'buiten_gebruik') NOT NULL DEFAULT 'vrij',
    deleted_at       DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rooms_number (room_number),
    CONSTRAINT fk_rooms_type
        FOREIGN KEY (room_type_id) REFERENCES room_types (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_rooms_price CHECK (price_per_night > 0)
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    room_id     INT UNSIGNED NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    guests      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_room
        FOREIGN KEY (room_id) REFERENCES rooms (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_bookings_dates  CHECK (end_date > start_date),   -- vertrek NA aankomst
    CONSTRAINT chk_bookings_guests CHECK (guests BETWEEN 1 AND 6)
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

INSERT INTO room_types (id, name) VALUES
(1, 'Eenpersoons'), (2, 'Tweepersoons'), (3, 'Suite');

INSERT INTO rooms (id, room_type_id, room_number, price_per_night, status) VALUES
(1, 1, '101', 69.00,  'vrij'),
(2, 2, '102', 99.00,  'vrij'),
(3, 3, '201', 189.00, 'schoonmaak');

INSERT INTO bookings (user_id, room_id, start_date, end_date, guests) VALUES
(2, 2, CURDATE() + INTERVAL 5 DAY, CURDATE() + INTERVAL 8 DAY, 2);

-- ---- CONTROLE --------------------------------------------------------
-- Boekingen met aantal nachten en totaalprijs:
-- SELECT b.id, u.name, r.room_number, t.name AS type,
--        DATEDIFF(b.end_date, b.start_date) AS nachten,
--        DATEDIFF(b.end_date, b.start_date) * r.price_per_night AS totaal
-- FROM bookings b
-- INNER JOIN users u      ON u.id = b.user_id
-- INNER JOIN rooms r      ON r.id = b.room_id
-- INNER JOIN room_types t ON t.id = r.room_type_id;
-- Is kamer 2 vrij van dag X tot Y? (0 rijen = vrij)
-- SELECT id FROM bookings WHERE room_id = 2 AND start_date < 'Y' AND end_date > 'X';
