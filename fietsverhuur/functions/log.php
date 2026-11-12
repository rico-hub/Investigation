<?php
// =====================================================================
// functions/log.php - acties van gebruikers opslaan (audit log)
// UNIVERSEEL: nooit aanpassen. Heeft de tabel action_log nodig.
// =====================================================================

// $user_id: geef current_user_id() mee (uit de SESSIE), of null bij bv. mislukte login.
function log_action($conn, $user_id, $action, $details)
{
    $ip = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // details inkorten tot de kolomgrootte (VARCHAR 255)
    $details = mb_substr($details, 0, 255);

    $result = db_insert(
        $conn,
        'INSERT INTO action_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)',
        'isss',
        array($user_id, $action, $details, $ip)
    );

    if ($result === false) {
        return false;
    }
    return true;
}

// Logboek lezen (admin). Filters: action (dropdown) en search.
function logs_get_all($conn, $filters)
{
    $sql = 'SELECT l.id, l.action, l.details, l.ip_address, l.created_at, u.name AS user_name
            FROM action_log l
            LEFT JOIN users u ON u.id = l.user_id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    if ($filters['action'] !== '') {
        $sql .= ' AND l.action = ?';
        $types .= 's';
        $params[] = $filters['action'];
    }

    if ($filters['search'] !== '') {
        $sql .= ' AND (l.details LIKE ? OR u.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY l.created_at DESC, l.id DESC LIMIT 200';

    return db_select_all($conn, $sql, $types, $params);
}

// Alle verschillende acties (voor de dropdown)
function logs_get_actions($conn)
{
    return db_select_all($conn, 'SELECT DISTINCT action FROM action_log ORDER BY action', '', array());
}
