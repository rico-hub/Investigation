# Simple PHP Assessment - Notes + 4 werkende systemen

Alles in deze map: **HTML, CSS, PHP (MySQLi Object-Oriented, geen PDO), MySQL**.
Simpele `if / elseif / else`, geen ternary (`? :`) en geen `??`.

## Inhoud

| Map | Wat |
|---|---|
| [bibliotheek/](bibliotheek/) | **Referentie-systeem** (meest uitgebreid commentaar). Boeken, categorieën, leningen, winkelmand = boeken lenen, prullenbak (soft + hard delete), embedded voorbeeld (info.php) |
| [klachtensysteem/](klachtensysteem/) | Klachten, reacties (ON DELETE CASCADE), status-knoppen met tellingen, werklijst (winkelmand) voor bulk-update door admin |
| [sportsysteem/](sportsysteem/) | Lessen, inschrijvingen (koppeltabel + UNIQUE), statusfilter met HAVING, winkelmand = inschrijven met `FOR UPDATE` lock |
| [schoolsysteem/](schoolsysteem/) | Klassen, vakken, cijfers (CHECK 1-10), rapport met gewogen gemiddelde, selectie leerlingen = 1 toets voor hele klas invoeren |
| [notes/](notes/) | Uitleg: hoe, waarom, troubleshooting, antwoorden op je vragen |
| [tools/make_hash.php](tools/make_hash.php) | Wachtwoord-hash maken voor je dummy data |
| [tools/hash_reader.php](tools/hash_reader.php) | Hash uitlezen (algoritme, cost, salt), wachtwoord testen tegen een hash, en alle hashes in een database controleren (vindt plain-text / afgekapte wachtwoorden) |

## Notes (lees in deze volgorde)

1. [01 - Setup & mappenstructuur](notes/01-setup-en-structuur.md) - XAMPP, waarom functions apart, request flow
2. [02 - Database, PK/FK & constraints](notes/02-database-constraints.md) - **vraag: condities voor constraints**
3. [03 - MySQLi OOP](notes/03-mysqli-oop.md) - prepared statements uitgeschreven, **vraag: wanneer ben je verplicht PDO te gebruiken?**
4. [04 - CRUD](notes/04-crud.md) - Create, Read, Update, Delete stap voor stap
5. [05 - Auth, sessies, wachtwoorden](notes/05-auth-sessies-wachtwoorden.md) - admin/user, hashing, sessie actief + wissen
6. [06 - Tabel, filters, zoeken, dropdown, count, knoppen, embedded](notes/06-tabel-filters-zoeken.md)
7. [07 - Winkelmand (add to cart)](notes/07-winkelmand.md)
8. [08 - Checks, ends & error codes 420/410](notes/08-checks-en-error-codes.md)
9. [09 - Acties loggen bij meerdere gebruikers](notes/09-acties-loggen-meerdere-gebruikers.md) - **vraag: hoe registreer je veilig een actie?**
10. [10 - Soft delete vs Delete](notes/10-soft-delete-vs-delete.md) - **vraag**
11. [11 - CSS](notes/11-css.md)
12. [12 - Troubleshooting](notes/12-troubleshooting.md) - foutmelding -> oorzaak -> oplossing
13. [13 - De 4 systemen (ERD + wat zit waar)](notes/13-systemen-overzicht.md)
14. [14 - Notities: bouwvolgorde op de assessment](notes/14-notities.md)

## Snel starten (XAMPP)

1. `C:\xampp\htdocs\Investigation` is een **junction** (snelkoppeling op mapniveau) naar `S:\Investigation`. Je werkt dus in `S:\Investigation` en Apache ziet dezelfde bestanden; niets kopiëren.
   Opnieuw aanmaken (cmd als nodig): `mklink /J "C:\xampp\htdocs\Investigation" "S:\Investigation"`.
   Let op: verwijder de junction met `rmdir "C:\xampp\htdocs\Investigation"` (zonder `/s`!), anders wis je de echte bestanden.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Ga naar http://localhost/phpmyadmin -> tabblad **Importeren** -> kies `bibliotheek/database.sql` -> **Starten**.
   (Herhaal voor de andere systemen. Elk systeem heeft z'n eigen database.)
4. Open http://localhost/Investigation/bibliotheek/

**Test-accounts** (in alle 4 systemen): `admin@test.nl` (admin) en `bram@test.nl` (user), wachtwoord: `password`.

## Waar zit elke eis?

| Eis | Waar (bibliotheek als voorbeeld) |
|---|---|
| Primary key / Foreign keys / constraints | `database.sql` |
| Create / Read / Update / Delete | `book_create.php`, `index.php`, `book_edit.php`, `book_delete.php` + `functions/books.php` |
| Dropdown (waarde behouden) | `selected()` in `functions/helpers.php`, gebruikt in `index.php` en `includes/book_form.php` |
| Filters + wissen | filterbalk in `index.php`, knop "Filters wissen" = link naar `index.php` zonder `?` |
| Kolomfilters | 2e rij in `<thead>` met `form="filters"` |
| Tabel + eigen header + ID kolom | `index.php` `<thead>`, `sort_link()` |
| StatusFilter | `status` dropdown; klachtensysteem: status-knoppen met aantallen |
| Searchbar | `search` + `LIKE ?` over meerdere kolommen |
| TotalCount | `count($books)` van `books_count_all()` |
| Embedded | `info.php` (PHP in HTML + iframe) en `includes/*.php` |
| Buttons | `.btn` classes in CSS, POST-formulieren voor acties |
| Sessie actief + wissen | `check_session_timeout()`, `logout_user()`, `cart_clear()` na afrekenen |
| Proper checks & ends | elke functie: checks -> `return function_error()`, `redirect()` = `exit` |
| Admin / normale users | `require_admin()`, `is_admin()` in `functions/auth.php` |
| Wachtwoorden gehasht | `password_hash()` / `password_verify()` |
| Add to cart | `functions/cart.php`, `cart.php` |
| Table snippets om data toe te voegen | `includes/book_form.php` (formulier in tabel) en schoolsysteem `cart.php` (cijfer per rij) |
| Error-code 420 / 410 | `function_error()` / `database_error()` in `functions/helpers.php` |
| SQL dummy data | onderaan elke `database.sql` |

> Getest op XAMPP (PHP 8.4.27, MariaDB 10.11.16): alle 4 systemen draaien zonder warnings of deprecations (103 end-to-end checks). Werkt er toch iets niet? Zie [notes/12-troubleshooting.md](notes/12-troubleshooting.md).
