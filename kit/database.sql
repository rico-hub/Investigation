-- =====================================================================
-- KIT - database.sql   (PUZZELSTUK: database met gekoppelde tabellen)
-- Importeren: phpMyAdmin > tabblad "Importeren" > dit bestand kiezen
--         of: phpMyAdmin > tabblad "SQL" > alles plakken > Starten
-- LET OP: dit script verwijdert de database eerst (DROP) = alles opnieuw.
--
-- AANPASSEN: vervang "kit" 3x hieronder door de naam van jouw database
--            (zelfde naam als DB_NAME in config/config.php).
--
-- Zo hangen de tabellen aan elkaar (pijl = foreign key, wijst naar de parent):
--
--   categories  <----  items  <----  orders  ---->  users  <----  action_log
--   (parent)          (child)      (koppeltabel)   (parent)        (child)
--
-- Minimaal 3 gekoppelde tabellen nodig? categories + items + orders (+ users).
-- Foreign key later toevoegen, weghalen of tijdelijk uitzetten: zie database_fk.sql
-- =====================================================================

DROP DATABASE IF EXISTS kit;
CREATE DATABASE kit CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE kit;

-- ---------------------------------------------------------------------
-- 1. TABELLEN
--    VOLGORDE IS BELANGRIJK: eerst de parent, dan de child.
--    Parent = tabel waar naar verwezen wordt (users, categories)
--    Child  = tabel met de FOREIGN KEY (items, orders, action_log)
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
CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- CHILD tabel: elk item hoort bij 1 categorie
-- AANPASSEN: jouw eigen kolommen (kolom erbij of weg? Zoek in *.php op price en doe op elke plek hetzelfde)
CREATE TABLE items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id  INT UNSIGNED NOT NULL,                -- MOET exact hetzelfde type zijn als categories.id
    name         VARCHAR(150) NOT NULL,                -- NOT NULL: verplicht veld
    price        DECIMAL(8,2) NOT NULL DEFAULT 0.00,   -- bedrag: 8 cijfers, 2 achter de komma
    item_date    DATE NULL DEFAULT NULL,               -- NULL: mag leeg blijven
    status       ENUM('actief', 'inactief', 'uitverkocht') NOT NULL DEFAULT 'actief',
    deleted_at   DATETIME NULL DEFAULT NULL,           -- SOFT DELETE: NULL = actief
    PRIMARY KEY (id),
    CONSTRAINT fk_items_category                       -- naam: fk_<child>_<parent>
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE                              -- id van categorie wijzigt? hier ook
        ON DELETE RESTRICT,                            -- categorie met items kan niet weg
    CONSTRAINT chk_items_price
        CHECK (price >= 0)                             -- CHECK: prijs mag niet negatief
) ENGINE=InnoDB;

-- KOPPELTABEL: wie (user) bestelde wat (item). Twee foreign keys.
CREATE TABLE orders (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    item_id     INT UNSIGNED NOT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,                            -- user met bestellingen kan niet weg
    CONSTRAINT fk_orders_item
        FOREIGN KEY (item_id) REFERENCES items (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT                             -- item met bestellingen kan niet hard weg
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

INSERT INTO categories (id, name) VALUES
(1, 'Categorie A'),
(2, 'Categorie B'),
(3, 'Categorie C');

-- category_id moet een id zijn dat in categories BESTAAT (anders fout #1452)
INSERT INTO items (id, category_id, name, price, item_date, status) VALUES
(1, 1, 'Item een',   9.99,  '2026-01-15', 'actief'),
(2, 1, 'Item twee',  24.50, '2026-02-01', 'actief'),
(3, 2, 'Item drie',  5.00,  NULL,         'inactief'),
(4, 2, 'Item vier',  120.00,'2026-03-20', 'uitverkocht'),
(5, 3, 'Item vijf',  49.95, '2026-04-05', 'actief'),
(6, 3, 'Item zes',   0.00,  NULL,         'actief');

-- Soft-deleted item (staat in de prullenbak, niet in de lijst)
INSERT INTO items (id, category_id, name, price, status, deleted_at) VALUES
(7, 1, 'Oud item', 1.00, 'inactief', NOW());

INSERT INTO orders (user_id, item_id) VALUES
(2, 1),
(2, 5),
(3, 2);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1, 'login',    'Dummy data',         '127.0.0.1'),
(2, 'checkout', 'Item #1 besteld',    '127.0.0.1');

-- ---------------------------------------------------------------------
-- 3. TESTEN OF DE KOPPELING WERKT (los uitvoeren in phpMyAdmin > SQL)
-- ---------------------------------------------------------------------
-- Items met hun categorie (JOIN via de foreign key):
--   SELECT i.id, i.name, c.name AS categorie FROM items i INNER JOIN categories c ON c.id = i.category_id;
-- Alle 3 de tabellen aan elkaar:
--   SELECT o.id, u.name, i.name, c.name FROM orders o
--   INNER JOIN users u ON u.id = o.user_id
--   INNER JOIN items i ON i.id = o.item_id
--   INNER JOIN categories c ON c.id = i.category_id;
-- Test de foreign key (MOET een fout geven, #1452: categorie 999 bestaat niet):
--   INSERT INTO items (category_id, name) VALUES (999, 'Test');
-- Test RESTRICT (MOET een fout geven, #1451: er hangen items aan):
--   DELETE FROM categories WHERE id = 1;
-- Test CHECK (MOET een fout geven, #4025):
--   INSERT INTO items (category_id, name, price) VALUES (1, 'Test', -5);
