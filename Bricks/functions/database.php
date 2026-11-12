<?php
// =====================================================================
// functions/database.php - prepared statements in 1 regel (Error-code 410)
// $types:  's' = tekst, 'i' = heel getal, 'd' = komma-getal
// $params: array met waarden, zelfde volgorde als de ? in de SQL
// =====================================================================

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

// Meerdere rijen -> array (kan leeg zijn) of false bij fout
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

// 1 rij -> array, null (niet gevonden) of false (fout)
function db_select_one($conn, $sql, $types, $params)
{
    $stmt = db_execute($conn, $sql, $types, $params);
    if ($stmt === false) {
        return false;
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row === null) {
        return null;
    }
    return $row;
}

// INSERT -> nieuw id, of false
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

// UPDATE / DELETE -> aantal veranderde rijen, of false
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
