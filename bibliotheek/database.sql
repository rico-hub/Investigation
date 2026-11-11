-- =====================================================================
-- BIBLIOTHEEK - database.sql
-- Importeren: phpMyAdmin > tabblad "Importeren" > dit bestand kiezen
--         of: phpMyAdmin > tabblad "SQL" > alles plakken > Starten
-- LET OP: dit script verwijdert de database eerst (DROP) = alles opnieuw.
-- =====================================================================

DROP DATABASE IF EXISTS bibliotheek;
CREATE DATABASE bibliotheek CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bibliotheek;

-- ---------------------------------------------------------------------
-- 1. TABELLEN (volgorde is belangrijk: eerst "parent", dan "child")
--    Parent = tabel waar naar verwezen wordt (users, categories)
--    Child  = tabel met de FOREIGN KEY (books, loans, action_log)
-- ---------------------------------------------------------------------

CREATE TABLE users (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,                 -- hash is 60+ tekens, 255 = toekomstvast
    role        ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)                  -- e-mail mag maar 1 keer bestaan
) ENGINE=InnoDB;

CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE books (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id   INT UNSIGNED NOT NULL,               -- zelfde type als categories.id !
    title         VARCHAR(200) NOT NULL,
    author        VARCHAR(150) NOT NULL,
    publish_year  SMALLINT UNSIGNED NULL,
    status        ENUM('beschikbaar', 'uitgeleend', 'reparatie') NOT NULL DEFAULT 'beschikbaar',
    deleted_at    DATETIME NULL DEFAULT NULL,           -- SOFT DELETE: NULL = actief
    PRIMARY KEY (id),
    CONSTRAINT fk_books_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,                             -- categorie met boeken kan niet weg
    CONSTRAINT chk_books_year
        CHECK (publish_year IS NULL OR publish_year BETWEEN 1450 AND 2100)
) ENGINE=InnoDB;

CREATE TABLE loans (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED NOT NULL,
    book_id      INT UNSIGNED NOT NULL,
    loan_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_date     DATE NOT NULL,
    returned_at  DATETIME NULL DEFAULT NULL,           -- NULL = nog niet teruggebracht
    PRIMARY KEY (id),
    CONSTRAINT fk_loans_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,                             -- gebruiker met leningen kan niet weg
    CONSTRAINT fk_loans_book
        FOREIGN KEY (book_id) REFERENCES books (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT                              -- boek met leningen kan niet hard weg
) ENGINE=InnoDB;

-- Logboek: wie deed wat en wanneer (zie notes/09)
CREATE TABLE action_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NULL,                      -- NULL mag: bv. mislukte login
    action      VARCHAR(50) NOT NULL,
    details     VARCHAR(255) NULL,
    ip_address  VARCHAR(45) NULL,                       -- 45 = lang genoeg voor IPv6
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_log_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL                              -- user weg? log blijft, user_id wordt NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. DUMMY DATA (ook in volgorde: eerst parents, dan children)
-- Wachtwoord van ALLE dummy accounts = password
-- (hash gemaakt met password_hash('password', PASSWORD_DEFAULT))
-- Eigen hash maken: open tools/make_hash.php in de browser
-- ---------------------------------------------------------------------

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',  'admin@test.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Bram Bakker', 'bram@test.nl',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 'Cleo de Vries', 'cleo@test.nl','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (id, name) VALUES
(1, 'Roman'),
(2, 'Thriller'),
(3, 'Fantasy'),
(4, 'Studieboek'),
(5, 'Kinderboek');

INSERT INTO books (id, category_id, title, author, publish_year, status) VALUES
(1,  1, 'De Avonden',                 'Gerard Reve',        1947, 'beschikbaar'),
(2,  1, 'Het Diner',                  'Herman Koch',        2009, 'uitgeleend'),
(3,  2, 'Gone Girl',                  'Gillian Flynn',      2012, 'beschikbaar'),
(4,  2, 'De Da Vinci Code',           'Dan Brown',          2003, 'beschikbaar'),
(5,  3, 'De Hobbit',                  'J.R.R. Tolkien',     1937, 'uitgeleend'),
(6,  3, 'Harry Potter en de Steen der Wijzen', 'J.K. Rowling', 1997, 'beschikbaar'),
(7,  4, 'PHP & MySQL voor Beginners', 'Jan Jansen',         2021, 'beschikbaar'),
(8,  4, 'Databases Ontwerpen',        'Piet Pieters',       2019, 'reparatie'),
(9,  5, 'Nijntje in de Speeltuin',    'Dick Bruna',         1975, 'beschikbaar'),
(10, 5, 'Jip en Janneke',             'Annie M.G. Schmidt', 1953, 'beschikbaar'),
(11, 1, 'Max Havelaar',               'Multatuli',          1860, 'beschikbaar'),
(12, 2, 'Het Bittere Kruid',          'Marga Minco',        1957, 'beschikbaar');

-- Soft-deleted boek (staat in de prullenbak, niet in de lijst)
INSERT INTO books (id, category_id, title, author, publish_year, status, deleted_at) VALUES
(13, 3, 'Oud Kapot Boek', 'Onbekend', 1990, 'beschikbaar', NOW());

-- Leningen: boek 2 en 5 zijn uitgeleend (klopt met status hierboven)
INSERT INTO loans (user_id, book_id, loan_date, due_date, returned_at) VALUES
(2, 2,  NOW() - INTERVAL 5 DAY,  CURDATE() + INTERVAL 16 DAY, NULL),
(3, 5,  NOW() - INTERVAL 30 DAY, CURDATE() - INTERVAL 9 DAY,  NULL),   -- te laat!
(2, 3,  NOW() - INTERVAL 60 DAY, CURDATE() - INTERVAL 39 DAY, NOW() - INTERVAL 45 DAY);

INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1, 'login',       'Dummy data',          '127.0.0.1'),
(2, 'checkout',    'Boek #2 geleend',     '127.0.0.1'),
(3, 'checkout',    'Boek #5 geleend',     '127.0.0.1');

-- ---------------------------------------------------------------------
-- 3. HANDIGE TEST-QUERIES (los uitvoeren in phpMyAdmin)
-- ---------------------------------------------------------------------
-- Alle boeken met categorie (JOIN via de foreign key):
--   SELECT b.id, b.title, c.name FROM books b INNER JOIN categories c ON c.id = b.category_id;
-- Aantal boeken per status (TotalCount per groep):
--   SELECT status, COUNT(*) AS aantal FROM books WHERE deleted_at IS NULL GROUP BY status;
-- Test de foreign key (moet FOUT geven, #1452):
--   INSERT INTO books (category_id, title, author) VALUES (999, 'Test', 'Test');
-- Test RESTRICT (moet FOUT geven, #1451):
--   DELETE FROM categories WHERE id = 1;
-- Test CHECK (moet FOUT geven, #4025):
--   INSERT INTO books (category_id, title, author, publish_year) VALUES (1, 'Test', 'Test', 3000);
