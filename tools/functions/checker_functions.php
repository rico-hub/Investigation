<?php
// =====================================================================
// tools/functions/checker_functions.php - alle controles van de App checker
// Werkt offline: alleen PHP, geen internet, geen AI.
// Elke controle geeft een lijst "meldingen" terug:
//   array('level' => 'error' of 'warn', 'cat' => groep, 'file' => bestand,
//         'line' => regel, 'msg' => wat is er mis, 'how' => hoe los je het op,
//         'fix' => naam van de fix-knop of '')
// =====================================================================

mysqli_report(MYSQLI_REPORT_OFF);

define('AC_BASE', realpath(__DIR__ . '/../..'));                       // S:\Investigation
define('AC_KIT', AC_BASE . DIRECTORY_SEPARATOR . 'kit');
define('AC_BACKUPS', AC_BASE . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'backups');

// Veilig tonen in HTML
function ac_h($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Pad met / en kleine letters, om paden te kunnen vergelijken (Windows)
function ac_norm($path)
{
    return strtolower(str_replace('\\', '/', $path));
}

// Ligt dit pad binnen S:\Investigation?
function ac_inside_base($path)
{
    $base = ac_norm(AC_BASE) . '/';
    $p = ac_norm($path) . '/';
    if (strpos($p, $base) === 0) {
        return true;
    }
    return false;
}

// Pad relatief aan de app-map, voor in de meldingen
function ac_rel($root, $path)
{
    $r = str_replace('\\', '/', substr($path, strlen($root) + 1));
    return $r;
}

// ---------------------------------------------------------------------
// Invoer: map of bestand -> app-map (de map met config/config.php)
// Geeft array('root' => ..., 'file' => ... of '', 'error' => ... of '')
// ---------------------------------------------------------------------
function ac_resolve($input)
{
    $input = trim($input);
    $input = trim($input, "\"' ");
    $result = array('root' => '', 'file' => '', 'error' => '');

    if ($input === '') {
        $result['error'] = 'Vul een map of bestand in.';
        return $result;
    }

    // Alleen een naam (dierenasiel)? Dan in S:\Investigation zoeken
    $path = $input;
    if (!preg_match('/^[a-zA-Z]:[\\\\\/]/', $input) && substr($input, 0, 1) !== '/' && substr($input, 0, 1) !== '\\') {
        $path = AC_BASE . DIRECTORY_SEPARATOR . $input;
    }
    // Een localhost-adres mag ook: http://localhost/Investigation/dierenasiel/index.php
    if (preg_match('#^https?://localhost/Investigation/(.*)$#i', $input, $m)) {
        $path = AC_BASE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $m[1]);
    }

    $real = realpath($path);
    if ($real === false) {
        $result['error'] = 'Dit bestaat niet: ' . $path;
        return $result;
    }
    if (!ac_inside_base($real)) {
        $result['error'] = 'Alleen mappen in ' . AC_BASE . ' kunnen gecontroleerd worden.';
        return $result;
    }

    $dir = $real;
    if (is_file($real)) {
        $result['file'] = $real;
        $dir = dirname($real);
    }

    // Omhoog lopen tot we config/config.php vinden
    $root = '';
    $current = $dir;
    for ($i = 0; $i < 6; $i++) {
        if (is_file($current . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
            $root = $current;
            break;
        }
        $parent = dirname($current);
        if ($parent === $current || !ac_inside_base($parent)) {
            break;
        }
        $current = $parent;
    }

    if ($root === '') {
        $result['error'] = 'Geen app gevonden: er staat geen config/config.php in deze map (of erboven).';
        return $result;
    }
    $result['root'] = $root;
    return $result;
}

// Alle apps in S:\Investigation (mappen met config/config.php), voor de snelkeuze
function ac_list_apps()
{
    $apps = array();
    foreach (scandir(AC_BASE) as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (is_file(AC_BASE . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
            $apps[] = $name;
        }
    }
    return $apps;
}

// Alle bestanden met deze extensies in de app (zonder de voorbeeldmappen extra/sql)
function ac_files($root, $exts)
{
    $list = array();
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        $path = $file->getPathname();
        $rel = ac_rel($root, $path);
        if (strpos($rel, 'extra/') === 0 || strpos($rel, 'sql/') === 0 || strpos($rel, '.git/') === 0) {
            continue;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, $exts, true)) {
            $list[] = $path;
        }
    }
    sort($list);
    return $list;
}

// Is dit een kopie van de kit? (de kit-config heeft AANPASSEN-commentaar)
function ac_is_kit_copy($root)
{
    $config = file_get_contents($root . '/config/config.php');
    if (strpos($config, 'AANPASSEN: naam van je app') !== false) {
        return true;
    }
    return false;
}

function ac_is_kit($root)
{
    if (ac_norm($root) === ac_norm(AC_KIT)) {
        return true;
    }
    return false;
}

function ac_issue($level, $cat, $file, $line, $msg, $how, $fix)
{
    return array('level' => $level, 'cat' => $cat, 'file' => $file, 'line' => $line, 'msg' => $msg, 'how' => $how, 'fix' => $fix);
}

// Regelnummer van een positie in een tekst
function ac_line_of($text, $pos)
{
    return substr_count(substr($text, 0, $pos), "\n") + 1;
}

// ---------------------------------------------------------------------
// config.php uitlezen (zonder hem uit te voeren)
// ---------------------------------------------------------------------
function ac_config($root)
{
    $text = file_get_contents($root . '/config/config.php');
    $config = array('DB_HOST' => 'localhost', 'DB_USER' => 'root', 'DB_PASS' => '', 'DB_NAME' => '', 'APP_NAME' => '');
    foreach (array_keys($config) as $key) {
        if (preg_match("/define\(\s*'" . $key . "'\s*,\s*'([^']*)'\s*\)/", $text, $m)) {
            $config[$key] = $m[1];
        }
    }
    return $config;
}

// =====================================================================
// CONTROLE 1: PHP-fouten (syntax) - zelfde als "Parse error"
// =====================================================================
function ac_check_syntax($root, $files)
{
    $issues = array();
    foreach ($files as $path) {
        $code = file_get_contents($path);
        try {
            token_get_all($code, TOKEN_PARSE);
        } catch (ParseError $e) {
            $issues[] = ac_issue('error', 'PHP-fouten', ac_rel($root, $path), $e->getLine(),
                'PHP kan dit bestand niet lezen: ' . $e->getMessage(),
                'Kijk op deze regel en een paar regels erboven: vaak een vergeten ; of } of een quote die niet dicht is. PHP merkt de fout pas op de regel erna.', '');
        }
    }
    return $issues;
}

// =====================================================================
// CONTROLE 2: regels van het assessment (geen ?:, geen ??, geen PDO, geen mysqli_ procedural)
// =====================================================================
function ac_check_rules($root, $files)
{
    $issues = array();
    foreach ($files as $path) {
        $tokens = @token_get_all(file_get_contents($path));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if ($t === '?') {
                $issues[] = ac_issue('warn', 'Regels assessment', ac_rel($root, $path), ac_token_line($tokens, $i),
                    'Hier staat een korte if (ternary: ? :). Dat mag niet in het assessment.',
                    'Schrijf het uit met if / else.', '');
            }
            if (is_array($t) && $t[0] === T_COALESCE) {
                $issues[] = ac_issue('warn', 'Regels assessment', ac_rel($root, $path), $t[2],
                    'Hier staat ?? (null coalescing). Dat mag niet in het assessment.',
                    'Schrijf het uit met if (isset(...)) { ... } else { ... }.', '');
            }
            if (is_array($t) && $t[0] === T_NEW) {
                $next = ac_next_token($tokens, $i);
                if (is_array($next) && strtolower($next[1]) === 'pdo') {
                    $issues[] = ac_issue('error', 'Regels assessment', ac_rel($root, $path), $t[2],
                        'Hier wordt PDO gebruikt. Verplicht is MySQLi object-oriented.',
                        'Gebruik $conn = new mysqli(...) en $conn->prepare(...).', '');
                }
            }
            if (is_array($t) && $t[0] === T_STRING && stripos($t[1], 'mysqli_') === 0 && strtolower($t[1]) !== 'mysqli_report') {
                $next = ac_next_token($tokens, $i);
                if ($next === '(') {
                    $issues[] = ac_issue('error', 'Regels assessment', ac_rel($root, $path), $t[2],
                        $t[1] . '() is MySQLi procedural. Verplicht is MySQLi object-oriented (met ->).',
                        'Bijvoorbeeld: mysqli_query($conn, $sql) wordt $conn->query($sql).', '');
                }
            }
        }
    }
    return $issues;
}

// Regelnummer van een token (een los teken heeft geen regelnummer: dan de vorige)
function ac_token_line($tokens, $i)
{
    for ($j = $i; $j >= 0; $j--) {
        if (is_array($tokens[$j])) {
            return $tokens[$j][2] + substr_count($tokens[$j][1], "\n");
        }
    }
    return 1;
}

// Volgende token die geen spatie of commentaar is
function ac_next_token($tokens, $i)
{
    $count = count($tokens);
    for ($j = $i + 1; $j < $count; $j++) {
        $t = $tokens[$j];
        if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
            continue;
        }
        return $t;
    }
    return null;
}

function ac_prev_token($tokens, $i)
{
    for ($j = $i - 1; $j >= 0; $j--) {
        $t = $tokens[$j];
        if (is_array($t) && ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
            continue;
        }
        return $t;
    }
    return null;
}

// =====================================================================
// CONTROLE 3: woorden van de kit die nog niet hernoemd zijn
// =====================================================================
function ac_kit_word_pattern()
{
    return '/(?<![A-Za-z])(items?|Items?|categor(?:y|ies)|Categor(?:y|ies)|orders?|Orders?)(?![a-z])/';
}

function ac_check_leftovers($root, $files)
{
    $issues = array();
    if (ac_is_kit($root) || !ac_is_kit_copy($root)) {
        return $issues;
    }
    $pattern = ac_kit_word_pattern();

    // Bestandsnamen
    foreach ($files as $path) {
        $name = basename($path);
        if (preg_match($pattern, $name, $m)) {
            $issues[] = ac_issue('warn', 'Hernoemen', ac_rel($root, $path), 0,
                'De bestandsnaam bevat nog het kit-woord "' . $m[1] . '".',
                'Gebruik de knop Hernoemen hieronder, of hernoem het bestand met de hand.', 'rename');
        }
    }

    // Inhoud
    foreach ($files as $path) {
        $lines = file($path);
        $found = array();
        foreach ($lines as $n => $line) {
            if (preg_match($pattern, $line, $m)) {
                $found[] = ($n + 1) . ' (' . $m[1] . ')';
            }
        }
        if (count($found) > 0) {
            $show = array_slice($found, 0, 6);
            $more = '';
            if (count($found) > 6) {
                $more = ' en nog ' . (count($found) - 6) . ' regels';
            }
            $issues[] = ac_issue('warn', 'Hernoemen', ac_rel($root, $path), (int) $found[0],
                'Nog ' . count($found) . 'x een kit-woord (items, categories, orders). Regels: ' . implode(', ', $show) . $more . '.',
                'Nog niet hernoemd? Gebruik de knop Hernoemen. Is het woord juist goed (bv. een webshop houdt orders)? Dan mag het blijven.', 'rename');
        }
    }
    return $issues;
}

// =====================================================================
// CONTROLE 4: css en js heel? (kapot door zoek en vervang)
// =====================================================================
function ac_check_css_js($root)
{
    $issues = array();
    if (ac_is_kit($root)) {
        return $issues;
    }
    $css = $root . '/css/style.css';
    if (!is_file($css)) {
        $issues[] = ac_issue('error', 'CSS en JS', 'css/style.css', 0, 'Het bestand css/style.css ontbreekt: de pagina heeft geen opmaak.',
            'Kopieer css/style.css uit de kit (knop hieronder).', 'cssjs');
    } else {
        $text = file_get_contents($css);
        // Elke css-eigenschap (zoals align-items:) moet ook in de kit bestaan.
        // Een eigenschap die de kit niet kent (zoals align-animals:) komt van zoek en vervang.
        $kit_css = '';
        if (is_file(AC_KIT . '/css/style.css')) {
            $kit_css = file_get_contents(AC_KIT . '/css/style.css');
        }
        if ($kit_css !== '') {
            preg_match_all('/(?<![a-z-])([a-z][a-z-]*)\s*:\s*[^;{}]+;/', $kit_css, $k);
            $known = array_flip($k[1]);
            if (preg_match_all('/(?<![a-z-])([a-z][a-z-]*)\s*:\s*[^;{}]+;/', $text, $mm, PREG_OFFSET_CAPTURE)) {
                $bad = array();
                foreach ($mm[1] as $x) {
                    $prop = $x[0];
                    if (!isset($known[$prop]) && !isset($bad[$prop])) {
                        $bad[$prop] = ac_line_of($text, $x[1]);
                    }
                }
                if (count($bad) > 0) {
                    $names = array_keys($bad);
                    $issues[] = ac_issue('error', 'CSS en JS', 'css/style.css', reset($bad),
                        'De css bevat eigenschappen die niet bestaan: ' . implode(', ', array_slice($names, 0, 5)) . '. Waarschijnlijk is er ook in de css vervangen (bv. align-items werd align-animals).',
                        'Zet de css terug met de knop hieronder. Vervang daarna alleen in *.php, *.sql.', 'cssjs');
                }
            }
        }
    }
    foreach (array('column-filter.js', 'filter-bar.js') as $js) {
        $mine = $root . '/js/' . $js;
        $kit = AC_KIT . '/js/' . $js;
        if (!is_file($kit)) {
            continue;
        }
        if (!is_file($mine)) {
            $issues[] = ac_issue('warn', 'CSS en JS', 'js/' . $js, 0, 'js/' . $js . ' ontbreekt: de filters werken niet.',
                'Kopieer de js uit de kit (knop hieronder).', 'cssjs');
        } elseif (file_get_contents($mine) !== file_get_contents($kit)) {
            $issues[] = ac_issue('error', 'CSS en JS', 'js/' . $js, 0,
                'js/' . $js . ' is anders dan in de kit. Waarschijnlijk is er ook in de js vervangen (bv. getItem werd getAnimal).',
                'Zet de js terug met de knop hieronder.', 'cssjs');
        }
    }
    return $issues;
}

// =====================================================================
// CONTROLE 5: bestaan de bestanden die je inlaadt (require) en de pagina's waar je naar linkt?
// =====================================================================
function ac_check_paths($root, $files)
{
    $issues = array();
    $all_php = array();
    foreach ($files as $p) {
        $all_php[] = ac_rel($root, $p);
    }

    foreach ($files as $path) {
        $code = file_get_contents($path);
        $rel = ac_rel($root, $path);

        // require / include
        if (preg_match_all("/(require|include)(_once)?\s*\(?\s*(__DIR__\s*\.\s*)?'([^']+\.php)'/", $code, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $target = $m[4][0];
                if ($m[3][0] !== '') {
                    $full = dirname($path) . '/' . ltrim($target, '/');
                } else {
                    $full = $root . '/' . $target;
                }
                if (!is_file($full)) {
                    $hint = ac_similar_file(basename($target), $all_php);
                    $issues[] = ac_issue('error', 'Bestanden en pagina\'s', $rel, ac_line_of($code, $m[0][1]),
                        'Dit bestand wordt ingeladen maar bestaat niet: ' . $target . '. Dat geeft "Failed to open stream".',
                        'Is het hernoemd? Pas de naam hier aan.' . $hint, '');
                }
            }
        }

        // links, formulieren, redirects en het menu
        $patterns = array(
            '/(?:href|action)="([a-z0-9_]+\.php)/',
            "/redirect\(\s*'([a-z0-9_]+\.php)/",
            "/\\\$(?:menu|account_menu)\[\s*'([a-z0-9_]+\.php)'/",
            "/\\\$(?:cart_file|form_action)\s*=\s*'([a-z0-9_]+\.php)/",
        );
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $code, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
                foreach ($mm as $m) {
                    $page = $m[1][0];
                    if (!is_file($root . '/' . $page)) {
                        $hint = ac_similar_file($page, $all_php);
                        $issues[] = ac_issue('warn', 'Bestanden en pagina\'s', $rel, ac_line_of($code, $m[0][1]),
                            'Er wordt gelinkt naar ' . $page . ', maar die pagina bestaat niet. Klikken geeft "Not Found" (404).',
                            'Pas de naam van de link aan, of maak de pagina.' . $hint, '');
                    }
                }
            }
        }
    }
    return $issues;
}

// Lijkt er een bestand op? (voor een tip)
function ac_similar_file($name, $all)
{
    $best = '';
    $best_score = 99;
    foreach ($all as $rel) {
        $score = levenshtein(strtolower($name), strtolower(basename($rel)));
        if ($score < $best_score) {
            $best_score = $score;
            $best = $rel;
        }
    }
    if ($best !== '' && $best_score <= 8) {
        return ' Bedoel je ' . $best . '?';
    }
    return '';
}

// =====================================================================
// CONTROLE 6: functies die worden aangeroepen maar nergens bestaan
// =====================================================================
function ac_check_functions($root, $files)
{
    $issues = array();
    $defined = array();
    foreach ($files as $path) {
        if (preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', file_get_contents($path), $m)) {
            foreach ($m[1] as $name) {
                $defined[strtolower($name)] = true;
            }
        }
    }
    $skip_before = array(T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_NULLSAFE_OBJECT_OPERATOR);

    foreach ($files as $path) {
        $tokens = @token_get_all(file_get_contents($path));
        $count = count($tokens);
        $reported = array();
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (!is_array($t) || $t[0] !== T_STRING) {
                continue;
            }
            if (ac_next_token($tokens, $i) !== '(') {
                continue;
            }
            $prev = ac_prev_token($tokens, $i);
            if (is_array($prev) && in_array($prev[0], $skip_before, true)) {
                continue;
            }
            $name = strtolower($t[1]);
            if (isset($defined[$name]) || function_exists($name) || isset($reported[$name])) {
                continue;
            }
            $reported[$name] = true;
            $issues[] = ac_issue('error', 'Functies', ac_rel($root, $path), $t[2],
                'De functie ' . $t[1] . '() wordt aangeroepen, maar bestaat nergens. Dat geeft "Call to undefined function".',
                'Is de functie hernoemd? Zoek in functions/ hoe hij nu heet. Of staat het bestand niet in includes/init.php?', '');
        }
    }
    return $issues;
}

// =====================================================================
// CONTROLE 7: tellen bij INSERT / UPDATE / SELECT
// vraagtekens = letters = waarden
// =====================================================================
function ac_check_counts($root, $files)
{
    $issues = array();
    foreach ($files as $path) {
        $code = file_get_contents($path);
        if (!preg_match_all('/(db_insert|db_change|db_select_all|db_select_one|db_execute)\s*\(\s*\$conn\s*,/', $code, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            continue;
        }
        foreach ($mm as $m) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $sql = ac_read_quoted($code, $pos);
            if ($sql === null) {
                continue; // SQL staat in een variabele: kan niet geteld worden
            }
            $pos = $sql['end'];
            if (!ac_expect_comma($code, $pos)) {
                continue;
            }
            $types = ac_read_quoted($code, $pos);
            if ($types === null) {
                continue;
            }
            $pos = $types['end'];
            if (!ac_expect_comma($code, $pos)) {
                continue;
            }
            $values = ac_read_array($code, $pos);
            if ($values === null) {
                continue;
            }

            $q = substr_count($sql['text'], '?');
            $letters = strlen($types['text']);
            $n = $values;
            $line = ac_line_of($code, $m[0][1]);
            if ($q !== $letters || $q !== $n) {
                $issues[] = ac_issue('error', 'Tellen (INSERT, UPDATE)', ac_rel($root, $path), $line,
                    'Telfout: de SQL heeft ' . $q . ' vraagtekens, de letters \'' . $types['text'] . '\' zijn er ' . $letters . ', en er staan ' . $n . ' waarden in array(...).',
                    'Alle 3 moeten gelijk zijn. Per kolom: 1 vraagteken, 1 letter (s = tekst of datum, i = heel getal, d = kommagetal), 1 waarde.', '');
            } elseif (preg_match('/[^sidb]/', $types['text'])) {
                $issues[] = ac_issue('error', 'Tellen (INSERT, UPDATE)', ac_rel($root, $path), $line,
                    'De letters \'' . $types['text'] . '\' bevatten een letter die niet bestaat.',
                    'Alleen s (tekst, datum), i (heel getal), d (kommagetal) en b mogen.', '');
            }
        }
    }
    return $issues;
}

// Lees een PHP-string met enkele quotes vanaf $pos (spaties overslaan). null = geen string.
function ac_read_quoted($code, $pos)
{
    $len = strlen($code);
    while ($pos < $len && ctype_space($code[$pos])) {
        $pos++;
    }
    if ($pos >= $len || $code[$pos] !== "'") {
        return null;
    }
    $pos++;
    $text = '';
    while ($pos < $len) {
        $c = $code[$pos];
        if ($c === '\\' && $pos + 1 < $len) {
            $text .= $code[$pos + 1];
            $pos += 2;
            continue;
        }
        if ($c === "'") {
            // Wordt de string aan elkaar geplakt met een punt? Dan niet tellen.
            $after = $pos + 1;
            while ($after < $len && ctype_space($code[$after])) {
                $after++;
            }
            if ($after < $len && $code[$after] === '.') {
                return null;
            }
            return array('text' => $text, 'end' => $pos + 1);
        }
        $text .= $c;
        $pos++;
    }
    return null;
}

function ac_expect_comma($code, &$pos)
{
    $len = strlen($code);
    while ($pos < $len && ctype_space($code[$pos])) {
        $pos++;
    }
    if ($pos < $len && $code[$pos] === ',') {
        $pos++;
        return true;
    }
    return false;
}

// Tel de waarden in array(...) vanaf $pos. null = geen array (bv. een variabele).
function ac_read_array($code, $pos)
{
    $len = strlen($code);
    while ($pos < $len && ctype_space($code[$pos])) {
        $pos++;
    }
    if (strtolower(substr($code, $pos, 6)) !== 'array(') {
        return null;
    }
    $pos += 6;
    $depth = 0;
    $items = 0;
    $current = '';
    while ($pos < $len) {
        $c = $code[$pos];
        if ($c === "'" || $c === '"') {
            $quote = $c;
            $current .= $c;
            $pos++;
            while ($pos < $len && $code[$pos] !== $quote) {
                if ($code[$pos] === '\\') {
                    $current .= $code[$pos];
                    $pos++;
                }
                $current .= $code[$pos];
                $pos++;
            }
            $current .= $quote;
            $pos++;
            continue;
        }
        if ($c === '(' || $c === '[') {
            $depth++;
        } elseif ($c === ')' || $c === ']') {
            if ($depth === 0) {
                if (trim($current) !== '') {
                    $items++;
                }
                return $items;
            }
            $depth--;
        } elseif ($c === ',' && $depth === 0) {
            if (trim($current) !== '') {
                $items++;
            }
            $current = '';
            $pos++;
            continue;
        }
        $current .= $c;
        $pos++;
    }
    return null;
}

// =====================================================================
// CONTROLE 8: config.php en database.sql dezelfde database?
// =====================================================================
function ac_check_dbname($root, $config)
{
    $issues = array();
    $sqlfile = $root . '/database.sql';
    if (!is_file($sqlfile)) {
        $issues[] = ac_issue('warn', 'Database', 'database.sql', 0, 'Er is geen database.sql in deze app.',
            'Maak er een (kopieer die uit de kit) zodat je de database opnieuw kunt maken.', '');
        return $issues;
    }
    $sql = file_get_contents($sqlfile);
    $db = $config['DB_NAME'];
    $checks = array(
        'DROP DATABASE IF EXISTS' => '/DROP\s+DATABASE\s+IF\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?\s*;/i',
        'CREATE DATABASE' => '/CREATE\s+DATABASE\s+`?([a-zA-Z0-9_]+)`?/i',
        'USE' => '/^\s*USE\s+`?([a-zA-Z0-9_]+)`?\s*;/im',
    );
    foreach ($checks as $label => $pattern) {
        if (preg_match($pattern, $sql, $m, PREG_OFFSET_CAPTURE)) {
            if ($m[1][0] !== $db) {
                $issues[] = ac_issue('error', 'Database', 'database.sql', ac_line_of($sql, $m[0][1]),
                    'Bij ' . $label . ' staat database "' . $m[1][0] . '", maar in config.php staat DB_NAME "' . $db . '".',
                    'Ze moeten gelijk zijn. De knop hieronder zet de 3 namen in database.sql gelijk aan DB_NAME.', 'dbname');
            }
        }
    }
    if (!ac_is_kit($root) && $db === 'kit') {
        $issues[] = ac_issue('warn', 'Database', 'config/config.php', 0,
            'DB_NAME is nog "kit". Dan delen deze app en de kit dezelfde database.',
            'Geef je app een eigen databasenaam (veld "Databasenaam" bij Hernoemen), en importeer daarna.', 'rename');
    }
    return $issues;
}

// =====================================================================
// CONTROLE 9: de echte database (MySQL in XAMPP)
// - bestaat hij? alle tabellen er? alle kolommen er?
// - kent de database elke SQL in de code? (prepare = controleren zonder uitvoeren)
// =====================================================================
function ac_connect($config, $with_db)
{
    $db = '';
    if ($with_db) {
        $db = $config['DB_NAME'];
    }
    $conn = @new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $db);
    return $conn;
}

// Tabellen en kolommen uit database.sql halen
function ac_schema_from_sql($sql)
{
    $schema = array();
    if (preg_match_all('/CREATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*ENGINE/is', $sql, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $m) {
            $cols = array();
            foreach (preg_split('/\r?\n/', $m[2]) as $line) {
                $line = trim($line);
                if (preg_match('/^`?([a-z_][a-z0-9_]*)`?\s+(INT|TINYINT|SMALLINT|BIGINT|VARCHAR|CHAR|TEXT|DECIMAL|DATE|DATETIME|TIME|ENUM|FLOAT|DOUBLE|BOOLEAN|TIMESTAMP)/i', $line, $c)) {
                    $cols[] = strtolower($c[1]);
                }
            }
            $schema[strtolower($m[1])] = $cols;
        }
    }
    return $schema;
}

function ac_check_database($root, $config, $files)
{
    $issues = array();
    $conn = ac_connect($config, false);
    if ($conn->connect_errno) {
        $issues[] = ac_issue('error', 'Database', 'config/config.php', 0,
            'Geen verbinding met MySQL: ' . $conn->connect_error,
            'Start MySQL in het XAMPP Control Panel.', '');
        return $issues;
    }
    $db = $config['DB_NAME'];
    $res = $conn->query("SHOW DATABASES LIKE '" . $conn->real_escape_string($db) . "'");
    if ($res === false || $res->num_rows === 0) {
        $issues[] = ac_issue('error', 'Database', 'config/config.php', 0,
            'De database "' . $db . '" bestaat niet in MySQL. Dat geeft "Unknown database".',
            'Importeer database.sql (knop hieronder, of phpMyAdmin > Importeren).', 'import');
        return $issues;
    }
    $conn->select_db($db);

    // Tabellen en kolommen: database.sql tegenover de echte database
    $sqlfile = $root . '/database.sql';
    if (is_file($sqlfile)) {
        $schema = ac_schema_from_sql(file_get_contents($sqlfile));
        foreach ($schema as $table => $cols) {
            $res = $conn->query('SHOW COLUMNS FROM `' . $table . '`');
            if ($res === false) {
                $issues[] = ac_issue('error', 'Database', 'database.sql', 0,
                    'De tabel ' . $table . ' staat in database.sql, maar niet in de database.',
                    'database.sql is veranderd na het importeren. Importeer opnieuw.', 'import');
                continue;
            }
            $live = array();
            while ($row = $res->fetch_assoc()) {
                $live[] = strtolower($row['Field']);
            }
            $missing = array_diff($cols, $live);
            $extra = array_diff($live, $cols);
            if (count($missing) > 0 || count($extra) > 0) {
                $text = 'Tabel ' . $table . ' in de database is anders dan in database.sql.';
                if (count($missing) > 0) {
                    $text .= ' Ontbreekt in de database: ' . implode(', ', $missing) . '.';
                }
                if (count($extra) > 0) {
                    $text .= ' Staat alleen nog in de database: ' . implode(', ', $extra) . '.';
                }
                $issues[] = ac_issue('error', 'Database', 'database.sql', 0, $text,
                    'Je hebt database.sql aangepast maar nog niet opnieuw geïmporteerd.', 'import');
            }
        }
    }

    // Elke vaste SQL in de code laten controleren door MySQL (prepare voert niets uit)
    foreach ($files as $path) {
        $tokens = @token_get_all(file_get_contents($path));
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $t = $tokens[$i];
            if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }
            $raw = $t[1];
            if (substr($raw, 0, 1) !== "'") {
                continue;
            }
            $sql = str_replace(array("\\'", '\\\\'), array("'", '\\'), substr($raw, 1, -1));
            if (!preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\b/i', $sql)) {
                continue;
            }
            // Aan elkaar geplakt met een punt? Dan is dit maar een stukje: overslaan
            if (ac_next_token($tokens, $i) === '.' || ac_prev_token($tokens, $i) === '.') {
                continue;
            }
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $issues[] = ac_issue('error', 'Database', ac_rel($root, $path), $t[2],
                    'MySQL keurt deze SQL af: ' . $conn->error,
                    'Meestal een kolom of tabel die (nog) niet bestaat. Klopt de naam? Staat hij in database.sql, en is die opnieuw geïmporteerd?', '');
            } else {
                $stmt->close();
            }
        }
    }
    $conn->close();
    return $issues;
}

// =====================================================================
// CONTROLE 10: formulier-velden: post_param('x') zonder name="x"
// =====================================================================
function ac_check_forms($root, $files)
{
    $issues = array();
    $names = array();
    $uses = array();
    foreach ($files as $path) {
        $code = file_get_contents($path);
        if (preg_match_all('/name="([a-zA-Z0-9_]+)(\[\])?"/', $code, $m)) {
            foreach ($m[1] as $name) {
                $names[$name] = true;
            }
        }
        if (preg_match_all("/post_(?:param|raw)\(\s*'([a-zA-Z0-9_]+)'\s*\)/", $code, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach ($mm as $x) {
                $key = $x[1][0];
                if (!isset($uses[$key])) {
                    $uses[$key] = array(ac_rel($root, $path), ac_line_of($code, $x[0][1]));
                }
            }
        }
    }
    foreach ($uses as $key => $where) {
        if (!isset($names[$key])) {
            $issues[] = ac_issue('warn', 'Formulieren', $where[0], $where[1],
                'De code leest het veld "' . $key . '" (post_param), maar geen enkel formulier heeft name="' . $key . '".',
                'Het veld blijft dan altijd leeg (melding "verplicht"). Maak name="..." in het formulier precies hetzelfde.', '');
        }
    }
    return $issues;
}

// =====================================================================
// CONTROLE 11: statussen: lijst in de code = ENUM in de database? kleur in de css?
// =====================================================================
function ac_check_statuses($root, $files)
{
    $issues = array();
    $sqlfile = $root . '/database.sql';
    $sql = '';
    if (is_file($sqlfile)) {
        $sql = file_get_contents($sqlfile);
    }
    $css = '';
    if (is_file($root . '/css/style.css')) {
        $css = file_get_contents($root . '/css/style.css');
    }
    $all_status_words = array();

    foreach ($files as $path) {
        $code = file_get_contents($path);
        if (!preg_match_all('/function\s+([a-z0-9_]+)_statuses\s*\(\s*\)\s*\{\s*return\s+array\(([^)]*)\)/', $code, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            continue;
        }
        foreach ($mm as $m) {
            $thing = $m[1][0];
            // array('a' => 'Tekst A', ...) = de sleutels zijn de statussen; anders alle waarden
            if (strpos($m[2][0], '=>') !== false) {
                preg_match_all("/'([^']*)'\s*=>/", $m[2][0], $vals);
            } else {
                preg_match_all("/'([^']*)'/", $m[2][0], $vals);
            }
            $list = $vals[1];
            foreach ($list as $v) {
                $all_status_words[$v] = true;
            }
            $line = ac_line_of($code, $m[0][1]);

            // ENUM van de kolom status in tabel <ding>s
            $table = $thing . 's';
            if (preg_match('/CREATE\s+TABLE\s+`?' . preg_quote($table, '/') . '`?\s*\((.*?)\)\s*ENGINE/is', $sql, $t)) {
                if (preg_match("/\bstatus\s+ENUM\(([^)]*)\)/i", $t[1], $e)) {
                    preg_match_all("/'([^']*)'/", $e[1], $ev);
                    $enum = $ev[1];
                    if (array_values($enum) !== array_values($list)) {
                        $issues[] = ac_issue('error', 'Statussen', ac_rel($root, $path), $line,
                            'De statussen in de code (' . implode(', ', $list) . ') zijn anders dan de ENUM in database.sql (' . implode(', ', $enum) . ').',
                            'Zet in beide precies dezelfde woorden, in dezelfde volgorde. Zoek op een oude status om alle plekken te vinden.', '');
                    }
                }
            }

            // kleur in de css
            if ($css !== '') {
                foreach ($list as $v) {
                    if (strpos($css, '.status-' . $v . ',') === false && strpos($css, '.status-' . $v . ' ') === false && strpos($css, '.status-' . $v . '{') === false) {
                        $issues[] = ac_issue('warn', 'Statussen', 'css/style.css', 0,
                            'De status "' . $v . '" heeft geen kleur in de css: het label wordt grijs.',
                            'Zoek in css/style.css op NIEUWE STATUS en zet .status-' . $v . ', bij groen, oranje of rood.', '');
                    }
                }
            }
        }
    }

    // Oude kit-statussen die nergens meer bestaan (de winkelmand-knop verdwijnt dan)
    if (count($all_status_words) > 0) {
        foreach (array('actief', 'inactief', 'uitverkocht') as $old) {
            if (isset($all_status_words[$old])) {
                continue;
            }
            foreach ($files as $path) {
                $code = file_get_contents($path);
                if (preg_match_all("/'" . $old . "'/", $code, $mm, PREG_OFFSET_CAPTURE)) {
                    foreach ($mm[0] as $x) {
                        $issues[] = ac_issue('warn', 'Statussen', ac_rel($root, $path), ac_line_of($code, $x[1]),
                            'Hier staat nog de oude status \'' . $old . '\', maar die bestaat niet meer in jouw statussen.',
                            'Vervang hem door jouw status (bv. de status waarbij iets in de winkelmand mag). Anders verdwijnt de knop of klopt de check niet.', '');
                    }
                }
            }
        }
    }
    return $issues;
}

// =====================================================================
// ALLES CONTROLEREN
// $only_file = '' (hele app) of een pad (dan alleen meldingen over dat bestand + de app-brede)
// =====================================================================
function ac_run_all($root, $only_file)
{
    $config = ac_config($root);
    $php = ac_files($root, array('php'));
    $phpsql = ac_files($root, array('php', 'sql'));

    $groups = array(
        'PHP-fouten' => ac_check_syntax($root, $php),
        'Regels assessment' => ac_check_rules($root, $php),
        'Hernoemen' => ac_check_leftovers($root, $phpsql),
        'CSS en JS' => ac_check_css_js($root),
        'Bestanden en pagina\'s' => ac_check_paths($root, $php),
        'Functies' => ac_check_functions($root, $php),
        'Tellen (INSERT, UPDATE)' => ac_check_counts($root, $php),
        'Database' => array_merge(ac_check_dbname($root, $config), ac_check_database($root, $config, $php)),
        'Formulieren' => ac_check_forms($root, $php),
        'Statussen' => ac_check_statuses($root, $php),
    );

    if ($only_file !== '') {
        $rel = ac_rel($root, $only_file);
        foreach ($groups as $name => $issues) {
            $keep = array();
            foreach ($issues as $issue) {
                if ($issue['file'] === $rel) {
                    $keep[] = $issue;
                }
            }
            $groups[$name] = $keep;
        }
    }
    return $groups;
}

// =====================================================================
// FIXES - altijd eerst een reservekopie in tools/backups/<app>/<tijd>/
// =====================================================================
function ac_backup($root, $paths, $stamp)
{
    $dir = AC_BACKUPS . DIRECTORY_SEPARATOR . basename($root) . DIRECTORY_SEPARATOR . $stamp;
    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        $target = $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ac_rel($root, $path));
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }
        copy($path, $target);
    }
    return $dir;
}

// Fix: css en js terugzetten uit de kit
function ac_fix_cssjs($root, $stamp)
{
    $paths = array($root . '/css/style.css', $root . '/js/column-filter.js', $root . '/js/filter-bar.js');
    $backup = ac_backup($root, $paths, $stamp);
    foreach (array('css/style.css', 'js/column-filter.js', 'js/filter-bar.js') as $rel) {
        $to = $root . '/' . $rel;
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0777, true);
        }
        copy(AC_KIT . '/' . $rel, $to);
    }
    return 'css/style.css en de js-bestanden zijn teruggezet uit de kit. Reservekopie: ' . $backup;
}

// Fix: database.sql dezelfde naam als DB_NAME
function ac_fix_dbname($root, $stamp)
{
    $config = ac_config($root);
    $db = $config['DB_NAME'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $db)) {
        return 'Niet gedaan: DB_NAME in config.php is leeg of bevat rare tekens.';
    }
    $file = $root . '/database.sql';
    $backup = ac_backup($root, array($file), $stamp);
    $sql = file_get_contents($file);
    $sql = preg_replace('/(DROP\s+DATABASE\s+IF\s+EXISTS\s+)`?[a-zA-Z0-9_]+`?/i', '${1}' . $db, $sql, 1);
    $sql = preg_replace('/(CREATE\s+DATABASE\s+)`?[a-zA-Z0-9_]+`?/i', '${1}' . $db, $sql, 1);
    $sql = preg_replace('/^(\s*USE\s+)`?[a-zA-Z0-9_]+`?(\s*;)/im', '${1}' . $db . '${2}', $sql, 1);
    file_put_contents($file, $sql);
    return 'database.sql gebruikt nu database "' . $db . '". Importeer hem nog wel. Reservekopie: ' . $backup;
}

// Fix: database.sql importeren (maakt de database opnieuw: alle testdata wordt gereset)
function ac_fix_import($root)
{
    $config = ac_config($root);
    $file = $root . '/database.sql';
    if (!is_file($file)) {
        return 'Niet gedaan: er is geen database.sql.';
    }
    $conn = ac_connect($config, false);
    if ($conn->connect_errno) {
        return 'Niet gedaan: geen verbinding met MySQL (' . $conn->connect_error . '). Start MySQL in XAMPP.';
    }
    $sql = file_get_contents($file);
    if (!$conn->multi_query($sql)) {
        return 'Importeren mislukt: ' . $conn->error;
    }
    do {
        $res = $conn->store_result();
        if ($res) {
            $res->free();
        }
        if (!$conn->more_results()) {
            break;
        }
    } while ($conn->next_result());
    if ($conn->errno) {
        return 'Importeren gestopt met een fout: ' . $conn->error . ' (alles daarvoor is wel gedaan). Los de fout in database.sql op en importeer opnieuw.';
    }
    $conn->close();
    return 'database.sql is geïmporteerd: database "' . $config['DB_NAME'] . '" is opnieuw gemaakt met de dummy data.';
}

// Van animal_type naar AnimalType (voor de hoofdletter-versie)
function ac_camel($word)
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
}

// Fix: hernoemen (zelfde als Fase 2, maar automatisch)
// $words = array('ding_s','ding_p','lijst_s','lijst_p','actie_s','actie_p','app_name','db_name')
function ac_fix_rename($root, $words, $stamp)
{
    if (ac_is_kit($root)) {
        return 'Niet gedaan: de kit zelf hernoem je nooit. Kopieer de kit eerst.';
    }
    $rows = array(
        array('items', 'item', 'ding'),
        array('categories', 'category', 'lijst'),
        array('orders', 'order', 'actie'),
    );
    $forbidden = array('cart', 'carts', 'user', 'users', 'log', 'logs', 'status', 'name', 'price', 'item', 'items', 'category', 'categories', 'order', 'orders');
    $pairs = array();
    foreach ($rows as $row) {
        $s = strtolower(trim($words[$row[2] . '_s']));
        $p = strtolower(trim($words[$row[2] . '_p']));
        if ($s === '' && $p === '') {
            continue; // deze rij overslaan
        }
        if (!preg_match('/^[a-z][a-z_]*$/', $s) || !preg_match('/^[a-z][a-z_]*$/', $p)) {
            return 'Niet gedaan: bij ' . $row[2] . ' alleen kleine letters en _ gebruiken (bv. animal en animals).';
        }
        if ($s === $p) {
            return 'Niet gedaan: bij ' . $row[2] . ' zijn enkelvoud en meervoud hetzelfde ("' . $s . '"). Kies een woord met een ander meervoud.';
        }
        if (in_array($s, $forbidden, true) || in_array($p, $forbidden, true)) {
            return 'Niet gedaan: "' . $s . '" of "' . $p . '" bestaat al in de kit. Kies een ander woord.';
        }
        // volgorde: meervoud eerst, dan enkelvoud, dan met hoofdletter
        $pairs[] = array($row[0], $p);
        $pairs[] = array($row[1], $s);
        $pairs[] = array(ucfirst($row[0]), ac_camel($p));
        $pairs[] = array(ucfirst($row[1]), ac_camel($s));
    }
    $app_name = trim($words['app_name']);
    $db_name = trim($words['db_name']);
    if ($db_name !== '' && !preg_match('/^[a-z0-9_]+$/', $db_name)) {
        return 'Niet gedaan: de databasenaam mag alleen kleine letters, cijfers en _ bevatten.';
    }
    if (count($pairs) === 0 && $app_name === '' && $db_name === '') {
        return 'Niet gedaan: je hebt niets ingevuld.';
    }

    $files = ac_files($root, array('php', 'sql'));
    $backup = ac_backup($root, $files, $stamp);

    // 1. vervangen in *.php en *.sql (nooit in css of js)
    $changed = 0;
    $replaced = 0;
    foreach ($files as $path) {
        $text = file_get_contents($path);
        $new = $text;
        foreach ($pairs as $pair) {
            $replaced += substr_count($new, $pair[0]);
            $new = str_replace($pair[0], $pair[1], $new);
        }
        if ($new !== $text) {
            file_put_contents($path, $new);
            $changed++;
        }
    }

    // 2. bestanden hernoemen
    $renamed = array();
    foreach ($files as $path) {
        $name = basename($path);
        $new_name = $name;
        foreach ($pairs as $pair) {
            $new_name = str_replace($pair[0], $pair[1], $new_name);
        }
        if ($new_name !== $name) {
            rename($path, dirname($path) . DIRECTORY_SEPARATOR . $new_name);
            $renamed[] = $name . ' → ' . $new_name;
        }
    }

    // 3. app-naam en databasenaam
    $config_file = $root . '/config/config.php';
    $config_text = file_get_contents($config_file);
    if ($app_name !== '') {
        $config_text = preg_replace("/define\('APP_NAME',\s*'[^']*'\)/", "define('APP_NAME', '" . str_replace("'", '', $app_name) . "')", $config_text);
    }
    if ($db_name !== '') {
        $config_text = preg_replace("/define\('DB_NAME',\s*'[^']*'\)/", "define('DB_NAME', '" . $db_name . "')", $config_text);
    }
    file_put_contents($config_file, $config_text);
    $extra = '';
    if ($db_name !== '') {
        $extra = ' ' . ac_fix_dbname($root, $stamp . '-db');
    }

    $renamed_text = 'geen';
    if (count($renamed) > 0) {
        $renamed_text = implode(', ', $renamed);
    }
    return 'Hernoemd: ' . $replaced . ' keer vervangen in ' . $changed . ' bestanden. Bestanden hernoemd: '
        . $renamed_text . '.' . $extra
        . ' Reservekopie: ' . $backup . '. Importeer nu database.sql (knop) en test.';
}
