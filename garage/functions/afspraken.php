<?php
function kenteken_normaal($kenteken)
{
    return strtoupper(str_replace(' ', '', $kenteken));
}

function is_valid_kenteken($kenteken)
{
    if (strlen($kenteken) !== 8) {
        return false;
    }
    if (!preg_match('/^[A-Z0-9]{1,3}-[A-Z0-9]{1,3}-[A-Z0-9]{1,3}$/', $kenteken)) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------
// READ
// ---------------------------------------------------------------------
function afspraken_read_filters()
{
    return array(
        'search'   => get_param('search'),    // klant of kenteken
        'type'     => get_param('type'),      // type onderhoud
        'periode'  => get_param('periode'),   // komend / verleden
        'beurt_id' => get_param('beurt_id'),  // alleen afspraken van 1 beurt
    );
}

function afspraken_get_all($conn, $filters)
{
    $sql = 'SELECT a.id, a.kenteken, a.created_at, k.naam AS klant_naam, k.telefoon,
                   b.id AS beurt_id, b.type_onderhoud, b.datum, b.tijd
            FROM afspraken a
            INNER JOIN klanten k ON k.id = a.klant_id
            INNER JOIN beurten b ON b.id = a.beurt_id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    if ($filters['search'] !== '') {
        $sql .= ' AND (k.naam LIKE ? OR a.kenteken LIKE ?)';
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

    if (is_valid_id($filters['beurt_id'])) {
        $sql .= ' AND a.beurt_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['beurt_id'];
    }

    $sql .= ' ORDER BY b.datum, b.tijd, a.kenteken';

    return db_select_all($conn, $sql, $types, $params);
}

// 1 afspraak met klant en beurt. Geeft array, null of false.
function afspraak_find($conn, $id)
{
    return db_select_one(
        $conn,
        'SELECT a.id, a.kenteken, k.naam AS klant_naam, b.type_onderhoud, b.datum, b.tijd
         FROM afspraken a
         INNER JOIN klanten k ON k.id = a.klant_id
         INNER JOIN beurten b ON b.id = a.beurt_id
         WHERE a.id = ?',
        'i',
        array($id)
    );
}

// Hoeveel auto's staan er op een dag in de werkplaats (alle beurten samen)?
function afspraken_op_dag($conn, $datum)
{
    $row = db_select_one(
        $conn,
        'SELECT COUNT(*) AS totaal FROM afspraken a INNER JOIN beurten b ON b.id = a.beurt_id WHERE b.datum = ?',
        's',
        array($datum)
    );
    if ($row === false) {
        return false;
    }
    return (int) $row['totaal'];
}

// ---------------------------------------------------------------------
// CREATE
// ---------------------------------------------------------------------
function afspraak_empty_data()
{
    return array('klant_id' => '', 'beurt_id' => '', 'kenteken' => '');
}

function afspraak_form_data()
{
    return array(
        'klant_id' => post_param('klant_id'),
        'beurt_id' => post_param('beurt_id'),
        'kenteken' => kenteken_normaal(post_param('kenteken')),
    );
}

// Geeft het nieuwe id, of false.
function afspraak_create($conn, $data)
{
    // 1. Checks op de invoer (420)
    if (!is_valid_id($data['klant_id'])) {
        return function_error('Kies een klant.');
    }
    if (!is_valid_id($data['beurt_id'])) {
        return function_error('Kies een beurt.');
    }
    if ($data['kenteken'] === '') {
        return function_error('Kenteken is verplicht.');
    }
    if (!is_valid_kenteken($data['kenteken'])) {
        return function_error('Ongeldig kenteken. Gebruik het formaat AB-123-C.');
    }

    // 2. Bestaan klant en beurt?
    $klant = klant_find($conn, (int) $data['klant_id']);
    if ($klant === false) {
        return false;
    }
    if ($klant === null) {
        return function_error('Deze klant bestaat niet.');
    }

    $beurt = beurt_find($conn, (int) $data['beurt_id']);
    if ($beurt === false) {
        return false;
    }
    if ($beurt === null) {
        return function_error('Deze beurt bestaat niet.');
    }
    if ($beurt['datum'] < date('Y-m-d')) {
        return function_error('Deze beurt is al geweest.');
    }

    // 3. Is de beurt vol?
    if ((int) $beurt['geboekt'] >= (int) $beurt['max_autos']) {
        return function_error('Deze beurt is vol (' . $beurt['max_autos'] . ' van ' . $beurt['max_autos'] . ' auto\'s).');
    }

    // 4. Is de werkplaats die dag vol? (max 4 auto's per dag)
    $op_dag = afspraken_op_dag($conn, $beurt['datum']);
    if ($op_dag === false) {
        return false;
    }
    if ($op_dag >= WERKPLAATS_PLEKKEN) {
        return function_error('De werkplaats is op ' . format_date($beurt['datum'], false) . ' vol geboekt.');
    }

    // 5. Staat deze auto al bij deze beurt?
    $dubbel = db_select_one(
        $conn,
        'SELECT id FROM afspraken WHERE beurt_id = ? AND kenteken = ?',
        'is',
        array($beurt['id'], $data['kenteken'])
    );
    if ($dubbel === false) {
        return false;
    }
    if ($dubbel !== null) {
        return function_error('Kenteken ' . $data['kenteken'] . ' staat al bij deze beurt.');
    }

    // 6. Opslaan
    return db_insert(
        $conn,
        'INSERT INTO afspraken (klant_id, beurt_id, kenteken) VALUES (?, ?, ?)',
        'iis',
        array($klant['id'], $beurt['id'], $data['kenteken'])
    );
}

// ---------------------------------------------------------------------
// ANNULEREN = afspraak verwijderen (de plek komt weer vrij)
// ---------------------------------------------------------------------
function afspraak_cancel($conn, $id)
{
    $result = db_change($conn, 'DELETE FROM afspraken WHERE id = ?', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Deze afspraak bestaat niet (meer).');
    }
    return true;
}
