<?php
// =====================================================================
// config/config.php - instellingen + database verbinding
// =====================================================================

define('APP_NAME', 'Sportsysteem');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sportsysteem');

define('SESSION_TIMEOUT', 1800);
define('MAX_CART', 5); // maximaal 5 lessen tegelijk in de winkelmand

define('ERR_FUNCTION', 420);
define('ERR_DATABASE', 410);

mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<p style="color:#b00020;font-family:sans-serif;">Error-code: ' . ERR_DATABASE
        . ' - Database verbinding mislukt: ' . htmlspecialchars($conn->connect_error) . '</p>');
}

$conn->set_charset('utf8mb4');

// Strict mode: te lange tekst / foute ENUM / '' in een INT kolom = FOUT (410)
// i.p.v. stil afkappen. XAMPP heeft dit standaard UIT staan.
if (!$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")) {
    die('Error-code: ' . ERR_DATABASE . ' - sql_mode instellen mislukt: ' . htmlspecialchars($conn->error));
}
