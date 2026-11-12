<?php
// =====================================================================
// functions/helpers.php - kleine algemene functies
// =====================================================================

// Veilig tekst tonen in HTML (voorkomt XSS). Gebruik dit bij ELKE echo van data.
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Doorsturen naar een andere pagina. ALTIJD exit erna, anders draait de rest van de pagina door.
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------
// Flash messages: een melding die 1 keer getoond wordt (ook na een redirect)
// ---------------------------------------------------------------------
function set_flash($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function show_flash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="flash flash-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
        unset($_SESSION['flash']); // na tonen weg uit de sessie
    }
}

// ---------------------------------------------------------------------
// Error codes
// ---------------------------------------------------------------------

// Error-code 420: een check in een functie is mislukt.
// Gebruik: return function_error('Titel is verplicht.');
function function_error($message)
{
    set_flash('error', 'Error-code: ' . ERR_FUNCTION . ' - ' . $message);
    return false;
}

// Error-code 410: de database gaf een fout.
// $detail = de technische melding ($conn->error of $stmt->error)
function database_error($message, $detail)
{
    error_log('[410] ' . $message . ' | ' . $detail); // komt in C:\xampp\php\logs\php_error_log
    set_flash('error', 'Error-code: ' . ERR_DATABASE . ' - ' . $message . ' (' . $detail . ')');
    return false;
}

// ---------------------------------------------------------------------
// Invoer ophalen (altijd via deze functies, nooit direct $_GET['x'])
// ---------------------------------------------------------------------

// Waarde uit de URL (?search=abc). Bestaat hij niet: lege string.
function get_param($key)
{
    if (isset($_GET[$key]) && is_string($_GET[$key])) {
        return trim($_GET[$key]);
    }
    return '';
}

// Waarde uit een formulier (method="post").
function post_param($key)
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return trim($_POST[$key]);
    }
    return '';
}

// Wachtwoorden NIET trimmen (spaties horen bij het wachtwoord).
function post_raw($key)
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return $_POST[$key];
    }
    return '';
}

// Is dit een geldig id? (alleen cijfers en groter dan 0)
function is_valid_id($value)
{
    $value = (string) $value;
    if ($value === '' || !ctype_digit($value)) {
        return false;
    }
    if ((int) $value <= 0) {
        return false;
    }
    return true;
}

// De huidige querystring (alles na de ?), bv. "search=abc&status=open".
// $_SERVER['QUERY_STRING'] bestaat niet altijd (lege ?) -> eerst isset checken.
function current_query()
{
    if (isset($_SERVER['QUERY_STRING'])) {
        return $_SERVER['QUERY_STRING'];
    }
    return '';
}

// Is dit request een formulier-verzending?
function is_post()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return true;
    }
    return false;
}

// ---------------------------------------------------------------------
// Formulier helpers (waarden behouden na verzenden)
// ---------------------------------------------------------------------

// Dropdown: zet 'selected' op de gekozen optie.
// Gebruik in HTML:  echo selected($filters['status'], 'open')  binnen de <option> tag
// (LET OP: schrijf nooit  ? >  zonder spatie in een // comment: dat sluit PHP af!)
function selected($current, $value)
{
    if ((string) $current === (string) $value) {
        return 'selected';
    }
    return '';
}

// Checkbox/radio: zet 'checked'.
function checked($current, $value)
{
    if ((string) $current === (string) $value) {
        return 'checked';
    }
    return '';
}

// Kolomkop waarop je kunt klikken om te sorteren. Houdt alle andere filters vast.
function sort_link($label, $column, $filters)
{
    $direction = 'asc';
    $arrow = '';

    if ($filters['sort'] === $column) {
        if ($filters['dir'] === 'desc') {
            $arrow = ' &#9660;'; // pijl omlaag
        } else {
            $direction = 'desc'; // nog een keer klikken = andersom
            $arrow = ' &#9650;'; // pijl omhoog
        }
    }

    $params = $filters;
    $params['sort'] = $column;
    $params['dir'] = $direction;

    return '<a href="?' . e(http_build_query($params)) . '">' . e($label) . $arrow . '</a>';
}

// Is dit een echte datum in formaat jjjj-mm-dd? (zo stuurt <input type="date"> hem)
// 2026-02-31 bestaat niet -> false
function is_valid_date($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d === false) {
        return false;
    }
    if ($d->format('Y-m-d') !== $date) {
        return false;
    }
    return true;
}

// Is dit een tijd in formaat uu:mm? (zo stuurt <input type="time"> hem)
function is_valid_time($time)
{
    $t = DateTime::createFromFormat('H:i', $time);
    if ($t === false) {
        return false;
    }
    if ($t->format('H:i') !== $time) {
        return false;
    }
    return true;
}

// Datum netjes tonen (2026-11-10 14:00:00 -> 10-11-2026 14:00)
function format_date($date, $with_time)
{
    if ($date === null || $date === '') {
        return '-';
    }
    if ($with_time) {
        return date('d-m-Y H:i', strtotime($date));
    }
    return date('d-m-Y', strtotime($date));
}
