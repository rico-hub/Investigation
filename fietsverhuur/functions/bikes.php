<?php
// =====================================================================
// functions/bikes.php - CRUD voor fietsen + filters/zoeken/sorteren
//
// De kolommen van bikes:
//   bike_type_id (dropdown uit database), name (tekst), frame_number (tekst, uniek),
//   price (prijs per dag), status (vaste lijst), deleted_at (soft delete)
// =====================================================================

// AANPASSEN: toegestane statussen (moet gelijk zijn aan de ENUM in database.sql)
function bike_statuses()
{
    return array('beschikbaar', 'in_reparatie', 'uit_gebruik');
}

// ---------------------------------------------------------------------
// READ - lijst met filters
// ---------------------------------------------------------------------

// Alle filters uit de URL lezen. Lege string = filter staat uit.
function bikes_read_filters()
{
    return array(
        'search'      => get_param('search'),       // searchbar
        'status'      => get_param('status'),       // statusfilter (dropdown)
        'bike_type_id' => get_param('bike_type_id'),  // dropdown uit database
        'sort'        => get_param('sort'),         // sorteren op kolom
        'dir'         => get_param('dir'),
    );
}

function bikes_get_all($conn, $filters)
{
    // AANPASSEN: de kolommen die je in de lijst wilt zien
    $sql = 'SELECT i.id, i.name, i.frame_number, i.price, i.status, c.name AS bike_type_name
            FROM bikes i
            INNER JOIN bike_types c ON c.id = i.bike_type_id
            WHERE i.deleted_at IS NULL';
    $types = '';
    $params = array();

    // AANPASSEN: in welke kolommen de searchbar zoekt (evenveel ? als $params[] regels)
    if ($filters['search'] !== '') {
        $sql .= ' AND (i.name LIKE ? OR i.frame_number LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    // StatusFilter: alleen als het een toegestane status is
    if ($filters['status'] !== '' && in_array($filters['status'], bike_statuses(), true)) {
        $sql .= ' AND i.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    // Categorie dropdown
    if (is_valid_id($filters['bike_type_id'])) {
        $sql .= ' AND i.bike_type_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['bike_type_id'];
    }

    // AANPASSEN: op welke kolommen je mag sorteren. 'naam in de URL' => 'kolom in SQL'
    // (kolomnamen kun je NIET met ? binden, daarom deze whitelist)
    $columns = array(
        'id'       => 'i.id',
        'name'     => 'i.name',
        'price'    => 'i.price',
        'frame'    => 'i.frame_number',
        'bike_type' => 'c.name',
        'status'   => 'i.status',
    );
    $rental = 'i.id';
    if (isset($columns[$filters['sort']])) {
        $rental = $columns[$filters['sort']];
    }
    $direction = 'ASC';
    if ($filters['dir'] === 'desc') {
        $direction = 'DESC';
    }
    $sql .= ' ORDER BY ' . $rental . ' ' . $direction;

    return db_select_all($conn, $sql, $types, $params);
}

// TotalCount: alle (niet verwijderde) bikes, zonder filters
function bikes_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM bikes WHERE deleted_at IS NULL', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['total'];
}

// 1 bike ophalen. $include_deleted = true om ook de prullenbak te doorzoeken.
// Geeft array, null (niet gevonden) of false (db fout).
function bike_find($conn, $id, $include_deleted)
{
    // AANPASSEN: alle kolommen die het formulier nodig heeft
    $sql = 'SELECT id, bike_type_id, name, frame_number, price, status, deleted_at FROM bikes WHERE id = ?';
    if (!$include_deleted) {
        $sql .= ' AND deleted_at IS NULL';
    }
    return db_select_one($conn, $sql, 'i', array($id));
}

// Prullenbak (soft deleted bikes)
function bikes_get_deleted($conn)
{
    return db_select_all(
        $conn,
        'SELECT i.id, i.name, i.deleted_at, c.name AS bike_type_name
         FROM bikes i
         INNER JOIN bike_types c ON c.id = i.bike_type_id
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
function bike_empty_data()
{
    return array(
        'name' => '',
        'bike_type_id' => '',
        'frame_number' => '',
        'price' => '',
        'status' => 'beschikbaar',
    );
}

// AANPASSEN: waarden uit het verzonden formulier (zelfde keys als hierboven)
function bike_form_data()
{
    return array(
        'name' => post_param('name'),
        'bike_type_id' => post_param('bike_type_id'),
        'frame_number' => strtoupper(post_param('frame_number')), // gz-1001 -> GZ-1001
        'price' => str_replace(',', '.', post_param('price')), // 12,50 -> 12.50
        'status' => post_param('status'),
    );
}

// AANPASSEN: alle checks voor een bike (Error-code 420). true = goed.
function bike_validate($conn, $data)
{
    // tekst: verplicht + maximale lengte (zelfde als VARCHAR in de database)
    if ($data['name'] === '') {
        return function_error('Naam is verplicht.');
    }
    if (mb_strlen($data['name']) > 150) {
        return function_error('Naam mag maximaal 150 tekens zijn.');
    }

    // framenummer: verplicht, max 20 tekens
    if ($data['frame_number'] === '') {
        return function_error('Framenummer is verplicht.');
    }
    if (mb_strlen($data['frame_number']) > 20) {
        return function_error('Framenummer mag maximaal 20 tekens zijn.');
    }

    // dropdown uit de database: geldig id + bestaat het echt?
    if (!is_valid_id($data['bike_type_id'])) {
        return function_error('Kies een type fiets.');
    }
    if (!bike_type_exists($conn, (int) $data['bike_type_id'])) {
        return function_error('Dit type fiets bestaat niet.');
    }

    // bedrag: verplicht, een getal, niet negatief
    if ($data['price'] === '' || !is_numeric($data['price'])) {
        return function_error('Prijs moet een getal zijn.');
    }
    if ((float) $data['price'] < 0 || (float) $data['price'] > 999999.99) {
        return function_error('Prijs moet tussen 0 en 999999,99 liggen.');
    }

    // vaste lijst
    if (!in_array($data['status'], bike_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    return true;
}

// Bestaat dit framenummer al bij een ANDERE fiets? (UNIQUE in de database, hier een nette 420)
// $except_id = id van de fiets die je wijzigt (0 bij toevoegen)
function bike_frame_taken($conn, $frame_number, $except_id)
{
    $row = db_select_one(
        $conn,
        'SELECT id FROM bikes WHERE frame_number = ? AND id <> ?',
        'si',
        array($frame_number, $except_id)
    );
    if ($row === false) {
        return true; // database fout: liever niet opslaan (410 staat al in de flash)
    }
    if ($row !== null) {
        return true;
    }
    return false;
}

// ---------------------------------------------------------------------
// CREATE - geeft nieuw id of false
// ---------------------------------------------------------------------
function bike_create($conn, $data)
{
    if (!bike_validate($conn, $data)) {
        return false;
    }
    if (bike_frame_taken($conn, $data['frame_number'], 0)) {
        return function_error('Framenummer ' . $data['frame_number'] . ' bestaat al.');
    }

    // AANPASSEN: kolommen, vraagtekens, types en waarden. TEL ZE: alle 4 even veel!
    //   kolommen: bike_type_id, name, frame_number, price, status = 5
    //   ?       : 5      types: 'issds' = 5 letters (i = heel getal, s = tekst, d = kommagetal)
    return db_insert(
        $conn,
        'INSERT INTO bikes (bike_type_id, name, frame_number, price, status) VALUES (?, ?, ?, ?, ?)',
        'issds',
        array((int) $data['bike_type_id'], $data['name'], $data['frame_number'], (float) $data['price'], $data['status'])
    );
}

// ---------------------------------------------------------------------
// UPDATE - geeft true of false
// ---------------------------------------------------------------------
function bike_update($conn, $id, $data)
{
    $bike = bike_find($conn, $id, false);
    if ($bike === false) {
        return false;
    }
    if ($bike === null) {
        return function_error('Fiets niet gevonden.');
    }

    if (!bike_validate($conn, $data)) {
        return false;
    }
    if (bike_frame_taken($conn, $data['frame_number'], $id)) {
        return function_error('Framenummer ' . $data['frame_number'] . ' bestaat al.');
    }

    // AANPASSEN: zelfde als bij CREATE, plus het id als LAATSTE (voor WHERE id = ?)
    $result = db_change(
        $conn,
        'UPDATE bikes SET bike_type_id = ?, name = ?, frame_number = ?, price = ?, status = ? WHERE id = ?',
        'issdsi',
        array((int) $data['bike_type_id'], $data['name'], $data['frame_number'], (float) $data['price'], $data['status'], $id)
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
function bike_soft_delete($conn, $id)
{
    $bike = bike_find($conn, $id, false);
    if ($bike === false) {
        return false;
    }
    if ($bike === null) {
        return function_error('Fiets niet gevonden of al verwijderd.');
    }

    $result = db_change($conn, 'UPDATE bikes SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

// Terugzetten uit de prullenbak
function bike_restore($conn, $id)
{
    $result = db_change($conn, 'UPDATE bikes SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Fiets staat niet in de prullenbak.');
    }
    return true;
}

// HARD delete: rij echt weg. Kan alleen als er GEEN bestellingen aan hangen (FK RESTRICT).
function bike_hard_delete($conn, $id)
{
    // Check 1 (420): eerst soft delete, dan pas hard
    $bike = bike_find($conn, $id, true);
    if ($bike === false) {
        return false;
    }
    if ($bike === null) {
        return function_error('Fiets niet gevonden.');
    }
    if ($bike['deleted_at'] === null) {
        return function_error('Zet de fiets eerst in de prullenbak.');
    }

    // Check 2 (420): hangen er nog rijen aan in de child tabel?
    // AANPASSEN: de child tabel + kolom die naar bikes wijst (hier: rentals.bike_id)
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM rentals WHERE bike_id = ?', 'i', array($id));
    if ($row === false) {
        return false;
    }
    if ((int) $row['total'] > 0) {
        return function_error('Deze fiets is ' . $row['total'] . ' keer verhuurd en kan niet definitief verwijderd worden.');
    }

    // De DELETE zelf, uitgeschreven ZONDER helper: zo vang je de foreign key fout (1451) af
    $stmt = $conn->prepare('DELETE FROM bikes WHERE id = ?');
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
            return database_error('Kan niet verwijderen: er hangen nog verhuringen aan deze fiets', $error);
        }
        return database_error('Verwijderen mislukt', $error);
    }

    $stmt->close();
    return true;
}
