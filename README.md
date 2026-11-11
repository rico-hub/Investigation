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
| [tools/make_hash.php](tools/make_hash.php) | Wachtwoord-hash maken voor je dummy data |
| [tools/hash_reader.php](tools/hash_reader.php) | Hash uitlezen (algoritme, cost, salt), wachtwoord testen tegen een hash, en alle hashes in een database controleren (vindt plain-text / afgekapte wachtwoorden) |

## Snel starten (XAMPP)

1. `C:\xampp\htdocs\Investigation` is een **junction** (snelkoppeling op mapniveau) naar `S:\Investigation`. Je werkt dus in `S:\Investigation` en Apache ziet dezelfde bestanden; niets kopiëren.
   Opnieuw aanmaken (cmd als nodig): `mklink /J "C:\xampp\htdocs\Investigation" "S:\Investigation"`.
   Let op: verwijder de junction met `rmdir "C:\xampp\htdocs\Investigation"` (zonder `/s`!), anders wis je de echte bestanden.
2. Start **Apache** en **MySQL** in het XAMPP Control Panel.
3. Ga naar http://localhost/phpmyadmin -> tabblad **Importeren** -> kies `bibliotheek/database.sql` -> **Starten**.
   (Herhaal voor de andere systemen. Elk systeem heeft z'n eigen database.)
4. Open http://localhost/Investigation/bibliotheek/

**Test-accounts** (in alle 4 systemen): `admin@test.nl` (admin) en `bram@test.nl` (user), wachtwoord: `password`.

> Getest op XAMPP (PHP 8.4.27, MariaDB 10.11.16): alle 4 systemen draaien zonder warnings of deprecations (103 end-to-end checks).
