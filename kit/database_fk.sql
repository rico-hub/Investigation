-- =====================================================================
-- KIT - database_fk.sql   FOREIGN KEYS: met de hand koppelen, weghalen,
--                         tijdelijk uitzetten (bypass) en fouten oplossen.
-- NIET in 1 keer importeren! Dit is een receptenboek:
-- kopieer het stukje dat je nodig hebt naar phpMyAdmin > tabblad SQL.
-- Voorbeeld: child = items (kolom category_id), parent = categories (kolom id)
-- =====================================================================


-- ---------------------------------------------------------------------
-- RECEPT 1: 3 gekoppelde tabellen ZONDER foreign keys maken,
--           en de koppeling er DAARNA met de hand op zetten.
-- (Handig als CREATE TABLE met FOREIGN KEY steeds een fout geeft.)
-- ---------------------------------------------------------------------

-- Stap 1: alleen de tabellen (volgorde maakt nu niet uit)
CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE items (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id  INT UNSIGNED NOT NULL,     -- zelfde type als categories.id
    name         VARCHAR(150) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id  INT UNSIGNED NOT NULL,         -- zelfde type als users.id
    item_id  INT UNSIGNED NOT NULL,         -- zelfde type als items.id
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- Stap 2: de koppeling met de hand toevoegen (1 ALTER TABLE per foreign key)
ALTER TABLE items
    ADD CONSTRAINT fk_items_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_item
    FOREIGN KEY (item_id) REFERENCES items (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

ALTER TABLE orders
    ADD CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

-- Invullen voor je eigen tabellen:
--   ALTER TABLE <child>
--       ADD CONSTRAINT fk_<child>_<parent>
--       FOREIGN KEY (<kolom in child>) REFERENCES <parent> (<kolom in parent, meestal id>)
--       ON UPDATE CASCADE
--       ON DELETE RESTRICT;      -- of CASCADE, of SET NULL (zie RECEPT 6)


-- ---------------------------------------------------------------------
-- RECEPT 2: hetzelfde met de MUIS in phpMyAdmin (zonder SQL)
-- ---------------------------------------------------------------------
--   1. Klik de CHILD tabel aan (items) > tabblad "Structuur"
--   2. Klik bovenaan op "Relatieoverzicht" (Engels: "Relation view")
--   3. Bij "Foreign key constraints":
--        Constraint-naam : fk_items_category
--        Kolom           : category_id
--        Database        : jouw database
--        Tabel           : categories
--        Kolom           : id
--        ON DELETE       : RESTRICT      ON UPDATE : CASCADE
--   4. Klik "Opslaan"
--   Zie je "Relatieoverzicht" niet? Dan is de tabel geen InnoDB:
--        ALTER TABLE items ENGINE=InnoDB;


-- ---------------------------------------------------------------------
-- RECEPT 3: BYPASS = de foreign key controle TIJDELIJK uitzetten
-- Gebruik dit als je moet importeren / legen / droppen in de "verkeerde" volgorde.
-- ---------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;     -- controle UIT (alleen voor dit SQL-venster / deze import)

-- ... hier je INSERT / DROP TABLE / TRUNCATE / import ...
-- bv.  TRUNCATE TABLE items;
--      DROP TABLE categories;

SET FOREIGN_KEY_CHECKS = 1;     -- controle weer AAN. NOOIT VERGETEN.

-- In phpMyAdmin kan het ook met een vinkje: onder het SQL-venster en bij Importeren
-- staat "Controle op vreemde sleutels inschakelen" (Enable foreign key checks). Vinkje UIT = bypass.
--
-- LET OP: als je de controle weer aanzet, kijkt MySQL NIET terug naar wat je net deed.
-- Er kunnen dus "wees-rijen" in staan (items met een categorie die niet bestaat).
-- Controleer dat ALTIJD met RECEPT 4.


-- ---------------------------------------------------------------------
-- RECEPT 4: wees-rijen zoeken (child rijen zonder bestaande parent)
-- Geeft dit 0 rijen? Dan klopt de koppeling.
-- ---------------------------------------------------------------------
SELECT i.id, i.category_id
FROM items i
LEFT JOIN categories c ON c.id = i.category_id
WHERE c.id IS NULL;

-- Wees-rijen repareren: geef ze een categorie die WEL bestaat (hier id 1)...
--   UPDATE items SET category_id = 1 WHERE category_id NOT IN (SELECT id FROM categories);
-- ...of gooi ze weg:
--   DELETE FROM items WHERE category_id NOT IN (SELECT id FROM categories);


-- ---------------------------------------------------------------------
-- RECEPT 5: een foreign key bekijken, weghalen en opnieuw maken
-- ---------------------------------------------------------------------
-- Welke foreign keys heeft deze tabel (en hoe heten ze)?
SHOW CREATE TABLE items;

-- Alle foreign keys van de hele database in 1 lijst:
SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Weghalen (de naam komt uit SHOW CREATE TABLE):
ALTER TABLE items DROP FOREIGN KEY fk_items_category;

-- Opnieuw maken = RECEPT 1, stap 2.


-- ---------------------------------------------------------------------
-- RECEPT 6: wat doet ON DELETE?
-- ---------------------------------------------------------------------
--   RESTRICT  parent met children kan NIET verwijderd worden (fout 1451). Veiligste keuze.
--   CASCADE   parent weg = alle children OOK weg (bv. klacht weg = reacties weg).
--   SET NULL  parent weg = kolom in child wordt NULL (kolom moet dan NULL toestaan).


-- ---------------------------------------------------------------------
-- RECEPT 7: koppelen ZONDER foreign key (het kan, maar zonder bewaking)
-- ---------------------------------------------------------------------
-- Een JOIN werkt ook als er GEEN foreign key bestaat. De foreign key is alleen de
-- "bewaker" die foute id's tegenhoudt; de JOIN is wat de tabellen echt aan elkaar plakt.
SELECT i.id, i.name, c.name AS categorie
FROM items i
INNER JOIN categories c ON c.id = i.category_id;
-- Voor het assessment zijn foreign keys verplicht: gebruik dit alleen om te testen.


-- ---------------------------------------------------------------------
-- FOUTEN bij foreign keys
-- ---------------------------------------------------------------------
-- #1005 / errno 150 "Foreign key constraint is incorrectly formed"
--     -> types zijn niet gelijk. INT UNSIGNED en INT is NIET hetzelfde.
--        Check met:  SHOW CREATE TABLE categories;  SHOW CREATE TABLE items;
--     -> de parent kolom is geen PRIMARY KEY (of heeft geen index)
--     -> 1 van de 2 tabellen is geen InnoDB:  ALTER TABLE items ENGINE=InnoDB;
--     -> ON DELETE SET NULL, maar de kolom is NOT NULL
-- #1215 "Cannot add foreign key constraint"            -> zelfde oorzaken als #1005
-- #1146 / "Table doesn't exist" bij CREATE TABLE       -> parent bestaat nog niet: eerst de parent maken
-- #1452 "Cannot add or update a child row"
--     -> bij INSERT/UPDATE: het id bestaat niet in de parent (category_id 999)
--     -> bij ALTER TABLE ADD CONSTRAINT: er staan al wees-rijen in -> RECEPT 4
-- #1451 "Cannot delete or update a parent row"
--     -> er hangen nog children aan. Eerst de children weg/verplaatsen, of soft delete gebruiken.
-- #1826 / #1022 "Duplicate foreign key constraint name"
--     -> de naam bestaat al in deze database. Kies een andere naam (fk_items_category2).
-- #1091 "Can't DROP FOREIGN KEY; check that it exists" -> verkeerde naam: SHOW CREATE TABLE items;
-- #3730 / #1217 bij DROP TABLE                         -> eerst de child droppen, of RECEPT 3 (bypass)
