<?php
// =====================================================================
// tools/functions/hash_functions.php - functies voor hash_reader.php
//
// LET OP: een hash kun je NIET terugrekenen naar het wachtwoord.
// password_hash() is "one-way". Wat je WEL kunt:
//   1. uitlezen WAT er in de hash staat (algoritme, cost, salt)
//   2. testen of een wachtwoord bij de hash hoort (password_verify)
// =====================================================================

define('ERR_FUNCTION', 420);
define('ERR_DATABASE', 410);

// Alleen deze databases mogen gelezen worden (whitelist: databasenaam kun je niet binden met ?)
function hash_allowed_databases()
{
    return array('bibliotheek', 'klachtensysteem', 'sportsysteem', 'schoolsysteem');
}

function hash_e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function hash_post($key)
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return $_POST[$key];
    }
    return '';
}

// ---------------------------------------------------------------------
// Een hash uit elkaar halen.
// Bcrypt opbouw (60 tekens):
//   $2y$10$abcdefghijklmnopqrstuvWXYZ0123456789abcdefghijklmno
//   |  |  |                     |
//   |  |  salt (22 tekens)      hash (31 tekens)
//   |  cost (10 = 2^10 = 1024 rondes)
//   algoritme (2y = bcrypt)
// Geeft altijd een array terug met 'status': ok / plain / unknown / empty
// ---------------------------------------------------------------------
function hash_analyse($hash)
{
    $result = array(
        'status'  => 'unknown',
        'message' => '',
        'algo'    => '-',
        'cost'    => '-',
        'salt'    => '-',
        'checksum'=> '-',
        'length'  => strlen($hash),
        'rehash'  => false,
    );

    if ($hash === '') {
        $result['status'] = 'empty';
        $result['message'] = 'Leeg: er staat geen wachtwoord in de database.';
        return $result;
    }

    // password_get_info() herkent hashes van password_hash()
    $info = password_get_info($hash);

    if ($info['algoName'] === 'unknown') {
        // Geen geldige hash. Lijkt het op een gewoon wachtwoord?
        if (strlen($hash) < 30 && strpos($hash, '$') === false) {
            $result['status'] = 'plain';
            $result['message'] = 'Dit is GEEN hash maar (waarschijnlijk) een plain-text wachtwoord. Inloggen werkt niet: password_verify() verwacht een hash.';
        } elseif (strlen($hash) === 32 && ctype_xdigit($hash)) {
            $result['status'] = 'unknown';
            $result['algo'] = 'md5 (?)';
            $result['message'] = 'Lijkt op md5. Onveilig en werkt niet met password_verify(). Maak een nieuwe hash.';
        } elseif (strlen($hash) === 40 && ctype_xdigit($hash)) {
            $result['status'] = 'unknown';
            $result['algo'] = 'sha1 (?)';
            $result['message'] = 'Lijkt op sha1. Onveilig en werkt niet met password_verify(). Maak een nieuwe hash.';
        } elseif (strpos($hash, '$2y$') === 0 && strlen($hash) < 60) {
            $result['status'] = 'unknown';
            $result['algo'] = 'bcrypt (afgekapt!)';
            $result['message'] = 'Bcrypt hash is maar ' . strlen($hash) . ' tekens (moet 60 zijn). De kolom is te kort: gebruik VARCHAR(255).';
        } else {
            $result['message'] = 'Onbekend formaat. Geen hash van password_hash().';
        }
        return $result;
    }

    $result['status'] = 'ok';
    $result['algo'] = $info['algoName'];

    if ($info['algoName'] === 'bcrypt') {
        // $2y$10$ + 22 tekens salt + 31 tekens hash
        $parts = explode('$', $hash);           // array('', '2y', '10', 'salt+hash')
        $result['cost'] = $info['options']['cost'];
        if (isset($parts[3])) {
            $result['salt'] = substr($parts[3], 0, 22);
            $result['checksum'] = substr($parts[3], 22);
        }
        $result['message'] = 'Geldige bcrypt hash (variant $' . $parts[1] . '$).';
    } else {
        // argon2i / argon2id: opties staan in de hash zelf
        $options = array();
        foreach ($info['options'] as $key => $value) {
            $options[] = $key . '=' . $value;
        }
        $result['cost'] = implode(', ', $options);
        $result['message'] = 'Geldige ' . $info['algoName'] . ' hash.';
    }

    // Moet de hash vernieuwd worden? (bv. na een PHP-update met een sterkere standaard)
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $result['rehash'] = true;
    }

    return $result;
}

// Hoort dit wachtwoord bij deze hash? Geeft 'match', 'nomatch' of 'skip' (niets ingevuld)
function hash_check_password($password, $hash)
{
    if ($password === '') {
        return 'skip';
    }
    if (password_verify($password, $hash)) {
        return 'match';
    }
    return 'nomatch';
}

// ---------------------------------------------------------------------
// Alle users + hashes uit een database lezen (MySQLi OOP)
// Geeft array met rijen, of een string met de foutmelding (Error-code)
// ---------------------------------------------------------------------
function hash_read_users($database)
{
    if (!in_array($database, hash_allowed_databases(), true)) {
        return 'Error-code: ' . ERR_FUNCTION . ' - Kies een database uit de lijst.';
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli('localhost', 'root', '', $database);

    if ($conn->connect_error) {
        return 'Error-code: ' . ERR_DATABASE . ' - Verbinding met "' . $database . '" mislukt: ' . $conn->connect_error
            . ' (staat MySQL aan in XAMPP? Is database.sql geïmporteerd?)';
    }
    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare('SELECT id, name, email, role, password FROM users ORDER BY id');
    if (!$stmt) {
        $error = $conn->error;
        $conn->close();
        return 'Error-code: ' . ERR_DATABASE . ' - Query mislukt: ' . $error;
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return 'Error-code: ' . ERR_DATABASE . ' - Query mislukt: ' . $error;
    }

    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $rows;
}

// Lange hash inkorten voor in de tabel: $2y$10$92IXUNpk...uheWG/igi
function hash_short($hash)
{
    if (strlen($hash) <= 24) {
        return $hash;
    }
    return substr($hash, 0, 14) . '...' . substr($hash, -8);
}
