<?php
// =====================================================================
// functions/animals.php - CRUD voor animals (dieren) + filters/zoeken/sorteren
//
// De kolommen van animals:
//   animal_type_id (dropdown uit database), name (tekst), age (leeftijd, heel getal),
//   status (vaste lijst), deleted_at (soft delete)
// =====================================================================

// AANPASSEN: toegestane statussen (moet gelijk zijn aan de ENUM in database.sql)
function animal_statuses()
{
    return array('beschikbaar', 'gereserveerd', 'geadopteerd');
}

// ---------------------------------------------------------------------
// READ - lijst met filters
// ---------------------------------------------------------------------

// Alle filters uit de URL lezen. Lege string = filter staat uit.
function animals_read_filters()
{
    return array(
        'search'      => get_param('search'),       // searchbar
        'status'      => get_param('status'),       // statusfilter (dropdown)
        'animal_type_id' => get_param('animal_type_id'),  // dropdown uit database
        'sort'        => get_param('sort'),         // sorteren op kolom
        'dir'         => get_param('dir'),
    );
}

function animals_get_all($conn, $filters)
{
    // AANPASSEN: de kolommen die je in de lijst wilt zien
    $sql = 'SELECT i.id, i.name, i.age, i.status, c.name AS animal_type_name
            FROM animals i
            INNER JOIN animal_types c ON c.id = i.animal_type_id
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
    if ($filters['status'] !== '' && in_array($filters['status'], animal_statuses(), true)) {
        $sql .= ' AND i.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    // Categorie dropdown
    if (is_valid_id($filters['animal_type_id'])) {
        $sql .= ' AND i.animal_type_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['animal_type_id'];
    }

    // AANPASSEN: op welke kolommen je mag sorteren. 'naam in de URL' => 'kolom in SQL'
    // (kolomnamen kun je NIET met ? binden, daarom deze whitelist)
    $columns = array(
        'id'       => 'i.id',
        'name'     => 'i.name',
        'age'      => 'i.age',
        'animal_type' => 'c.name',
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

// TotalCount: alle (niet verwijderde) animals, zonder filters
function animals_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM animals WHERE deleted_at IS NULL', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['total'];
}

// 1 animal ophalen. $include_deleted = true om ook de prullenbak te doorzoeken.
// Geeft array, null (niet gevonden) of false (db fout).
function animal_find($conn, $id, $include_deleted)
{
    // AANPASSEN: alle kolommen die het formulier nodig heeft
    $sql = 'SELECT id, animal_type_id, name, age, status, deleted_at FROM animals WHERE id = ?';
    if (!$include_deleted) {
        $sql .= ' AND deleted_at IS NULL';
    }
    return db_select_one($conn, $sql, 'i', array($id));
}

// Prullenbak (soft deleted animals)
function animals_get_deleted($conn)
{
    return db_select_all(
        $conn,
        'SELECT i.id, i.name, i.deleted_at, c.name AS animal_type_name
         FROM animals i
         INNER JOIN animal_types c ON c.id = i.animal_type_id
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
function animal_empty_data()
{
    return array(
        'name' => '',
        'animal_type_id' => '',
        'age' => '',
        'status' => 'beschikbaar',
    );
}

// AANPASSEN: waarden uit het verzonden formulier (zelfde keys als hierboven)
function animal_form_data()
{
    return array(
        'name' => post_param('name'),
        'animal_type_id' => post_param('animal_type_id'),
        'age' => post_param('age'),
        'status' => post_param('status'),
    );
}

// AANPASSEN: alle checks voor een animal (Error-code 420). true = goed.
function animal_validate($conn, $data)
{
    // tekst: verplicht + maximale lengte (zelfde als VARCHAR in de database)
    if ($data['name'] === '') {
        return function_error('Naam is verplicht.');
    }
    if (mb_strlen($data['name']) > 150) {
        return function_error('Naam mag maximaal 150 tekens zijn.');
    }

    // dropdown uit de database: geldig id + bestaat het echt?
    if (!is_valid_id($data['animal_type_id'])) {
        return function_error('Kies een diersoort.');
    }
    if (!animal_type_exists($conn, (int) $data['animal_type_id'])) {
        return function_error('Deze diersoort bestaat niet.');
    }

    // leeftijd: verplicht, alleen cijfers (heel getal), 0 t/m 40
    if ($data['age'] === '' || !ctype_digit($data['age'])) {
        return function_error('Leeftijd moet een heel getal zijn.');
    }
    if ((int) $data['age'] > 40) {
        return function_error('Leeftijd moet tussen 0 en 40 jaar liggen.');
    }

    // vaste lijst
    if (!in_array($data['status'], animal_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    return true;
}

// ---------------------------------------------------------------------
// CREATE - geeft nieuw id of false
// ---------------------------------------------------------------------
function animal_create($conn, $data)
{
    if (!animal_validate($conn, $data)) {
        return false;
    }

    // AANPASSEN: kolommen, vraagtekens, types en waarden. TEL ZE: alle 4 even veel!
    //   kolommen: animal_type_id, name, age, status = 4
    //   ?       : 4      types: 'isis' = 4 letters (i = heel getal, s = tekst)
    return db_insert(
        $conn,
        'INSERT INTO animals (animal_type_id, name, age, status) VALUES (?, ?, ?, ?)',
        'isis',
        array((int) $data['animal_type_id'], $data['name'], (int) $data['age'], $data['status'])
    );
}

// ---------------------------------------------------------------------
// UPDATE - geeft true of false
// ---------------------------------------------------------------------
function animal_update($conn, $id, $data)
{
    $animal = animal_find($conn, $id, false);
    if ($animal === false) {
        return false;
    }
    if ($animal === null) {
        return function_error('Dier niet gevonden.');
    }

    if (!animal_validate($conn, $data)) {
        return false;
    }

    // AANPASSEN: zelfde als bij CREATE, plus het id als LAATSTE (voor WHERE id = ?)
    $result = db_change(
        $conn,
        'UPDATE animals SET animal_type_id = ?, name = ?, age = ?, status = ? WHERE id = ?',
        'isisi',
        array((int) $data['animal_type_id'], $data['name'], (int) $data['age'], $data['status'], $id)
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
function animal_soft_delete($conn, $id)
{
    $animal = animal_find($conn, $id, false);
    if ($animal === false) {
        return false;
    }
    if ($animal === null) {
        return function_error('Dier niet gevonden of al verwijderd.');
    }

    $result = db_change($conn, 'UPDATE animals SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

// Terugzetten uit de prullenbak
function animal_restore($conn, $id)
{
    $result = db_change($conn, 'UPDATE animals SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Dier staat niet in de prullenbak.');
    }
    return true;
}

// HARD delete: rij echt weg. Kan alleen als er GEEN bestellingen aan hangen (FK RESTRICT).
function animal_hard_delete($conn, $id)
{
    // Check 1 (420): eerst soft delete, dan pas hard
    $animal = animal_find($conn, $id, true);
    if ($animal === false) {
        return false;
    }
    if ($animal === null) {
        return function_error('Dier niet gevonden.');
    }
    if ($animal['deleted_at'] === null) {
        return function_error('Zet het dier eerst in de prullenbak.');
    }

    // Check 2 (420): hangen er nog rijen aan in de child tabel?
    // AANPASSEN: de child tabel + kolom die naar animals wijst (hier: appointments.animal_id)
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM appointments WHERE animal_id = ?', 'i', array($id));
    if ($row === false) {
        return false;
    }
    if ((int) $row['total'] > 0) {
        return function_error('Dit dier heeft ' . $row['total'] . ' afspraak/afspraken en kan niet definitief verwijderd worden.');
    }

    // De DELETE zelf, uitgeschreven ZONDER helper: zo vang je de foreign key fout (1451) af
    $stmt = $conn->prepare('DELETE FROM animals WHERE id = ?');
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
            return database_error('Kan niet verwijderen: er hangen nog gegevens aan dit dier', $error);
        }
        return database_error('Verwijderen mislukt', $error);
    }

    $stmt->close();
    return true;
}
