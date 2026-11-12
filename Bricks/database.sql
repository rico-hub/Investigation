-- =====================================================================
-- Bricks - database voor de bouwstenen (Notion pagina 7)
-- Importeren: http://localhost/phpmyadmin -> Importeren -> dit bestand -> Starten
-- =====================================================================
DROP DATABASE IF EXISTS bricks;
CREATE DATABASE bricks CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bricks;

-- Parent: lijst voor de dropdown "uit de database"
CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- Parent: lijst voor "meerdere checkboxes"
CREATE TABLE tags (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tags_name (name)
) ENGINE=InnoDB;

-- Child: elk soort veld heeft hier een kolom
CREATE TABLE items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id  INT UNSIGNED NOT NULL,                                       -- dropdown uit de database
    name         VARCHAR(150) NOT NULL,                                       -- tekstveld
    description  TEXT NULL,                                                   -- textarea
    priority     ENUM('laag', 'normaal', 'hoog') NOT NULL DEFAULT 'normaal',  -- dropdown / radio met vaste waarden
    status       ENUM('actief', 'inactief', 'uitverkocht') NOT NULL DEFAULT 'actief',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,                               -- ja / nee
    price        DECIMAL(8,2) NOT NULL DEFAULT 0.00,                          -- bedrag
    stock        INT UNSIGNED NOT NULL DEFAULT 0,                             -- heel getal
    start_date   DATE NULL,                                                   -- datum
    start_at     DATETIME NULL,                                               -- datum + tijd
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_items_price CHECK (price >= 0)
) ENGINE=InnoDB;

-- Koppeltabel (veel-op-veel): welk item heeft welke tags
CREATE TABLE item_tags (
    item_id  INT UNSIGNED NOT NULL,
    tag_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (item_id, tag_id),
    CONSTRAINT fk_item_tags_item FOREIGN KEY (item_id) REFERENCES items (id) ON DELETE CASCADE,
    CONSTRAINT fk_item_tags_tag  FOREIGN KEY (tag_id)  REFERENCES tags (id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Dummy data (parents eerst)
-- ---------------------------------------------------------------------
INSERT INTO categories (id, name) VALUES
(1, 'Boeken'), (2, 'Sport'), (3, 'School');

INSERT INTO tags (id, name) VALUES
(1, 'Nieuw'), (2, 'Populair'), (3, 'Aanbieding'), (4, 'Tip');

INSERT INTO items (id, category_id, name, description, priority, status, is_active, price, stock, start_date, start_at) VALUES
(1, 1, 'Leesboek', 'Een dik boek.\nMet een tweede regel.', 'normaal', 'actief', 1, 1234.50, 10, CURDATE() + INTERVAL 3 DAY, NOW() + INTERVAL 3 DAY),
(2, 1, 'Woordenboek', NULL, 'laag', 'uitverkocht', 1, 19.95, 0, NULL, NULL),
(3, 2, 'Voetbal', 'Een hele lange omschrijving die veel langer is dan vijftig tekens, zodat je het inkorten kunt zien.', 'hoog', 'actief', 1, 24.99, 5, CURDATE(), NULL),
(4, 2, 'Tennisracket', '', 'normaal', 'inactief', 0, 89.00, 2, NULL, NULL),
(5, 3, 'Rekenmachine', 'Voor wiskunde.', 'hoog', 'actief', 1, 15.00, 20, CURDATE() + INTERVAL 10 DAY, NULL);

INSERT INTO item_tags (item_id, tag_id) VALUES
(1, 1), (1, 4), (3, 2), (3, 3), (5, 1);
