<?php
function beurt_types()
{
    return array('APK-Keuring', 'Grote Beurt', 'Bandenwissel', 'Airco Service');
}

// ---------------------------------------------------------------------
// Werkplaats: hoeveel plekken zijn op een dag al aan beurten gegeven?
// ---------------------------------------------------------------------
function werkplaats_gereserveerd($conn, $datum)
{
    $row = db_select_one(
        $conn,
        'SELECT COALESCE(SUM(max_autos), 0) AS totaal FROM beurten WHERE datum = ?',
        's',
        array($datum)
    );
    if ($row === false) {
        return false;
    }
    return (int) $row['totaal'];
}

// Hoeveel plekken zijn er op die dag nog vrij voor een NIEUWE beurt?
function werkplaats_vrij($conn, $datum)
{
    $gereserveerd = werkplaats_gereserveerd($conn, $datum);
    if ($gereserveerd === false) {
        return false;
    }
    return WERKPLAATS_PLEKKEN - $gereserveerd;
}

// ---------------------------------------------------------------------
// READ - overzicht met filters
// ---------------------------------------------------------------------
function beurten_read_filters()
{
    return array(
        'search'  => get_param('search'),   // zoek op type of kenteken
        'type'    => get_param('type'),     // dropdown type onderhoud
        'periode' => get_param('periode'),  // komend / verleden
        'sort'    => get_param('sort'),
        'dir'     => get_param('dir'),
    );
}

function beurten_get_all($conn, $filters)
{
    $sql = 'SELECT b.id, b.type_onderhoud, b.datum, b.tijd, b.max_autos,
                   COUNT(a.id) AS geboekt,
                   GROUP_CONCAT(a.kenteken ORDER BY a.kenteken SEPARATOR \', \') AS kentekens,
                   (SELECT SUM(b2.max_autos) FROM beurten b2 WHERE b2.datum = b.datum) AS dag_gereserveerd
            FROM beurten b
            LEFT JOIN afspraken a ON a.beurt_id = b.id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    // Searchbar: type onderhoud OF een kenteken dat bij de beurt hoort
    if ($filters['search'] !== '') {
        $sql .= ' AND (b.type_onderhoud LIKE ? OR b.id IN (SELECT beurt_id FROM afspraken WHERE kenteken LIKE ?))';
        $like = '%' . $filters['search'] . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    if ($filters['type'] !== '' && in_array($filters['type'], beurt_types(), true)) {
        $sql .= ' AND b.type_onderhoud = ?';
        $types .= 's';
        $params[] = $filters['type'];
    }

    if ($filters['periode'] === 'komend') {
        $sql .= ' AND b.datum >= CURDATE()';
    } elseif ($filters['periode'] === 'verleden') {
        $sql .= ' AND b.datum < CURDATE()';
    }

    $sql .= ' GROUP BY b.id';

    // Sorteren: whitelist (kolomnamen kun je niet met ? binden)
    $columns = array(
        'id'      => 'b.id',
        'type'    => 'b.type_onderhoud',
        'datum'   => 'b.datum',
        'plekken' => '(b.max_autos - COUNT(a.id))',
    );
    $sort_column = 'b.datum';
    if (isset($columns[$filters['sort']])) {
        $sort_column = $columns[$filters['sort']];
    }
    $direction = 'ASC';
    if ($filters['dir'] === 'desc') {
        $direction = 'DESC';
    }
    $sql .= ' ORDER BY ' . $sort_column . ' ' . $direction . ', b.tijd ASC';

    return db_select_all($conn, $sql, $types, $params);
}

// TotalCount: alle beurten, zonder filters
function beurten_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS totaal FROM beurten', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['totaal'];
}

// 1 beurt ophalen, met het aantal geboekte auto's.
// Geeft array, null (niet gevonden) of false (db fout).
function beurt_find($conn, $id)
{
    return db_select_one(
        $conn,
        'SELECT b.id, b.type_onderhoud, b.datum, b.tijd, b.max_autos,
                (SELECT COUNT(*) FROM afspraken a WHERE a.beurt_id = b.id) AS geboekt
         FROM beurten b
         WHERE b.id = ?',
        'i',
        array($id)
    );
}

// Komende beurten waar nog plek is (voor de dropdown bij "+ Afspraak")
function beurten_met_plek($conn)
{
    return db_select_all(
        $conn,
        'SELECT b.id, b.type_onderhoud, b.datum, b.tijd, b.max_autos, COUNT(a.id) AS geboekt
         FROM beurten b
         LEFT JOIN afspraken a ON a.beurt_id = b.id
         WHERE b.datum >= CURDATE()
         GROUP BY b.id
         HAVING COUNT(a.id) < b.max_autos
         ORDER BY b.datum, b.tijd',
        '',
        array()
    );
}

// ---------------------------------------------------------------------
// CREATE
// ---------------------------------------------------------------------
function beurt_empty_data()
{
    return array(
        'type_onderhoud' => '',
        'datum'          => '',
        'tijd'           => '',
        'max_autos'      => '',
    );
}

function beurt_form_data()
{
    return array(
        'type_onderhoud' => post_param('type_onderhoud'),
        'datum'          => post_param('datum'),
        'tijd'           => post_param('tijd'),
        'max_autos'      => post_param('max_autos'),
    );
}

// Geeft het nieuwe id, of false.
function beurt_create($conn, $data)
{
    // 1. Checks (Error-code 420)
    if (!in_array($data['type_onderhoud'], beurt_types(), true)) {
        return function_error('Kies een type onderhoud.');
    }
    if (!is_valid_date($data['datum'])) {
        return function_error('Vul een geldige datum in.');
    }
    if ($data['datum'] < date('Y-m-d')) {
        return function_error('De datum mag niet in het verleden liggen.');
    }
    if (!is_valid_time($data['tijd'])) {
        return function_error('Vul een geldige tijd in (uu:mm).');
    }
    if (is_numeric($data['max_autos']) && (float) $data['max_autos'] < 1) {
        return function_error('Maximaal aantal auto\'s mag niet 0 of negatief zijn.');
    }
    if (!ctype_digit($data['max_autos'])) {
        return function_error('Maximaal aantal auto\'s moet een heel getal zijn.');
    }
    $max_autos = (int) $data['max_autos'];
    if ($max_autos < 1 || $max_autos > WERKPLAATS_PLEKKEN) {
        return function_error('Maximaal aantal auto\'s moet tussen 1 en ' . WERKPLAATS_PLEKKEN . ' liggen.');
    }

    // 2. Werkplaats-check: is er die dag nog genoeg plek?
    $vrij = werkplaats_vrij($conn, $data['datum']);
    if ($vrij === false) {
        return false; // database fout (410 al gezet)
    }
    if ($vrij <= 0) {
        return function_error('De werkplaats is op ' . format_date($data['datum'], false) . ' al vol geboekt. Kies een andere dag.');
    }
    if ($max_autos > $vrij) {
        return function_error('Op ' . format_date($data['datum'], false) . ' zijn nog maar ' . $vrij . ' plek(ken) vrij in de werkplaats.');
    }

    // 3. Opslaan
    return db_insert(
        $conn,
        'INSERT INTO beurten (type_onderhoud, datum, tijd, max_autos) VALUES (?, ?, ?, ?)',
        'sssi',
        array($data['type_onderhoud'], $data['datum'], $data['tijd'], $max_autos)
    );
}

// ---------------------------------------------------------------------
// DELETE - alleen een beurt ZONDER afspraken (eerst afspraken annuleren)
// ---------------------------------------------------------------------
function beurt_delete($conn, $beurt)
{
    if ((int) $beurt['geboekt'] > 0) {
        return function_error('Deze beurt heeft nog afspraken. Annuleer die eerst.');
    }

    $result = db_change($conn, 'DELETE FROM beurten WHERE id = ?', 'i', array($beurt['id']));
    if ($result === false) {
        return false;
    }
    return true;
}
