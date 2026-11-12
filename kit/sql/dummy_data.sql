-- =====================================================================
-- DUMMY DATA SNIPPETS  (nep-gegevens om mee te testen)
-- Werkt op de tabellen van de kit (users, categories, items, orders, action_log).
-- Heb je hernoemd (bv. items -> bikes)? Vervang dan ook hier de namen.
--
-- Gebruik: kopieer het stukje dat je nodig hebt naar phpMyAdmin > tabblad SQL.
-- Of draai het hele bestand: dan wordt alles eerst leeggemaakt (stap 0).
--
-- VOLGORDE IS BELANGRIJK: eerst de parents (users, categories),
--                         dan de children (items), dan de koppeltabel (orders).
-- =====================================================================

USE kit;   -- AANPASSEN: naam van jouw database


-- ---------------------------------------------------------------------
-- 0. ALLES LEEGMAKEN (zonder de tabellen weg te gooien)
--    TRUNCATE = tabel leeg + nummering (id) begint weer bij 1.
--    Children eerst, of de controle even uit (bypass).
-- ---------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE action_log;
TRUNCATE TABLE orders;
TRUNCATE TABLE items;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------------
-- 1. ACCOUNTS
--    Wachtwoord van ALLE accounts hieronder = password
--    De lange tekst is de hash van 'password' (gemaakt met password_hash).
--    NOOIT een gewoon wachtwoord in de database zetten: dan werkt inloggen niet.
--    Eigen wachtwoord? Maak de hash met tools/hash_maker_reader.php
--    of in PHP:  echo password_hash('jouwwachtwoord', PASSWORD_DEFAULT);
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Anja',    'admin@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Paul Bakker',   'paul@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(3, 'Cleo de Vries', 'cleo@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
(4, 'Nina Jansen',   'nina@test.sr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Later nog een account erbij (zonder id: de database geeft zelf het volgende nummer)
INSERT INTO users (name, email, password, role) VALUES
('Tim Tester', 'tim@test.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Iemand admin maken / weer gewone user maken
UPDATE users SET role = 'admin' WHERE email = 'cleo@test.sr';
UPDATE users SET role = 'user'  WHERE email = 'cleo@test.sr';

-- Wachtwoord van 1 account terugzetten naar: password
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'paul@test.sr';


-- ---------------------------------------------------------------------
-- 2. DE LIJST (parent, voor de dropdown)
--    Zelf het id geven: dan weet je welk nummer je bij de items gebruikt.
-- ---------------------------------------------------------------------
INSERT INTO categories (id, name) VALUES
(1, 'Categorie A'),
(2, 'Categorie B'),
(3, 'Categorie C');


-- ---------------------------------------------------------------------
-- 3. HET DING (child)
--    category_id MOET bestaan in categories (anders fout #1452).
--    Zorg voor variatie, zodat je filters en statussen kunt testen:
--    elke status minstens 1 keer, een lege (NULL) waarde, prijs 0.
-- ---------------------------------------------------------------------
INSERT INTO items (id, category_id, name, price, item_date, status) VALUES
(1, 1, 'Item een',   9.99,   '2026-01-15', 'actief'),
(2, 1, 'Item twee',  24.50,  '2026-02-01', 'actief'),
(3, 2, 'Item drie',  5.00,   NULL,         'inactief'),
(4, 2, 'Item vier',  120.00, '2026-03-20', 'uitverkocht'),
(5, 3, 'Item vijf',  49.95,  '2026-04-05', 'actief'),
(6, 3, 'Item zes',   0.00,   NULL,         'actief');

-- Een item in de PRULLENBAK (soft delete): deleted_at krijgt een datum
INSERT INTO items (id, category_id, name, price, status, deleted_at) VALUES
(7, 1, 'Oud item', 1.00, 'inactief', NOW());

-- Datums die altijd kloppen, hoe laat je ook test: rekenen vanaf vandaag
INSERT INTO items (id, category_id, name, price, item_date, status) VALUES
(8, 2, 'Item van vorige week', 15.00, CURDATE() - INTERVAL 7 DAY, 'actief'),
(9, 2, 'Item van volgende maand', 15.00, CURDATE() + INTERVAL 1 MONTH, 'actief');


-- ---------------------------------------------------------------------
-- 4. DE KOPPELTABEL (wie deed wat)
--    user_id en item_id MOETEN bestaan (anders fout #1452).
-- ---------------------------------------------------------------------
INSERT INTO orders (user_id, item_id, created_at) VALUES
(2, 1, NOW() - INTERVAL 3 DAY),     -- Paul, 3 dagen geleden
(2, 5, NOW() - INTERVAL 1 DAY),     -- Paul, gisteren
(3, 2, NOW()),                      -- Cleo, nu
(4, 1, NOW() - INTERVAL 30 DAY);    -- Nina, een maand geleden


-- ---------------------------------------------------------------------
-- 5. LOGBOEK
-- ---------------------------------------------------------------------
INSERT INTO action_log (user_id, action, details, ip_address) VALUES
(1,    'login',        'Dummy data',           '127.0.0.1'),
(2,    'checkout',     'Item #1 besteld',      '127.0.0.1'),
(NULL, 'login_failed', 'E-mail: hacker@x.sr',  '127.0.0.1');   -- NULL mag: onbekende gebruiker


-- ---------------------------------------------------------------------
-- 6. VEEL RIJEN IN 1 KEER (bv. 25 items om teller en sorteren te testen)
--    WITH RECURSIVE telt van 1 tot 25. Elke tel = 1 nieuw item.
--    MOD(n, 3) + 1 geeft afwisselend categorie 1, 2, 3.
-- ---------------------------------------------------------------------
INSERT INTO items (category_id, name, price, status)
WITH RECURSIVE tel (n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1 FROM tel WHERE n < 25
)
SELECT MOD(n, 3) + 1, CONCAT('Test item ', n), n * 2.50, 'actief' FROM tel;


-- ---------------------------------------------------------------------
-- 7. CONTROLE: klopt alles?
-- ---------------------------------------------------------------------
SELECT 'users' AS tabel, COUNT(*) AS aantal FROM users
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'items',      COUNT(*) FROM items
UNION ALL SELECT 'orders',     COUNT(*) FROM orders
UNION ALL SELECT 'action_log', COUNT(*) FROM action_log;

-- Alles aan elkaar (via de koppelingen)
SELECT o.id, u.name AS klant, i.name AS item, c.name AS categorie
FROM orders o
INNER JOIN users u      ON u.id = o.user_id
INNER JOIN items i      ON i.id = o.item_id
INNER JOIN categories c ON c.id = i.category_id;
