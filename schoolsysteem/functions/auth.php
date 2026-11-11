<?php
// =====================================================================
// functions/auth.php - registreren, inloggen, rollen, sessie
// Rollen: 'admin' en 'user'
// =====================================================================

function is_logged_in()
{
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    return false;
}

function is_admin()
{
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    return false;
}

// Het id van de ingelogde gebruiker. ALTIJD uit de sessie, NOOIT uit een formulier.
function current_user_id()
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    return null;
}

// Zet bovenaan een pagina die alleen voor ingelogde gebruikers is.
function require_login()
{
    if (!is_logged_in()) {
        set_flash('error', 'Log eerst in om deze pagina te bekijken.');
        redirect('login.php');
    }
}

// Zet bovenaan een pagina die alleen voor admins is.
function require_admin()
{
    require_login();
    if (!is_admin()) {
        function_error('Geen toegang. Deze pagina is alleen voor admins.');
        redirect('index.php');
    }
}

// ---------------------------------------------------------------------
// Registreren
// Geeft het nieuwe user id terug, of false.
// Schoolsysteem: een nieuwe leerling kiest ook zijn/haar klas (dropdown).
// ---------------------------------------------------------------------
function register_user($conn, $name, $email, $password, $password_repeat, $class_id)
{
    if (!is_valid_id($class_id) || !class_exists_db($conn, (int) $class_id)) {
        return function_error('Kies je klas.');
    }

    // 1. Checks (Error-code 420)
    if ($name === '' || $email === '' || $password === '') {
        return function_error('Vul alle velden in.');
    }
    if (strlen($name) > 100) {
        return function_error('Naam mag maximaal 100 tekens zijn.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return function_error('Dit is geen geldig e-mailadres.');
    }
    if (strlen($password) < 8) {
        return function_error('Wachtwoord moet minimaal 8 tekens zijn.');
    }
    if ($password !== $password_repeat) {
        return function_error('De wachtwoorden komen niet overeen.');
    }

    // 2. Bestaat dit e-mailadres al?
    $existing = db_select_one($conn, 'SELECT id FROM users WHERE email = ?', 's', array($email));
    if ($existing === false) {
        return false; // database fout (410 al gezet)
    }
    if ($existing !== null) {
        return function_error('Dit e-mailadres is al geregistreerd.');
    }

    // 3. Wachtwoord hashen en opslaan. Rol is ALTIJD 'user' (nooit uit het formulier halen!)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user';

    return db_insert(
        $conn,
        'INSERT INTO users (class_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)',
        'issss',
        array((int) $class_id, $name, $email, $hash, $role)
    );
}

// ---------------------------------------------------------------------
// Inloggen. Geeft true of false.
// ---------------------------------------------------------------------
function login_user($conn, $email, $password)
{
    if ($email === '' || $password === '') {
        return function_error('Vul je e-mail en wachtwoord in.');
    }

    $user = db_select_one(
        $conn,
        'SELECT id, name, password, role FROM users WHERE email = ?',
        's',
        array($email)
    );

    if ($user === false) {
        return false; // database fout
    }

    // Zelfde melding bij "bestaat niet" en "verkeerd wachtwoord" = hacker weet niet wat fout is
    if ($user === null || !password_verify($password, $user['password'])) {
        log_action($conn, null, 'login_failed', 'E-mail: ' . $email);
        return function_error('E-mail of wachtwoord is onjuist.');
    }

    // Nieuwe sessie-id na inloggen (tegen session fixation)
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    log_action($conn, $_SESSION['user_id'], 'login', '');
    return true;
}

// ---------------------------------------------------------------------
// Uitloggen = sessie helemaal leegmaken (ook de winkelmand)
// ---------------------------------------------------------------------
function logout_user()
{
    $_SESSION = array(); // alle sessie-variabelen weg

    // Sessie-cookie in de browser ook verwijderen
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy(); // sessie-bestand op de server weg
}

// ---------------------------------------------------------------------
// Actieve sessie: na SESSION_TIMEOUT seconden niets doen -> uitloggen.
// Wordt op elke pagina aangeroepen (in includes/init.php).
// ---------------------------------------------------------------------
function check_session_timeout()
{
    if (!is_logged_in()) {
        return;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logout_user();
        // Sessie is weg, dus geen flash mogelijk: melding via de URL
        redirect('login.php?expired=1');
    }

    $_SESSION['last_activity'] = time(); // gebruiker is actief: timer opnieuw
}
