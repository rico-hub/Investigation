<?php
// =====================================================================
// functions/complaints.php - CRUD klachten + reacties + bulk update
// Regels:
//   - user: maakt klachten, ziet/wijzigt alleen EIGEN klachten, en alleen zolang status 'open'
//   - admin: ziet alles, wijzigt status/prioriteit/behandelaar, prullenbak
// =====================================================================

function complaint_statuses()
{
    return array('open', 'in_behandeling', 'gesloten');
}

function complaint_priorities()
{
    return array('laag', 'normaal', 'hoog');
}

// ---------------------------------------------------------------------
// READ
// ---------------------------------------------------------------------
function complaints_read_filters()
{
    return array(
        'search'      => get_param('search'),
        'status'      => get_param('status'),
        'priority'    => get_param('priority'),
        'category_id' => get_param('category_id'),
        'f_id'        => get_param('f_id'),
        'f_title'     => get_param('f_title'),
        'f_user'      => get_param('f_user'),
        'sort'        => get_param('sort'),
        'dir'         => get_param('dir'),
    );
}

// $only_user_id: id = alleen klachten van die user, null = alles (admin)
function complaints_get_all($conn, $filters, $only_user_id)
{
    $sql = 'SELECT k.id, k.title, k.priority, k.status, k.created_at,
                   c.name AS category_name, u.name AS user_name, a.name AS assigned_name
            FROM complaints k
            INNER JOIN categories c ON c.id = k.category_id
            INNER JOIN users u ON u.id = k.user_id
            LEFT JOIN users a ON a.id = k.assigned_to
            WHERE k.deleted_at IS NULL';
    $types = '';
    $params = array();

    if ($only_user_id !== null) {
        $sql .= ' AND k.user_id = ?';
        $types .= 'i';
        $params[] = $only_user_id;
    }

    // Searchbar
    if ($filters['search'] !== '') {
        $sql .= ' AND (k.title LIKE ? OR k.description LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    // StatusFilter
    if (in_array($filters['status'], complaint_statuses(), true)) {
        $sql .= ' AND k.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    if (in_array($filters['priority'], complaint_priorities(), true)) {
        $sql .= ' AND k.priority = ?';
        $types .= 's';
        $params[] = $filters['priority'];
    }

    if (is_valid_id($filters['category_id'])) {
        $sql .= ' AND k.category_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['category_id'];
    }

    // Kolomfilters
    if (is_valid_id($filters['f_id'])) {
        $sql .= ' AND k.id = ?';
        $types .= 'i';
        $params[] = (int) $filters['f_id'];
    }
    if ($filters['f_title'] !== '') {
        $sql .= ' AND k.title LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_title'] . '%';
    }
    if ($filters['f_user'] !== '') {
        $sql .= ' AND u.name LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_user'] . '%';
    }

    // Sorteren (whitelist)
    $columns = array(
        'id' => 'k.id',
        'title' => 'k.title',
        'category' => 'c.name',
        'user' => 'u.name',
        'priority' => 'k.priority',
        'status' => 'k.status',
        'created' => 'k.created_at',
    );
    $order = 'k.created_at';
    if (isset($columns[$filters['sort']])) {
        $order = $columns[$filters['sort']];
    }
    $direction = 'DESC';
    if ($filters['dir'] === 'asc') {
        $direction = 'ASC';
    }
    $sql .= ' ORDER BY ' . $order . ' ' . $direction;

    return db_select_all($conn, $sql, $types, $params);
}

// TotalCount per status -> array('open' => 3, 'in_behandeling' => 2, 'gesloten' => 1, 'totaal' => 6)
function complaints_count_by_status($conn, $only_user_id)
{
    $counts = array('totaal' => 0);
    foreach (complaint_statuses() as $status) {
        $counts[$status] = 0;
    }

    $sql = 'SELECT status, COUNT(*) AS total FROM complaints WHERE deleted_at IS NULL';
    $types = '';
    $params = array();
    if ($only_user_id !== null) {
        $sql .= ' AND user_id = ?';
        $types = 'i';
        $params[] = $only_user_id;
    }
    $sql .= ' GROUP BY status';

    $rows = db_select_all($conn, $sql, $types, $params);
    if ($rows === false) {
        return $counts;
    }

    foreach ($rows as $row) {
        $counts[$row['status']] = (int) $row['total'];
        $counts['totaal'] = $counts['totaal'] + (int) $row['total'];
    }
    return $counts;
}

// Link voor de status-knoppen: huidige filters houden, alleen status veranderen
function status_tab_link($filters, $status)
{
    $params = $filters;
    $params['status'] = $status;
    return 'index.php?' . http_build_query($params);
}

function complaint_find($conn, $id, $include_deleted)
{
    $sql = 'SELECT k.*, c.name AS category_name, u.name AS user_name, a.name AS assigned_name
            FROM complaints k
            INNER JOIN categories c ON c.id = k.category_id
            INNER JOIN users u ON u.id = k.user_id
            LEFT JOIN users a ON a.id = k.assigned_to
            WHERE k.id = ?';
    if (!$include_deleted) {
        $sql .= ' AND k.deleted_at IS NULL';
    }
    return db_select_one($conn, $sql, 'i', array($id));
}

function complaints_get_deleted($conn)
{
    return db_select_all(
        $conn,
        'SELECT k.id, k.title, k.status, k.deleted_at, u.name AS user_name
         FROM complaints k
         INNER JOIN users u ON u.id = k.user_id
         WHERE k.deleted_at IS NOT NULL
         ORDER BY k.deleted_at DESC',
        '',
        array()
    );
}

// Mag deze gebruiker de klacht zien?
function complaint_can_view($complaint, $user_id, $user_is_admin)
{
    if ($user_is_admin) {
        return true;
    }
    if ((int) $complaint['user_id'] === $user_id) {
        return true;
    }
    return false;
}

// Mag deze gebruiker de klacht wijzigen/verwijderen?
function complaint_can_edit($complaint, $user_id, $user_is_admin)
{
    if ($user_is_admin) {
        return true;
    }
    if ((int) $complaint['user_id'] === $user_id && $complaint['status'] === 'open') {
        return true;
    }
    return false;
}

// ---------------------------------------------------------------------
// Formulier
// ---------------------------------------------------------------------
function complaint_empty_data()
{
    return array(
        'title' => '',
        'description' => '',
        'category_id' => '',
        'priority' => 'normaal',
        'status' => 'open',
        'assigned_to' => '',
    );
}

function complaint_form_data()
{
    return array(
        'title' => post_param('title'),
        'description' => post_param('description'),
        'category_id' => post_param('category_id'),
        'priority' => post_param('priority'),
        'status' => post_param('status'),
        'assigned_to' => post_param('assigned_to'),
    );
}

function complaint_validate($conn, $data, $user_is_admin)
{
    if (mb_strlen($data['title']) < 3) {
        return function_error('Titel moet minimaal 3 tekens zijn.');
    }
    if (mb_strlen($data['title']) > 150) {
        return function_error('Titel mag maximaal 150 tekens zijn.');
    }
    if ($data['description'] === '') {
        return function_error('Beschrijving is verplicht.');
    }
    if (!is_valid_id($data['category_id']) || !category_exists($conn, (int) $data['category_id'])) {
        return function_error('Kies een geldige categorie.');
    }
    if (!in_array($data['priority'], complaint_priorities(), true)) {
        return function_error('Ongeldige prioriteit.');
    }

    // Alleen admin stuurt status en behandelaar mee
    if ($user_is_admin) {
        if (!in_array($data['status'], complaint_statuses(), true)) {
            return function_error('Ongeldige status.');
        }
        if ($data['assigned_to'] !== '') {
            if (!is_valid_id($data['assigned_to']) || !admin_exists($conn, (int) $data['assigned_to'])) {
                return function_error('Behandelaar moet een admin zijn.');
            }
        }
    }
    return true;
}

function id_or_null($value)
{
    if ($value === '' || $value === null) {
        return null;
    }
    return (int) $value;
}

// ---------------------------------------------------------------------
// CREATE (door user of admin; start altijd als 'open')
// ---------------------------------------------------------------------
function complaint_create($conn, $user_id, $data)
{
    if (!complaint_validate($conn, $data, false)) {
        return false;
    }

    return db_insert(
        $conn,
        'INSERT INTO complaints (user_id, category_id, title, description, priority) VALUES (?, ?, ?, ?, ?)',
        'iisss',
        array($user_id, (int) $data['category_id'], $data['title'], $data['description'], $data['priority'])
    );
}

// ---------------------------------------------------------------------
// UPDATE
// ---------------------------------------------------------------------
function complaint_update($conn, $id, $data, $user_id, $user_is_admin)
{
    $complaint = complaint_find($conn, $id, false);
    if ($complaint === false) {
        return false;
    }
    if ($complaint === null) {
        return function_error('Klacht niet gevonden.');
    }
    if (!complaint_can_edit($complaint, $user_id, $user_is_admin)) {
        return function_error('Je mag deze klacht niet (meer) wijzigen.');
    }
    if (!complaint_validate($conn, $data, $user_is_admin)) {
        return false;
    }

    if ($user_is_admin) {
        $result = db_change(
            $conn,
            'UPDATE complaints SET category_id = ?, title = ?, description = ?, priority = ?, status = ?, assigned_to = ? WHERE id = ?',
            'issssii',
            array((int) $data['category_id'], $data['title'], $data['description'], $data['priority'], $data['status'], id_or_null($data['assigned_to']), $id)
        );
    } else {
        // User: status/behandelaar NIET aanpassen. Extra veiligheid: alleen eigen + open klacht.
        $result = db_change(
            $conn,
            "UPDATE complaints SET category_id = ?, title = ?, description = ?, priority = ?
             WHERE id = ? AND user_id = ? AND status = 'open'",
            'isssii',
            array((int) $data['category_id'], $data['title'], $data['description'], $data['priority'], $id, $user_id)
        );
    }

    if ($result === false) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------
function complaint_soft_delete($conn, $id, $user_id, $user_is_admin)
{
    $complaint = complaint_find($conn, $id, false);
    if ($complaint === false) {
        return false;
    }
    if ($complaint === null) {
        return function_error('Klacht niet gevonden of al verwijderd.');
    }
    if (!complaint_can_edit($complaint, $user_id, $user_is_admin)) {
        return function_error('Je mag deze klacht niet verwijderen.');
    }

    $result = db_change($conn, 'UPDATE complaints SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

function complaint_restore($conn, $id)
{
    $result = db_change($conn, 'UPDATE complaints SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Klacht staat niet in de prullenbak.');
    }
    return true;
}

// Hard delete: reacties gaan automatisch mee door ON DELETE CASCADE
function complaint_hard_delete($conn, $id)
{
    $complaint = complaint_find($conn, $id, true);
    if ($complaint === false) {
        return false;
    }
    if ($complaint === null) {
        return function_error('Klacht niet gevonden.');
    }
    if ($complaint['deleted_at'] === null) {
        return function_error('Zet de klacht eerst in de prullenbak.');
    }

    $result = db_change($conn, 'DELETE FROM complaints WHERE id = ?', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

// ---------------------------------------------------------------------
// REACTIES
// ---------------------------------------------------------------------
function reactions_get($conn, $complaint_id)
{
    return db_select_all(
        $conn,
        'SELECT r.id, r.message, r.created_at, u.name AS user_name, u.role
         FROM reactions r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.complaint_id = ?
         ORDER BY r.created_at ASC, r.id ASC',
        'i',
        array($complaint_id)
    );
}

function reaction_add($conn, $complaint_id, $user_id, $user_is_admin, $message)
{
    if ($message === '') {
        return function_error('Typ eerst een bericht.');
    }
    if (mb_strlen($message) > 2000) {
        return function_error('Bericht mag maximaal 2000 tekens zijn.');
    }

    $complaint = complaint_find($conn, $complaint_id, false);
    if ($complaint === false) {
        return false;
    }
    if ($complaint === null) {
        return function_error('Klacht niet gevonden.');
    }
    if (!complaint_can_view($complaint, $user_id, $user_is_admin)) {
        return function_error('Dit is niet jouw klacht.');
    }
    if ($complaint['status'] === 'gesloten') {
        return function_error('Deze klacht is gesloten. Reageren kan niet meer.');
    }

    return db_insert(
        $conn,
        'INSERT INTO reactions (complaint_id, user_id, message) VALUES (?, ?, ?)',
        'iis',
        array($complaint_id, $user_id, $message)
    );
}

// ---------------------------------------------------------------------
// BULK UPDATE vanuit de werklijst (winkelmand) - admin
// Transactie: alles of niets.
// ---------------------------------------------------------------------
function complaints_bulk_update($conn, $ids, $status, $assign_to_me, $admin_id)
{
    if (count($ids) === 0) {
        return function_error('Je werklijst is leeg.');
    }
    if (!in_array($status, complaint_statuses(), true)) {
        return function_error('Kies een geldige status.');
    }

    $conn->begin_transaction();

    foreach ($ids as $id) {
        if ($assign_to_me) {
            $changed = db_change(
                $conn,
                'UPDATE complaints SET status = ?, assigned_to = ? WHERE id = ? AND deleted_at IS NULL',
                'sii',
                array($status, $admin_id, $id)
            );
        } else {
            $changed = db_change(
                $conn,
                'UPDATE complaints SET status = ? WHERE id = ? AND deleted_at IS NULL',
                'si',
                array($status, $id)
            );
        }

        if ($changed === false) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return true;
}
