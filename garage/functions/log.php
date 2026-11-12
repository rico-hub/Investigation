<?php
function log_action($conn, $user_id, $action, $details)
{
    $ip = '';
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $details = mb_substr($details, 0, 255);

    $result = db_insert(
        $conn,
        'INSERT INTO action_log (user_id, action, details) VALUES (?, ?, ?)',
        'iss',
        array($user_id, $action, $details)
    );

    if ($result === false) {
        return false;
    }
    return true;
}

function logs_get_all($conn, $filters)
{
    $sql = 'SELECT l.id, l.action, l.details, l.created_at, u.name AS user_name
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

function logs_get_actions($conn)
{
    return db_select_all($conn, 'SELECT DISTINCT action FROM action_log ORDER BY action', '', array());
}
