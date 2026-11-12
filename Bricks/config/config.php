<?php
// =====================================================================
// config/config.php - instellingen + database verbinding
// =====================================================================

define('APP_NAME', 'Bricks');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // XAMPP: leeg
define('DB_NAME', 'bricks');

// Eigen error codes
define('ERR_FUNCTION', 420);      // een check in een functie faalt
define('ERR_DATABASE', 410);      // de database geeft een fout

// Zelf checken met if/else (anders gooit PHP 8 een fatal error)
mysqli_report(MYSQLI_REPORT_OFF);

// MySQLi Object-Oriented
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<p style="color:#b00020;font-family:sans-serif;">Error-code: ' . ERR_DATABASE
        . ' - Database verbinding mislukt: ' . htmlspecialchars($conn->connect_error)
        . '<br>Tip: importeer <b>Bricks/database.sql</b> in phpMyAdmin en start MySQL in XAMPP.</p>');
}

$conn->set_charset('utf8mb4');

if (!$conn->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'")) {
    die('Error-code: ' . ERR_DATABASE . ' - sql_mode instellen mislukt: ' . htmlspecialchars($conn->error));
}
