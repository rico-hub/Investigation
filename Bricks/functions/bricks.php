<?php
// =====================================================================
// functions/bricks.php - lijsten, checks en queries voor de bouwstenen
// Logica staat HIER, de pagina's tonen alleen.
// =====================================================================

// ---------------------------------------------------------------------
// VASTE LIJSTEN (1 plek: voor de dropdown EN voor de check)
// ---------------------------------------------------------------------

// Zelfde waarden als de ENUM in database.sql
function priorities()
{
    return array('laag', 'normaal', 'hoog');
}

// key = wat in de database staat, waarde = wat de gebruiker ziet
function priority_labels()
{
    return array(
        'laag' => 'Lage prioriteit',
        'normaal' => 'Normaal',
        'hoog' => 'Spoed!',
    );
}

// Code -> tekst. Onbekende code? Dan de code zelf.
function priority_label($value)
{
    $labels = priority_labels();
    if (isset($labels[$value])) {
        return $labels[$value];
    }
    return $value;
}

function item_statuses()
{
    return array('actief', 'inactief', 'uitverkocht');
}

// array(1 => ...) = de lijst begint bij 1, dus januari = 1
function months()
{
    return array(1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli',
        'augustus', 'september', 'oktober', 'november', 'december');
}

// ---------------------------------------------------------------------
// LIJSTEN UIT DE DATABASE
// ---------------------------------------------------------------------

function categories_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM categories ORDER BY name', '', array());
}

function tags_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM tags ORDER BY name', '', array());
}

// Voor de dropdown met groepen: eerst sorteren op de groep
function items_grouped($conn)
{
    return db_select_all($conn,
        'SELECT i.id, i.name, c.name AS category_name
         FROM items i
         INNER JOIN categories c ON c.id = i.category_id
         ORDER BY c.name, i.name', '', array());
}

function category_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM categories WHERE id = ?', 'i', array((int) $id));
    if ($row === false) {
        return false;
    }
    if ($row === null) {
        return function_error('Deze categorie bestaat niet.');
    }
    return true;
}

// Alle items + categorie + tags. Filter: priority ('' = alle)
function items_get_all($conn, $filters)
{
    $sql = 'SELECT i.*, c.name AS category_name,
                   GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR \', \') AS tag_names
            FROM items i
            INNER JOIN categories c ON c.id = i.category_id
            LEFT JOIN item_tags it ON it.item_id = i.id
            LEFT JOIN tags t ON t.id = it.tag_id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    // Dropdown "Alle": alleen filteren als het een geldige waarde is
    if (array_key_exists($filters['priority'], priority_labels())) {
        $sql .= ' AND i.priority = ?';
        $types .= 's';
        $params[] = $filters['priority'];
    }

    $sql .= ' GROUP BY i.id ORDER BY i.id';
    return db_select_all($conn, $sql, $types, $params);
}

function item_find($conn, $id)
{
    return db_select_one($conn,
        'SELECT i.*, c.name AS category_name
         FROM items i INNER JOIN categories c ON c.id = i.category_id
         WHERE i.id = ?', 'i', array((int) $id));
}

// Bouwsteen 10 en knoppen: status van 1 item veranderen
function item_set_status($conn, $id, $status)
{
    if (!is_valid_id($id)) {
        return function_error('Ongeldig item.');
    }
    if (!in_array($status, item_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    $item = item_find($conn, $id);
    if ($item === false) {
        return false;
    }
    if ($item === null) {
        return function_error('Item bestaat niet.');
    }
    $changed = db_change($conn, 'UPDATE items SET status = ? WHERE id = ?', 'si', array($status, (int) $id));
    if ($changed === false) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------
// DEMO 1: dropdowns.php
// ---------------------------------------------------------------------

function dropdowns_empty_data()
{
    return array(
        'priority_simpel' => '',
        'priority' => '',
        'priority_label' => '',
        'category_id' => '',
        'quantity' => '1',
        'year' => date('Y'),
        'month' => date('n'),
        'is_active' => '1',
        'item_id' => '',
    );
}

function dropdowns_form_data()
{
    $data = array();
    foreach (dropdowns_empty_data() as $key => $default) {
        $data[$key] = post_param($key);
    }
    return $data;
}

// Stopt bij de EERSTE fout (Error-code 420), net als in een echte app
function dropdowns_validate($conn, $data)
{
    if (!in_array($data['priority_simpel'], priorities(), true)) {
        return function_error('1. Vaste waarden in HTML: kies een prioriteit.');
    }
    if (!in_array($data['priority'], priorities(), true)) {
        return function_error('2. Vaste waarden die onthouden: kies een prioriteit.');
    }
    if (!array_key_exists($data['priority_label'], priority_labels())) {
        return function_error('3. Waarde en tekst verschillend: kies een prioriteit.');
    }
    if (!is_valid_id($data['category_id'])) {
        return function_error('4. Uit de database: kies een categorie.');
    }
    if (!category_exists($conn, $data['category_id'])) {
        return false;
    }
    if (!ctype_digit($data['quantity']) || (int) $data['quantity'] < 1 || (int) $data['quantity'] > 10) {
        return function_error('5. Getallen: kies een aantal van 1 tot 10.');
    }
    if (!ctype_digit($data['year']) || (int) $data['year'] > (int) date('Y') || (int) $data['year'] < (int) date('Y') - 5) {
        return function_error('6. Jaren: ongeldig jaar.');
    }
    if (!array_key_exists((int) $data['month'], months())) {
        return function_error('6. Maanden: ongeldige maand.');
    }
    if ($data['is_active'] !== '0' && $data['is_active'] !== '1') {
        return function_error('7. Ja / Nee: kies ja of nee.');
    }
    if (!is_valid_id($data['item_id'])) {
        return function_error('9. Groepen: kies een item.');
    }
    return true;
}

// ---------------------------------------------------------------------
// DEMO 2: velden.php
// ---------------------------------------------------------------------

function velden_empty_data()
{
    return array(
        'priority_radio' => '',
        'is_featured' => '0',
        'tags' => array(),
        'stock' => '0',
        'price' => '',
        'start_date' => '',
        'start_at' => '',
        'name' => '',
        'email' => '',
        'description' => '',
        'secret_id' => '42',
    );
}

function velden_form_data()
{
    // 1 checkbox: niet aangevinkt = niet verstuurd, dus zelf '0' maken
    $is_featured = '0';
    if (post_param('is_featured') === '1') {
        $is_featured = '1';
    }

    return array(
        'priority_radio' => post_param('priority_radio'),
        'is_featured' => $is_featured,
        'tags' => post_array('tags'),
        'stock' => post_param('stock'),
        'price' => str_replace(',', '.', post_param('price')),   // 9,99 -> 9.99
        'start_date' => post_param('start_date'),
        'start_at' => post_param('start_at'),
        'name' => post_param('name'),
        'email' => post_param('email'),
        'description' => post_param('description'),
        'secret_id' => post_param('secret_id'),
    );
}

function velden_validate($data)
{
    if (!array_key_exists($data['priority_radio'], priority_labels())) {
        return function_error('Radio: kies een prioriteit.');
    }
    foreach ($data['tags'] as $tag_id) {
        if (!is_valid_id($tag_id)) {
            return function_error('Checkboxes: ongeldige tag.');
        }
    }
    if (!ctype_digit($data['stock']) || (int) $data['stock'] > 9999) {
        return function_error('Getal: voorraad moet 0 tot 9999 zijn.');
    }
    if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
        return function_error('Bedrag: prijs moet een getal van 0 of meer zijn.');
    }
    if (!is_valid_date($data['start_date'])) {
        return function_error('Datum: kies een geldige datum.');
    }
    if ($data['start_date'] < date('Y-m-d')) {
        return function_error('Datum: mag niet in het verleden liggen.');
    }
    if ($data['start_at'] !== '' && !is_valid_datetime_local($data['start_at'])) {
        return function_error('Datum + tijd: ongeldige waarde.');
    }
    if (mb_strlen($data['name']) < 3 || mb_strlen($data['name']) > 150) {
        return function_error('Tekst: naam moet 3 tot 150 tekens zijn.');
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return function_error('E-mail: ongeldig e-mailadres.');
    }
    if (strlen(post_raw('password')) < 8) {
        return function_error('Wachtwoord: minimaal 8 tekens. (Het veld is weer leeg: wachtwoorden zet je nooit terug.)');
    }
    if (mb_strlen($data['description']) > 1000) {
        return function_error('Lange tekst: maximaal 1000 tekens.');
    }
    return true;
}

// ---------------------------------------------------------------------
// DEMO 3: opslaan.php (alles samen, echt in de database)
// ---------------------------------------------------------------------

function item_empty_data()
{
    return array(
        'name' => '',
        'category_id' => '',
        'priority' => 'normaal',
        'is_active' => '1',
        'price' => '',
        'stock' => '0',
        'start_date' => '',
        'description' => '',
        'tags' => array(),
    );
}

// STUKJE 2: inlezen met precies dezelfde names als in de HTML
function item_form_data()
{
    return array(
        'name' => post_param('name'),
        'category_id' => post_param('category_id'),
        'priority' => post_param('priority'),
        'is_active' => post_param('is_active'),
        'price' => str_replace(',', '.', post_param('price')),
        'stock' => post_param('stock'),
        'start_date' => post_param('start_date'),
        'description' => post_param('description'),
        'tags' => post_array('tags'),
    );
}

// STUKJE 3a: alle checks (Error-code 420)
function item_validate($conn, $data)
{
    if ($data['name'] === '') {
        return function_error('Naam is verplicht.');
    }
    if (mb_strlen($data['name']) > 150) {
        return function_error('Naam mag maximaal 150 tekens zijn.');
    }
    if (!is_valid_id($data['category_id'])) {
        return function_error('Kies een categorie.');
    }
    if (!category_exists($conn, $data['category_id'])) {
        return false;
    }
    if (!array_key_exists($data['priority'], priority_labels())) {
        return function_error('Kies een geldige prioriteit.');
    }
    if ($data['is_active'] !== '0' && $data['is_active'] !== '1') {
        return function_error('Kies ja of nee bij Actief.');
    }
    if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
        return function_error('Prijs moet een getal van 0 of meer zijn.');
    }
    if (!ctype_digit($data['stock']) || (int) $data['stock'] > 9999) {
        return function_error('Voorraad moet 0 tot 9999 zijn.');
    }
    if ($data['start_date'] !== '' && !is_valid_date($data['start_date'])) {
        return function_error('Ongeldige startdatum.');
    }
    foreach ($data['tags'] as $tag_id) {
        if (!is_valid_id($tag_id)) {
            return function_error('Ongeldige tag.');
        }
    }
    return true;
}

// STUKJE 3b: opslaan (item + tags in 1 transactie). Geeft nieuw id of false.
function item_create($conn, $data)
{
    if (!item_validate($conn, $data)) {
        return false;
    }

    // Lege datum = NULL in de database (niet '')
    $start_date = null;
    if ($data['start_date'] !== '') {
        $start_date = $data['start_date'];
    }

    $conn->begin_transaction();

    $item_id = db_insert($conn,
        'INSERT INTO items (category_id, name, description, priority, is_active, price, stock, start_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        'isssidis',
        array((int) $data['category_id'], $data['name'], $data['description'], $data['priority'],
            (int) $data['is_active'], (float) $data['price'], (int) $data['stock'], $start_date));
    if ($item_id === false) {
        $conn->rollback();
        return false;
    }

    foreach ($data['tags'] as $tag_id) {
        $result = db_insert($conn, 'INSERT INTO item_tags (item_id, tag_id) VALUES (?, ?)', 'ii', array($item_id, (int) $tag_id));
        if ($result === false) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return $item_id;
}
