<?php
// =====================================================================
// functions/items.php - CRUD voor items + filters/zoeken/sorteren
//
// PUZZELSTUK. Zo maak je hem van jou:
//   1. Zoek & vervang (Match Case aan, alleen *.php, *.sql):
//        items -> products     item -> product     Items -> Products     Item -> Product
//      (en hernoem de bestanden item_*.php en functions/items.php)
//   2. Kolom erbij? Zoek in *.php op price (Ctrl + Shift + F). Elke plek die je vindt,
//      is een plek waar ook jouw kolom moet staan: zet hem er op dezelfde manier naast.
//      Kolom weg? Zoek op zijn naam en haal hem op elke plek weg.
//
// De kolommen van items in deze kit:
//   category_id (dropdown uit database), name (tekst), price (bedrag),
//   item_date (datum, mag leeg), status (vaste lijst), deleted_at (soft delete)
// =====================================================================

// AANPASSEN: toegestane statussen (moet gelijk zijn aan de ENUM in database.sql)
function item_statuses()
{
    return array('actief', 'inactief', 'uitverkocht');
}

// ---------------------------------------------------------------------
// READ - lijst met filters
// ---------------------------------------------------------------------

// Alle filters uit de URL lezen. Lege string = filter staat uit.
function items_read_filters()
{
    return array(
        'search'      => get_param('search'),       // searchbar
        'status'      => get_param('status'),       // statusfilter (dropdown)
        'category_id' => get_param('category_id'),  // dropdown uit database
        'sort'        => get_param('sort'),         // sorteren op kolom
        'dir'         => get_param('dir'),
    );
}

function items_get_all($conn, $filters)
{
    // AANPASSEN: de kolommen die je in de lijst wilt zien
    $sql = 'SELECT i.id, i.name, i.price, i.item_date, i.status, c.name AS category_name
            FROM items i
            INNER JOIN categories c ON c.id = i.category_id
            WHERE i.deleted_at IS NULL';
    $types = '';
    $params = array();

    // AANPASSEN: in welke kolommen de searchbar zoekt (evenveel ? als $params[] regels)
    if ($filters['search'] !== '') {
        $sql .= ' AND (i.name LIKE ? OR c.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    // StatusFilter: alleen als het een toegestane status is
    if ($filters['status'] !== '' && in_array($filters['status'], item_statuses(), true)) {
        $sql .= ' AND i.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    // Categorie dropdown
    if (is_valid_id($filters['category_id'])) {
        $sql .= ' AND i.category_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['category_id'];
    }

    // AANPASSEN: op welke kolommen je mag sorteren. 'naam in de URL' => 'kolom in SQL'
    // (kolomnamen kun je NIET met ? binden, daarom deze whitelist)
    $columns = array(
        'id'       => 'i.id',
        'name'     => 'i.name',
        'price'    => 'i.price',
        'date'     => 'i.item_date',
        'category' => 'c.name',
        'status'   => 'i.status',
    );
    $sort_column = 'i.id';
    if (isset($columns[$filters['sort']])) {
        $sort_column = $columns[$filters['sort']];
    }
    $direction = 'ASC';
    if ($filters['dir'] === 'desc') {
        $direction = 'DESC';
    }
    $sql .= ' ORDER BY ' . $sort_column . ' ' . $direction;

    return db_select_all($conn, $sql, $types, $params);
}

// TotalCount: alle (niet verwijderde) items, zonder filters
function items_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM items WHERE deleted_at IS NULL', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['total'];
}

// 1 item ophalen. $include_deleted = true om ook de prullenbak te doorzoeken.
// Geeft array, null (niet gevonden) of false (db fout).
function item_find($conn, $id, $include_deleted)
{
    // AANPASSEN: alle kolommen die het formulier nodig heeft
    $sql = 'SELECT id, category_id, name, price, item_date, status, deleted_at FROM items WHERE id = ?';
    if (!$include_deleted) {
        $sql .= ' AND deleted_at IS NULL';
    }
    return db_select_one($conn, $sql, 'i', array($id));
}

// Prullenbak (soft deleted items)
function items_get_deleted($conn)
{
    return db_select_all(
        $conn,
        'SELECT i.id, i.name, i.deleted_at, c.name AS category_name
         FROM items i
         INNER JOIN categories c ON c.id = i.category_id
         WHERE i.deleted_at IS NOT NULL
         ORDER BY i.deleted_at DESC',
        '',
        array()
    );
}

// ---------------------------------------------------------------------
// Formulier data
// ---------------------------------------------------------------------

// AANPASSEN: lege waarden voor een nieuw formulier (1 regel per veld)
function item_empty_data()
{
    return array(
        'name' => '',
        'category_id' => '',
        'price' => '',
        'item_date' => '',
        'status' => 'actief',
    );
}

// AANPASSEN: waarden uit het verzonden formulier (zelfde keys als hierboven)
function item_form_data()
{
    return array(
        'name' => post_param('name'),
        'category_id' => post_param('category_id'),
        'price' => str_replace(',', '.', post_param('price')), // 12,50 -> 12.50
        'item_date' => post_param('item_date'),
        'status' => post_param('status'),
    );
}

// AANPASSEN: alle checks voor een item (Error-code 420). true = goed.
function item_validate($conn, $data)
{
    // tekst: verplicht + maximale lengte (zelfde als VARCHAR in de database)
    if ($data['name'] === '') {
        return function_error('Naam is verplicht.');
    }
    if (mb_strlen($data['name']) > 150) {
        return function_error('Naam mag maximaal 150 tekens zijn.');
    }

    // dropdown uit de database: geldig id + bestaat het echt?
    if (!is_valid_id($data['category_id'])) {
        return function_error('Kies een categorie.');
    }
    if (!category_exists($conn, (int) $data['category_id'])) {
        return function_error('Deze categorie bestaat niet.');
    }

    // bedrag: verplicht, een getal, niet negatief
    if ($data['price'] === '' || !is_numeric($data['price'])) {
        return function_error('Prijs moet een getal zijn.');
    }
    if ((float) $data['price'] < 0 || (float) $data['price'] > 999999.99) {
        return function_error('Prijs moet tussen 0 en 999999,99 liggen.');
    }

    // datum: mag leeg, maar als hij is ingevuld moet hij echt bestaan
    if ($data['item_date'] !== '' && !is_valid_date($data['item_date'])) {
        return function_error('Datum is ongeldig.');
    }

    // vaste lijst
    if (!in_array($data['status'], item_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    return true;
}

// Lege datum -> NULL in de database
function item_date_or_null($date)
{
    if ($date === '') {
        return null;
    }
    return $date;
}

// ---------------------------------------------------------------------
// CREATE - geeft nieuw id of false
// ---------------------------------------------------------------------
function item_create($conn, $data)
{
    if (!item_validate($conn, $data)) {
        return false;
    }

    // AANPASSEN: kolommen, vraagtekens, types en waarden. TEL ZE: alle 4 even veel!
    //   kolommen: category_id, name, price, item_date, status = 5
    //   ?       : 5      types: 'isdss' = 5 letters (i = heel getal, s = tekst, d = kommagetal)
    return db_insert(
        $conn,
        'INSERT INTO items (category_id, name, price, item_date, status) VALUES (?, ?, ?, ?, ?)',
        'isdss',
        array((int) $data['category_id'], $data['name'], (float) $data['price'], item_date_or_null($data['item_date']), $data['status'])
    );
}

// ---------------------------------------------------------------------
// UPDATE - geeft true of false
// ---------------------------------------------------------------------
function item_update($conn, $id, $data)
{
    $item = item_find($conn, $id, false);
    if ($item === false) {
        return false;
    }
    if ($item === null) {
        return function_error('Item niet gevonden.');
    }

    if (!item_validate($conn, $data)) {
        return false;
    }

    // AANPASSEN: zelfde als bij CREATE, plus het id als LAATSTE (voor WHERE id = ?)
    $result = db_change(
        $conn,
        'UPDATE items SET category_id = ?, name = ?, price = ?, item_date = ?, status = ? WHERE id = ?',
        'isdssi',
        array((int) $data['category_id'], $data['name'], (float) $data['price'], item_date_or_null($data['item_date']), $data['status'], $id)
    );

    if ($result === false) {
        return false;
    }
    return true; // ook true als er niets veranderd is (affected_rows = 0)
}

// ---------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------

// SOFT delete: zet deleted_at. De rij blijft in de database.
function item_soft_delete($conn, $id)
{
    $item = item_find($conn, $id, false);
    if ($item === false) {
        return false;
    }
    if ($item === null) {
        return function_error('Item niet gevonden of al verwijderd.');
    }

    $result = db_change($conn, 'UPDATE items SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

// Terugzetten uit de prullenbak
function item_restore($conn, $id)
{
    $result = db_change($conn, 'UPDATE items SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Item staat niet in de prullenbak.');
    }
    return true;
}

// HARD delete: rij echt weg. Kan alleen als er GEEN bestellingen aan hangen (FK RESTRICT).
function item_hard_delete($conn, $id)
{
    // Check 1 (420): eerst soft delete, dan pas hard
    $item = item_find($conn, $id, true);
    if ($item === false) {
        return false;
    }
    if ($item === null) {
        return function_error('Item niet gevonden.');
    }
    if ($item['deleted_at'] === null) {
        return function_error('Zet het item eerst in de prullenbak.');
    }

    // Check 2 (420): hangen er nog rijen aan in de child tabel?
    // AANPASSEN: de child tabel + kolom die naar items wijst (hier: orders.item_id)
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM orders WHERE item_id = ?', 'i', array($id));
    if ($row === false) {
        return false;
    }
    if ((int) $row['total'] > 0) {
        return function_error('Dit item heeft ' . $row['total'] . ' bestelling(en) en kan niet definitief verwijderd worden.');
    }

    // De DELETE zelf, uitgeschreven ZONDER helper: zo vang je de foreign key fout (1451) af
    $stmt = $conn->prepare('DELETE FROM items WHERE id = ?');
    if (!$stmt) {
        return database_error('Verwijderen voorbereiden mislukt', $conn->error);
    }
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $errno = $stmt->errno;
        $error = $stmt->error;
        $stmt->close();

        // 1451 = "Cannot delete or update a parent row: a foreign key constraint fails"
        if ($errno === 1451) {
            return database_error('Kan niet verwijderen: er hangen nog gegevens aan dit item', $error);
        }
        return database_error('Verwijderen mislukt', $error);
    }

    $stmt->close();
    return true;
}
