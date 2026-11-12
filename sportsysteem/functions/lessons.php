<?php
// =====================================================================
// functions/lessons.php - sporten + CRUD lessen + filters
// =====================================================================

function sports_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM sports ORDER BY name', '', array());
}

function sport_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM sports WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}

// Statussen voor de STATUSFILTER (berekend uit status + datum + aantal inschrijvingen)
function lesson_filter_statuses()
{
    return array(
        'open_inschrijving' => 'Open voor inschrijving',
        'vol'               => 'Vol',
        'geannuleerd'       => 'Geannuleerd',
        'afgelopen'         => 'Afgelopen',
    );
}

// Statussen die echt in de database staan (ENUM)
function lesson_db_statuses()
{
    return array('gepland', 'geannuleerd');
}

// Status van 1 les uitrekenen (voor het label in de tabel)
function lesson_status($lesson)
{
    if ($lesson['status'] === 'geannuleerd') {
        return 'geannuleerd';
    } elseif ($lesson['lesson_date'] < date('Y-m-d')) {
        return 'afgelopen';
    } elseif ((int) $lesson['registered'] >= (int) $lesson['max_participants']) {
        return 'vol';
    } else {
        return 'open_inschrijving';
    }
}

// ---------------------------------------------------------------------
// READ
// ---------------------------------------------------------------------
function lessons_read_filters()
{
    return array(
        'search'    => get_param('search'),
        'status'    => get_param('status'),
        'sport_id'  => get_param('sport_id'),
        'sort'      => get_param('sort'),
        'dir'       => get_param('dir'),
    );
}

// LEFT JOIN + COUNT + GROUP BY = aantal inschrijvingen per les (ook 0)
function lessons_get_all($conn, $filters)
{
    $sql = 'SELECT l.id, l.title, l.trainer, l.lesson_date, l.start_time, l.max_participants, l.status,
                   s.name AS sport_name, COUNT(r.id) AS registered
            FROM lessons l
            INNER JOIN sports s ON s.id = l.sport_id
            LEFT JOIN registrations r ON r.lesson_id = l.id
            WHERE l.deleted_at IS NULL';
    $types = '';
    $params = array();
    $having = '';

    if ($filters['search'] !== '') {
        $sql .= ' AND (l.title LIKE ? OR l.trainer LIKE ? OR s.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (is_valid_id($filters['sport_id'])) {
        $sql .= ' AND l.sport_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['sport_id'];
    }

    // StatusFilter. "vol" en "open" hangen af van COUNT() -> dat moet in HAVING (na GROUP BY)
    if ($filters['status'] === 'open_inschrijving') {
        $sql .= " AND l.status = 'gepland' AND l.lesson_date >= CURDATE()";
        $having = ' HAVING COUNT(r.id) < l.max_participants';
    } elseif ($filters['status'] === 'vol') {
        $sql .= " AND l.status = 'gepland' AND l.lesson_date >= CURDATE()";
        $having = ' HAVING COUNT(r.id) >= l.max_participants';
    } elseif ($filters['status'] === 'geannuleerd') {
        $sql .= " AND l.status = 'geannuleerd'";
    } elseif ($filters['status'] === 'afgelopen') {
        $sql .= " AND l.status = 'gepland' AND l.lesson_date < CURDATE()";
    }

    $sql .= ' GROUP BY l.id, s.name';
    $sql .= $having;

    $columns = array(
        'id' => 'l.id',
        'title' => 'l.title',
        'sport' => 's.name',
        'trainer' => 'l.trainer',
        'date' => 'l.lesson_date',
        'registered' => 'registered',
    );
    $order = 'l.lesson_date';
    if (isset($columns[$filters['sort']])) {
        $order = $columns[$filters['sort']];
    }
    $direction = 'ASC';
    if ($filters['dir'] === 'desc') {
        $direction = 'DESC';
    }
    $sql .= ' ORDER BY ' . $order . ' ' . $direction . ', l.start_time ASC';

    return db_select_all($conn, $sql, $types, $params);
}

function lessons_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM lessons WHERE deleted_at IS NULL', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['total'];
}

// 1 les + aantal inschrijvingen
function lesson_find($conn, $id)
{
    return db_select_one(
        $conn,
        'SELECT l.*, s.name AS sport_name,
                (SELECT COUNT(*) FROM registrations r WHERE r.lesson_id = l.id) AS registered
         FROM lessons l
         INNER JOIN sports s ON s.id = l.sport_id
         WHERE l.id = ? AND l.deleted_at IS NULL',
        'i',
        array($id)
    );
}

// ---------------------------------------------------------------------
// Formulier
// ---------------------------------------------------------------------
function lesson_empty_data()
{
    return array(
        'sport_id' => '',
        'title' => '',
        'trainer' => '',
        'lesson_date' => date('Y-m-d', strtotime('+1 day')),
        'start_time' => '19:00',
        'max_participants' => '10',
        'status' => 'gepland',
    );
}

function lesson_form_data()
{
    return array(
        'sport_id' => post_param('sport_id'),
        'title' => post_param('title'),
        'trainer' => post_param('trainer'),
        'lesson_date' => post_param('lesson_date'),
        'start_time' => post_param('start_time'),
        'max_participants' => post_param('max_participants'),
        'status' => post_param('status'),
    );
}

function lesson_validate($conn, $data)
{
    if (!is_valid_id($data['sport_id']) || !sport_exists($conn, (int) $data['sport_id'])) {
        return function_error('Kies een geldige sport.');
    }
    if ($data['title'] === '' || mb_strlen($data['title']) > 150) {
        return function_error('Titel is verplicht (max. 150 tekens).');
    }
    if ($data['trainer'] === '' || mb_strlen($data['trainer']) > 100) {
        return function_error('Trainer is verplicht (max. 100 tekens).');
    }
    if (!is_valid_date($data['lesson_date'])) {
        return function_error('Ongeldige datum.');
    }
    if (!is_valid_time($data['start_time'])) {
        return function_error('Ongeldige tijd (uu:mm).');
    }
    if (!ctype_digit($data['max_participants'])) {
        return function_error('Max. deelnemers moet een getal zijn.');
    } elseif ((int) $data['max_participants'] < 1 || (int) $data['max_participants'] > 50) {
        return function_error('Max. deelnemers moet tussen 1 en 50 liggen.');
    }
    if (!in_array($data['status'], lesson_db_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    return true;
}

// ---------------------------------------------------------------------
// CREATE / UPDATE / DELETE (admin)
// ---------------------------------------------------------------------
function lesson_create($conn, $data)
{
    if (!lesson_validate($conn, $data)) {
        return false;
    }
    if ($data['lesson_date'] < date('Y-m-d')) {
        return function_error('Een nieuwe les kan niet in het verleden liggen.');
    }

    return db_insert(
        $conn,
        'INSERT INTO lessons (sport_id, title, trainer, lesson_date, start_time, max_participants, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
        'issssis',
        array((int) $data['sport_id'], $data['title'], $data['trainer'], $data['lesson_date'], $data['start_time'], (int) $data['max_participants'], $data['status'])
    );
}

function lesson_update($conn, $id, $data)
{
    $lesson = lesson_find($conn, $id);
    if ($lesson === false) {
        return false;
    }
    if ($lesson === null) {
        return function_error('Les niet gevonden.');
    }
    if (!lesson_validate($conn, $data)) {
        return false;
    }
    // Check: niet minder plekken dan er al mensen ingeschreven zijn
    if ((int) $data['max_participants'] < (int) $lesson['registered']) {
        return function_error('Er zijn al ' . $lesson['registered'] . ' inschrijvingen. Max. deelnemers kan niet lager.');
    }

    $result = db_change(
        $conn,
        'UPDATE lessons SET sport_id = ?, title = ?, trainer = ?, lesson_date = ?, start_time = ?, max_participants = ?, status = ? WHERE id = ?',
        'issssisi',
        array((int) $data['sport_id'], $data['title'], $data['trainer'], $data['lesson_date'], $data['start_time'], (int) $data['max_participants'], $data['status'], $id)
    );
    if ($result === false) {
        return false;
    }
    return true;
}

// Soft delete, maar NIET als er inschrijvingen zijn (dan: annuleren)
function lesson_soft_delete($conn, $id)
{
    $lesson = lesson_find($conn, $id);
    if ($lesson === false) {
        return false;
    }
    if ($lesson === null) {
        return function_error('Les niet gevonden of al verwijderd.');
    }
    if ((int) $lesson['registered'] > 0) {
        return function_error('Er zijn ' . $lesson['registered'] . ' inschrijvingen. Zet de les op "geannuleerd" in plaats van verwijderen.');
    }

    $result = db_change($conn, 'UPDATE lessons SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}
