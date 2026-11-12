-- =====================================================================
-- SCENARIO 5: WEBSHOP MET 2 LIJSTEN + BESTELREGELS
--   1. Het ding?            product         -> products
--   2. Kies uit een lijst?  merk EN kleur   -> brands + colors (products krijgt brand_id EN color_id)
--   3. User doet iets?      bestellen       -> orders (user_id) + order_lines (order_id + product_id)
--
--   brands  <--+
--              +--  products  <--  order_lines  -->  orders  -->  users
--   colors  <--+
--
-- In de kit: categories = brands; kopieer het categorie-stuk 1x voor colors
--   (2e dropdown in het formulier, 2e check in validate, 2e JOIN in de SELECT)
-- 1 bestelling met MEERDERE producten = bestelregels (order_lines)
-- =====================================================================

DROP DATABASE IF EXISTS webshop;
CREATE DATABASE webshop CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE webshop;

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

CREATE TABLE colors (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_colors_name (name)
) ENGINE=InnoDB;

CREATE TABLE products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    brand_id    INT UNSIGNED NOT NULL,               -- lijst 1
    color_id    INT UNSIGNED NOT NULL,               -- lijst 2
    name        VARCHAR(150) NOT NULL,
    price       DECIMAL(8,2) NOT NULL,
    stock       INT UNSIGNED NOT NULL DEFAULT 0,
    deleted_at  DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_products_brand
        FOREIGN KEY (brand_id) REFERENCES brands (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_products_color
        FOREIGN KEY (color_id) REFERENCES colors (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    status      ENUM('open', 'betaald', 'verzonden') NOT NULL DEFAULT 'open',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE order_lines (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    product_id  INT UNSIGNED NOT NULL,
    quantity    INT UNSIGNED NOT NULL DEFAULT 1,
    price       DECIMAL(8,2) NOT NULL,               -- prijs op het moment van kopen
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_line (order_id, product_id), -- zelfde product niet 2x in 1 bestelling
    CONSTRAINT fk_order_lines_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,         -- bestelling weg = regels weg
    CONSTRAINT fk_order_lines_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_order_lines_quantity CHECK (quantity >= 1)
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

INSERT INTO brands (id, name) VALUES (1, 'Nike'), (2, 'Adidas');
INSERT INTO colors (id, name) VALUES (1, 'Zwart'), (2, 'Wit'), (3, 'Rood');

INSERT INTO products (id, brand_id, color_id, name, price, stock) VALUES
(1, 1, 1, 'Hardloopschoen', 89.95, 10),
(2, 2, 2, 'Sporttas',       34.50, 4),
(3, 1, 3, 'Pet',            19.99, 0);

INSERT INTO orders (id, user_id, status) VALUES (1, 2, 'betaald');
INSERT INTO order_lines (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 89.95),
(1, 2, 2, 34.50);

-- ---- CONTROLE --------------------------------------------------------
-- Producten met merk en kleur (2 JOINs vanaf 1 tabel):
-- SELECT p.name, b.name AS merk, c.name AS kleur, p.price
-- FROM products p
-- INNER JOIN brands b ON b.id = p.brand_id
-- INNER JOIN colors c ON c.id = p.color_id;
-- Totaal per bestelling:
-- SELECT o.id, u.name, SUM(l.quantity * l.price) AS totaal
-- FROM orders o
-- INNER JOIN users u       ON u.id = o.user_id
-- INNER JOIN order_lines l ON l.order_id = o.id
-- GROUP BY o.id, u.name;
