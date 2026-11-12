<?php
define('APP_NAME', 'Garage Asraf');

// XAMPP standaard is: gebruiker root, GEEN wachtwoord
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'garage');

define('SESSION_TIMEOUT', 1800); // 30 minuten niets gedaan = automatisch uitgelogd
define('WERKPLAATS_PLEKKEN', 4); // max 4 auto's per dag in de werkplaats

define('ERR_FUNCTION', 420);     // een check in een functie faalt (verkeerde invoer, geen rechten, ...)
define('ERR_DATABASE', 410);     // de database geeft een fout (verbinding, query, constraint, ...)

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Database Error-code: 410 -> stop direct, zonder database kan de site niets
    die('<p style="color:#b00020;font-family:sans-serif;">Error-code: ' . ERR_DATABASE
        . ' - Database verbinding mislukt: ' . htmlspecialchars($conn->connect_error) . '</p>');
}

$conn->set_charset('utf8mb4');

if (!$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")) {
    die('Error-code: ' . ERR_DATABASE . ' - sql_mode instellen mislukt: ' . htmlspecialchars($conn->error));
}
