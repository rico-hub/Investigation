# Simple PHP Assessment - 4 werkende systemen

Alles in deze map: **HTML, CSS, PHP (MySQLi Object-Oriented, geen PDO), MySQL**.
Simpele `if / elseif / else`, geen ternary (`? :`) en geen `??`.

De uitleg en notes staan niet in deze repo; die worden apart bijgehouden.

## Inhoud

| Map | Wat |
|---|---|
| [bibliotheek/](bibliotheek/) | **Referentie-systeem** (meest uitgebreid commentaar). Boeken, categorieën, leningen, winkelmand = boeken lenen, prullenbak (soft + hard delete), embedded voorbeeld (info.php) |
| [klachtensysteem/](klachtensysteem/) | Klachten, reacties (ON DELETE CASCADE), status-knoppen met tellingen, werklijst (winkelmand) voor bulk-update door admin |
| [sportsysteem/](sportsysteem/) | Lessen, inschrijvingen (koppeltabel + UNIQUE), statusfilter met HAVING, winkelmand = inschrijven met `FOR UPDATE` lock |
| [schoolsysteem/](schoolsysteem/) | Klassen, vakken, cijfers (CHECK 1-10), rapport met gewogen gemiddelde, selectie leerlingen = 1 toets voor hele klas invoeren |
| [kit/](kit/) | **Universele starter** (puzzel). Complete werkende app met de neutrale naam `items`: kopieer de map, zoek/vervang item door jouw woord en pas de regels met `AANPASSEN` aan. Bevat `database.sql` (5 gekoppelde tabellen, foreign keys, constraints) en `database_fk.sql` (foreign keys met de hand maken, weghalen, tijdelijk uitzetten). In `kit/sql/` staan 6 complete, geteste scenario-databases (garage, sportschool, hotel, klachten, webshop, school). In `kit/extra/` staan kant-en-klare extra stukken om over een kopie van de kit te zetten (1 datum kiezen, 2 datums van-tot, tweede lijst, geen lijst), elk met een `LEESMIJ.txt`. `css/style.css`, `includes/header.php` en `includes/footer.php` zijn in ALLE mappen exact hetzelfde; alleen `config/site.php` (menu, logo) verschilt per app. |
| [Bricks/](Bricks/) | **Bouwstenen** (Notion pagina 7) live op het scherm: 9 soorten dropdowns, radio, checkboxes, datum, knoppen, waarden netjes tonen, status wijzigen in de tabel, en 1 formulier dat alles echt opslaat. Elk blok toont de code eronder. Database: `Bricks/database.sql`, open http://localhost/Investigation/Bricks/ |
| [tools/app_checker.php](tools/app_checker.php) | **App checker** (offline, zonder AI): controleert een app op PHP-fouten, assessment-regels, niet-hernoemde kit-woorden, kapotte css/js, ontbrekende bestanden en pagina's, onbekende functies, telfouten bij INSERT/UPDATE, de database (naam, tabellen, kolommen, elke SQL via prepare), formulier-velden en statussen. Knoppen om te repareren (met reservekopie in `tools/backups/`): css/js terugzetten, databasenaam gelijk zetten, importeren en hernoemen. Open http://localhost/Investigation/tools/app_checker.php |
| [tools/hash_maker_reader.php](tools/hash_maker_reader.php) | Wachtwoord-hash maken voor je dummy data, en een hash uitlezen (algoritme, cost, salt) + de tekst erachter vinden door te raden (password_verify) |

## Snel starten (XAMPP)

1. `C:\xampp\htdocs\Investigation` is een **junction** (snelkoppeling op mapniveau) naar `S:\Investigation`. Je werkt dus in `S:\Investigation` en Apache ziet dezelfde bestanden; niets kopiëren.
   Opnieuw aanmaken (cmd als nodig): `mklink /J "C:\xampp\htdocs\Investigation" "S:\Investigation"`.
   Let op: verwijder de junction met `rmdir "C:\xampp\htdocs\Investigation"` (zonder `/s`!), anders wis je de echte bestanden.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Ga naar http://localhost/phpmyadmin -> tabblad **Importeren** -> kies `bibliotheek/database.sql` -> **Starten**.
   (Herhaal voor de andere systemen. Elk systeem heeft z'n eigen database.)
4. Open http://localhost/Investigation/bibliotheek/

**Test-accounts** (in alle 4 systemen): `admin@test.sr` (admin) en `paul@test.sr` (user), wachtwoord: `password`.

> Getest op XAMPP (PHP 8.4.27, MariaDB 10.11.16): alle 4 systemen draaien zonder warnings of deprecations (103 end-to-end checks).
