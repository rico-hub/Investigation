<?php
// =====================================================================
// functions/grades.php - CRUD cijfers + filters + rapport
// =====================================================================

// ---------------------------------------------------------------------
// READ
// ---------------------------------------------------------------------
function grades_read_filters()
{
    return array(
        'search'        => get_param('search'),
        'class_id'      => get_param('class_id'),
        'subject_id'    => get_param('subject_id'),
        'status'        => get_param('status'),       // voldoende / onvoldoende
        'f_id'          => get_param('f_id'),
        'f_student'     => get_param('f_student'),
        'f_description' => get_param('f_description'),
        'sort'          => get_param('sort'),
        'dir'           => get_param('dir'),
    );
}

// $only_student_id: leerling ziet alleen eigen cijfers. null = docent ziet alles.
function grades_get_all($conn, $filters, $only_student_id)
{
    $sql = 'SELECT g.id, g.description, g.grade, g.weight, g.test_date,
                   u.name AS student_name, c.name AS class_name,
                   s.name AS subject_name, t.name AS teacher_name
            FROM grades g
            INNER JOIN users u ON u.id = g.student_id
            LEFT JOIN classes c ON c.id = u.class_id
            INNER JOIN subjects s ON s.id = g.subject_id
            LEFT JOIN users t ON t.id = g.teacher_id
            WHERE g.deleted_at IS NULL';
    $types = '';
    $params = array();

    if ($only_student_id !== null) {
        $sql .= ' AND g.student_id = ?';
        $types .= 'i';
        $params[] = $only_student_id;
    }

    if ($filters['search'] !== '') {
        $sql .= ' AND (u.name LIKE ? OR g.description LIKE ? OR s.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if (is_valid_id($filters['class_id'])) {
        $sql .= ' AND u.class_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['class_id'];
    }

    if (is_valid_id($filters['subject_id'])) {
        $sql .= ' AND g.subject_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['subject_id'];
    }

    // StatusFilter: berekend uit het cijfer
    if ($filters['status'] === 'voldoende') {
        $sql .= ' AND g.grade >= ?';
        $types .= 'd';
        $params[] = PASS_GRADE;
    } elseif ($filters['status'] === 'onvoldoende') {
        $sql .= ' AND g.grade < ?';
        $types .= 'd';
        $params[] = PASS_GRADE;
    }

    // Kolomfilters
    if (is_valid_id($filters['f_id'])) {
        $sql .= ' AND g.id = ?';
        $types .= 'i';
        $params[] = (int) $filters['f_id'];
    }
    if ($filters['f_student'] !== '') {
        $sql .= ' AND u.name LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_student'] . '%';
    }
    if ($filters['f_description'] !== '') {
        $sql .= ' AND g.description LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_description'] . '%';
    }

    $columns = array(
        'id' => 'g.id',
        'student' => 'u.name',
        'class' => 'c.name',
        'subject' => 's.name',
        'description' => 'g.description',
        'grade' => 'g.grade',
        'date' => 'g.test_date',
    );
    $order = 'g.test_date';
    if (isset($columns[$filters['sort']])) {
        $order = $columns[$filters['sort']];
    }
    $direction = 'DESC';
    if ($filters['dir'] === 'asc') {
        $direction = 'ASC';
    }
    $sql .= ' ORDER BY ' . $order . ' ' . $direction . ', g.id DESC';

    return db_select_all($conn, $sql, $types, $params);
}

// Gewogen gemiddelde van een lijst cijfers (uitgerekend in PHP)
// (7.0 x 1 + 5.0 x 2) / (1 + 2) = 5.7
function grades_weighted_average($grades)
{
    $total = 0;
    $weights = 0;
    foreach ($grades as $grade) {
        $total = $total + ((float) $grade['grade'] * (int) $grade['weight']);
        $weights = $weights + (int) $grade['weight'];
    }
    if ($weights === 0) {
        return null; // geen cijfers: niet delen door 0!
    }
    return round($total / $weights, 1);
}

function grade_status($grade)
{
    if ((float) $grade >= PASS_GRADE) {
        return 'voldoende';
    }
    return 'onvoldoende';
}

// Cijfer netjes tonen: 7.5 -> 7,5
function format_grade($grade)
{
    if ($grade === null) {
        return '-';
    }
    return number_format((float) $grade, 1, ',', '');
}

function grade_find($conn, $id)
{
    return db_select_one(
        $conn,
        'SELECT g.*, u.name AS student_name, s.name AS subject_name
         FROM grades g
         INNER JOIN users u ON u.id = g.student_id
         INNER JOIN subjects s ON s.id = g.subject_id
         WHERE g.id = ? AND g.deleted_at IS NULL',
        'i',
        array($id)
    );
}

// ---------------------------------------------------------------------
// Formulier + checks
// ---------------------------------------------------------------------
function grade_empty_data()
{
    return array(
        'student_id' => '',
        'subject_id' => '',
        'description' => '',
        'grade' => '',
        'weight' => '1',
        'test_date' => date('Y-m-d'),
    );
}

function grade_form_data()
{
    return array(
        'student_id' => post_param('student_id'),
        'subject_id' => post_param('subject_id'),
        'description' => post_param('description'),
        'grade' => post_param('grade'),
        'weight' => post_param('weight'),
        'test_date' => post_param('test_date'),
    );
}

// "7,5" -> 7.5   Geeft float of false
function parse_grade($value)
{
    $value = str_replace(',', '.', trim($value)); // Nederlandse komma toestaan
    if ($value === '' || !is_numeric($value)) {
        return false;
    }
    $grade = round((float) $value, 1);
    if ($grade < 1.0 || $grade > 10.0) {
        return false;
    }
    return $grade;
}

// Checks voor alles BEHALVE student en cijfer (die checken we apart, ook voor bulk)
function grade_validate_common($conn, $data)
{
    if (!is_valid_id($data['subject_id']) || !subject_exists($conn, (int) $data['subject_id'])) {
        return function_error('Kies een geldig vak.');
    }
    if ($data['description'] === '' || mb_strlen($data['description']) > 100) {
        return function_error('Omschrijving is verplicht (max. 100 tekens).');
    }
    if (!ctype_digit($data['weight']) || (int) $data['weight'] < 1 || (int) $data['weight'] > 5) {
        return function_error('Weging moet 1 t/m 5 zijn.');
    }
    if (!is_valid_date($data['test_date'])) {
        return function_error('Ongeldige datum.');
    }
    if ($data['test_date'] > date('Y-m-d')) {
        return function_error('De toetsdatum kan niet in de toekomst liggen.');
    }
    return true;
}

function grade_validate($conn, $data)
{
    if (!is_valid_id($data['student_id'])) {
        return function_error('Kies een leerling.');
    }
    $student = student_find($conn, (int) $data['student_id']);
    if ($student === false) {
        return false;
    }
    if ($student === null) {
        return function_error('Deze leerling bestaat niet.');
    }
    if (parse_grade($data['grade']) === false) {
        return function_error('Cijfer moet tussen 1,0 en 10,0 liggen.');
    }
    return grade_validate_common($conn, $data);
}

// ---------------------------------------------------------------------
// CREATE / UPDATE / DELETE (docent = admin)
// ---------------------------------------------------------------------
function grade_create($conn, $data, $teacher_id)
{
    if (!grade_validate($conn, $data)) {
        return false;
    }

    return db_insert(
        $conn,
        'INSERT INTO grades (student_id, subject_id, teacher_id, description, grade, weight, test_date) VALUES (?, ?, ?, ?, ?, ?, ?)',
        'iiisdis',
        array((int) $data['student_id'], (int) $data['subject_id'], $teacher_id, $data['description'], parse_grade($data['grade']), (int) $data['weight'], $data['test_date'])
    );
}

function grade_update($conn, $id, $data)
{
    $grade = grade_find($conn, $id);
    if ($grade === false) {
        return false;
    }
    if ($grade === null) {
        return function_error('Cijfer niet gevonden.');
    }
    if (!grade_validate($conn, $data)) {
        return false;
    }

    $result = db_change(
        $conn,
        'UPDATE grades SET student_id = ?, subject_id = ?, description = ?, grade = ?, weight = ?, test_date = ? WHERE id = ?',
        'iisdisi',
        array((int) $data['student_id'], (int) $data['subject_id'], $data['description'], parse_grade($data['grade']), (int) $data['weight'], $data['test_date'], $id)
    );
    if ($result === false) {
        return false;
    }
    return true;
}

// Soft delete: een cijfer "verdwijnt" van het rapport, maar is terug te halen
function grade_soft_delete($conn, $id)
{
    $result = db_change($conn, 'UPDATE grades SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Cijfer niet gevonden of al verwijderd.');
    }
    return true;
}

// ---------------------------------------------------------------------
// BULK: 1 toets invoeren voor alle geselecteerde leerlingen (winkelmand)
// $grades_per_student = array(student_id => '7,5', ...)
// Lege invoer = leerling overslaan. Transactie: alles of niets.
// ---------------------------------------------------------------------
function grades_bulk_create($conn, $data, $grades_per_student, $teacher_id)
{
    if (!grade_validate_common($conn, $data)) {
        return false;
    }

    // Eerst ALLE cijfers checken, pas daarna opslaan
    $to_save = array();
    foreach ($grades_per_student as $student_id => $value) {
        if (trim($value) === '') {
            continue; // niet ingevuld = overslaan
        }
        $grade = parse_grade($value);
        if ($grade === false) {
            return function_error('Ongeldig cijfer "' . $value . '" bij leerling #' . $student_id . '. Er is niets opgeslagen.');
        }
        $to_save[(int) $student_id] = $grade;
    }

    if (count($to_save) === 0) {
        return function_error('Vul minstens 1 cijfer in.');
    }

    $conn->begin_transaction();

    foreach ($to_save as $student_id => $grade) {
        $new_id = db_insert(
            $conn,
            'INSERT INTO grades (student_id, subject_id, teacher_id, description, grade, weight, test_date) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'iiisdis',
            array($student_id, (int) $data['subject_id'], $teacher_id, $data['description'], $grade, (int) $data['weight'], $data['test_date'])
        );
        if ($new_id === false) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return count($to_save);
}

// ---------------------------------------------------------------------
// RAPPORT: per vak aantal cijfers + gewogen gemiddelde (berekend in SQL)
// ---------------------------------------------------------------------
function report_get($conn, $student_id)
{
    return db_select_all(
        $conn,
        'SELECT s.id, s.code, s.name,
                COUNT(g.id) AS grade_count,
                ROUND(SUM(g.grade * g.weight) / SUM(g.weight), 1) AS average,
                MIN(g.grade) AS lowest,
                MAX(g.grade) AS highest
         FROM subjects s
         LEFT JOIN grades g ON g.subject_id = s.id AND g.student_id = ? AND g.deleted_at IS NULL
         GROUP BY s.id, s.code, s.name
         ORDER BY s.name',
        'i',
        array($student_id)
    );
}
