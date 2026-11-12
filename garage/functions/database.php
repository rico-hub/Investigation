<?php
function db_execute($conn, $sql, $types, $params)
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return database_error('Query voorbereiden mislukt', $conn->error);
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return database_error('Query uitvoeren mislukt', $error);
    }

    return $stmt;
}

function db_select_all($conn, $sql, $types, $params)
{
    $stmt = db_execute($conn, $sql, $types, $params);
    if ($stmt === false) {
        return false;
    }

    $result = $stmt->get_result();
    $rows = array();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function db_select_one($conn, $sql, $types, $params)
{
    $stmt = db_execute($conn, $sql, $types, $params);
    if ($stmt === false) {
        return false;
    }

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row === null) {
        return null;
    }
    return $row;
}

function db_change($conn, $sql, $types, $params)
{
    $stmt = db_execute($conn, $sql, $types, $params);
    if ($stmt === false) {
        return false;
    }
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function db_insert($conn, $sql, $types, $params)
{
    $stmt = db_execute($conn, $sql, $types, $params);
    if ($stmt === false) {
        return false;
    }
    $new_id = $stmt->insert_id;
    $stmt->close();
    return $new_id;
}

function db_placeholders($count)
{
    return implode(', ', array_fill(0, $count, '?'));
}
