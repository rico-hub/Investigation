<?php
// =====================================================================
// functions/database.php - prepared statements in 1 regel
// Alle checks voor Error-code 410 zitten HIER, zodat je ze nergens vergeet.
// De uitgeschreven (lange) versie staat in notes/03-mysqli-oop.md
// =====================================================================

// Voert een query uit met prepared statement.
// $types  = 's' string, 'i' integer, 'd' decimal/float, 'b' blob   bv. 'si'
// $params = array met waarden, zelfde volgorde als de ? in de query
// Geeft het $stmt terug (succes) of false (fout, flash met 410 is gezet).
function db_execute($conn, $sql, $types, $params)
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return database_error('Query voorbereiden mislukt', $conn->error);
    }

    if ($types !== '') {
        // ...$params = "spread": array uitpakken naar losse argumenten
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        return database_error('Query uitvoeren mislukt', $error);
    }

    return $stmt;
}

// SELECT met meerdere rijen -> array met rijen (kan leeg zijn), of false bij fout.
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

// SELECT met 1 rij -> de rij (array), null als niet gevonden, false bij fout.
// Controleer daarom met === : if ($row === false) ... elseif ($row === null) ...
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

// INSERT/UPDATE/DELETE -> aantal geraakte rijen (0 of meer), of false bij fout.
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

// INSERT -> het nieuwe id (AUTO_INCREMENT), of false bij fout.
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

// Maakt "?, ?, ?" voor een IN (...) lijst. count = aantal vraagtekens.
function db_placeholders($count)
{
    return implode(', ', array_fill(0, $count, '?'));
}
