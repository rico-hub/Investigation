<?php
// =====================================================================
// config/config.php - instellingen + database verbinding
// Dit is de ENIGE plek waar je de database-gegevens aanpast.
// =====================================================================

define('APP_NAME', 'Dierenasiel Pootjes');      // AANPASSEN: naam van je app

// XAMPP standaard: gebruiker root, GEEN wachtwoord
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dierenasiel');            // AANPASSEN: naam van je database

define('SESSION_TIMEOUT', 1800); // 30 minuten niets gedaan = automatisch uitgelogd
define('MAX_CART', 5);           // maximaal 5 animals in de winkelmand

// Eigen error codes (Notion pagina 6)
define('ERR_FUNCTION', 420);     // een check in een functie faalt (verkeerde invoer, geen rechten, ...)
define('ERR_DATABASE', 410);     // de database geeft een fout (verbinding, query, constraint, ...)

// Zet de automatische exceptions van mysqli UIT.
// Vanaf PHP 8.1 gooit mysqli standaard exceptions; wij willen zelf checken met if/else.
mysqli_report(MYSQLI_REPORT_OFF);

// MySQLi Object-Oriented: new mysqli(...)  (NIET mysqli_connect(...) = procedural)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Database Error-code: 410 -> stop direct, zonder database kan de site niets
    die('<p style="color:#b00020;font-family:sans-serif;">Error-code: ' . ERR_DATABASE
        . ' - Database verbinding mislukt: ' . htmlspecialchars($conn->connect_error) . '</p>');
}

// utf8mb4 = alle tekens goed opslaan (é, ë, emoji)
$conn->set_charset('utf8mb4');

// Strict mode: te lange tekst / foute ENUM / '' in een INT kolom = FOUT (410)
// i.p.v. stil afkappen. XAMPP heeft dit standaard UIT staan.
if (!$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")) {
    die('Error-code: ' . ERR_DATABASE . ' - sql_mode instellen mislukt: ' . htmlspecialchars($conn->error));
}
