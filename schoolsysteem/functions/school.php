<?php
// =====================================================================
// functions/school.php - klassen, vakken, leerlingen
// =====================================================================

function classes_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name, mentor FROM classes ORDER BY name', '', array());
}

function class_exists_db($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM classes WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}

function subjects_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, code, name FROM subjects ORDER BY name', '', array());
}

function subject_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM subjects WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}

// Leerlingen (role = 'user') met klas en aantal cijfers. Filters: class_id + search.
function students_get_all($conn, $class_id, $search)
{
    $sql = "SELECT u.id, u.name, u.email, c.name AS class_name,
                   (SELECT COUNT(*) FROM grades g WHERE g.student_id = u.id AND g.deleted_at IS NULL) AS grade_count
            FROM users u
            LEFT JOIN classes c ON c.id = u.class_id
            WHERE u.role = 'user'";
    $types = '';
    $params = array();

    if (is_valid_id($class_id)) {
        $sql .= ' AND u.class_id = ?';
        $types .= 'i';
        $params[] = (int) $class_id;
    }
    if ($search !== '') {
        $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY c.name, u.name';

    return db_select_all($conn, $sql, $types, $params);
}

// 1 leerling (geeft null als het id geen leerling is)
function student_find($conn, $id)
{
    return db_select_one(
        $conn,
        "SELECT u.id, u.name, u.email, u.class_id, c.name AS class_name
         FROM users u
         LEFT JOIN classes c ON c.id = u.class_id
         WHERE u.id = ? AND u.role = 'user'",
        'i',
        array($id)
    );
}
