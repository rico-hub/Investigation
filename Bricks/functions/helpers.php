<?php
// =====================================================================
// functions/helpers.php - kleine functies die je overal gebruikt
// (zelfde als de starter kit op Notion pagina 1, plus 2 demo-hulpjes onderaan)
// =====================================================================

// Veilig tonen in HTML (tegen XSS). Gebruik bij ELKE echo van data.
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Doorsturen + ALTIJD stoppen
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

// ---- Meldingen (flash): 1 keer tonen, ook na een redirect ----
function set_flash($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function show_flash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="flash flash-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
        unset($_SESSION['flash']);
    }
}

// ---- Error codes ----
// 420: een check faalt
function function_error($message)
{
    set_flash('error', 'Error-code: ' . ERR_FUNCTION . ' - ' . $message);
    return false;
}

// 410: database fout
function database_error($message, $detail)
{
    error_log('[410] ' . $message . ' | ' . $detail);
    set_flash('error', 'Error-code: ' . ERR_DATABASE . ' - ' . $message . ' (' . $detail . ')');
    return false;
}

// ---- Invoer ophalen (nooit direct $_GET['x']) ----
function get_param($key)
{
    if (isset($_GET[$key]) && is_string($_GET[$key])) {
        return trim($_GET[$key]);
    }
    return '';
}

function post_param($key)
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return trim($_POST[$key]);
    }
    return '';
}

// Wachtwoorden NIET trimmen
function post_raw($key)
{
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return $_POST[$key];
    }
    return '';
}

// Lijst uit formulier (meerdere checkboxes: name="tags[]")
function post_array($key)
{
    if (isset($_POST[$key]) && is_array($_POST[$key])) {
        return $_POST[$key];
    }
    return array();
}

function is_post()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return true;
    }
    return false;
}

// Geldig id? (alleen cijfers, groter dan 0)
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

// Alles na de ? in de URL (om terug te gaan naar dezelfde filters)
function current_query()
{
    if (isset($_SERVER['QUERY_STRING'])) {
        return $_SERVER['QUERY_STRING'];
    }
    return '';
}

// ---- Waarden behouden in formulieren ----
function selected($current, $value)
{
    if ((string) $current === (string) $value) {
        return 'selected';
    }
    return '';
}

function checked($current, $value)
{
    if ((string) $current === (string) $value) {
        return 'checked';
    }
    return '';
}

// Voor meerdere checkboxes: staat $value in de gekozen lijst?
function checked_in($list, $value)
{
    foreach ($list as $item) {
        if ((string) $item === (string) $value) {
            return 'checked';
        }
    }
    return '';
}

// Geldige datum jjjj-mm-dd (zo stuurt input type="date" hem)
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

// Geldige datum + tijd zoals input type="datetime-local" hem stuurt: 2026-11-20T14:30
function is_valid_datetime_local($value)
{
    $d = DateTime::createFromFormat('Y-m-d\TH:i', $value);
    if ($d === false) {
        return false;
    }
    if ($d->format('Y-m-d\TH:i') !== $value) {
        return false;
    }
    return true;
}

// Datum netjes tonen: 2026-11-10 -> 10-11-2026
function format_date($date)
{
    if ($date === null || $date === '') {
        return '-';
    }
    return date('d-m-Y', strtotime($date));
}

// =====================================================================
// Alleen voor deze demo (heb je op de assessment niet nodig)
// =====================================================================

// Laat de code van een bouwsteen-bestand zien, in een uitklapblok
function show_code($file)
{
    echo '<details class="code"><summary>Bekijk de code (' . e($file) . ')</summary><div class="code-box">';
    highlight_file(__DIR__ . '/../' . $file);
    echo '</div></details>';
}

// Waarde precies zoals hij uit de database komt: NULL, '' of 'tekst'
function raw_value($value)
{
    if ($value === null) {
        return 'NULL';
    }
    return "'" . $value . "'";
}

// Tabel met wat er binnenkwam in $_POST of $_GET (zoals var_dump, maar leesbaar)
function show_received($title, $values)
{
    echo '<div class="card received"><h3>' . e($title) . '</h3>';
    if (count($values) === 0) {
        echo '<p class="hint">Niets ontvangen.</p></div>';
        return;
    }
    echo '<table class="table"><thead><tr><th>name</th><th>waarde</th></tr></thead><tbody>';
    foreach ($values as $key => $value) {
        if (is_array($value)) {
            $value = 'array(' . implode(', ', $value) . ')';
        } elseif ($value === '') {
            $value = "'' (leeg)";
        }
        echo '<tr><td><code>' . e($key) . '</code></td><td>' . e($value) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}
