<?php
// =====================================================================
// functions/rentals.php - bestellingen = de KOPPELTABEL tussen users en bikes
// rentals heeft 2 foreign keys: user_id -> users.id  en  bike_id -> bikes.id
// PUZZELSTUK (optioneel, hoort bij de winkelmand).
// =====================================================================

// Afrekenen: alle bikes uit de winkelmand als rijen in rentals zetten.
// Alles of niets: gaat er 1 fout, dan wordt ALLES teruggedraaid (transactie).
function rentals_checkout($conn, $user_id, $bike_ids, $start_date, $end_date)
{
    if (count($bike_ids) === 0) {
        return function_error('Je winkelmand is leeg.');
    }

    // Checks voor de datums (Error-code 420)
    if (!is_valid_date($start_date) || !is_valid_date($end_date)) {
        return function_error('Kies een geldige begin- en einddatum.');
    }
    if ($start_date < date('Y-m-d')) {
        return function_error('De begindatum mag niet in het verleden liggen.');
    }
    if ($end_date <= $start_date) {
        return function_error('De einddatum moet na de begindatum liggen.');
    }

    // Is het al bezet in die periode? (overlap = begint voor jouw einde EN eindigt na jouw begin)
    foreach ($bike_ids as $bike_id) {
        $row = db_select_one(
            $conn,
            'SELECT COUNT(*) AS total FROM rentals WHERE bike_id = ? AND start_date < ? AND end_date > ?',
            'iss',
            array((int) $bike_id, $end_date, $start_date)
        );
        if ($row === false) {
            return false;
        }
        if ((int) $row['total'] > 0) {
            return function_error('Fiets #' . $bike_id . ' is in die periode al verhuurd.');
        }
    }

    $conn->begin_transaction();

    foreach ($bike_ids as $bike_id) {
        $new_id = db_insert(
            $conn,
            'INSERT INTO rentals (user_id, bike_id, start_date, end_date) VALUES (?, ?, ?, ?)',
            'iiss',
            array($user_id, (int) $bike_id, $start_date, $end_date)
        );
        if ($new_id === false) {
            $conn->rollback(); // alles terugdraaien (410 staat al in de flash)
            return false;
        }
    }

    $conn->commit();
    return true;
}

// $only_user_id = id van de gebruiker (normale user ziet alleen eigen bestellingen)
//               = null (admin ziet alles)
function rentals_get_all($conn, $only_user_id)
{
    // JOIN via de 2 foreign keys: zo haal je de naam van de klant en van de fiets erbij
    // BONUS: DATEDIFF = aantal dagen tussen 2 datums. Dagen x prijs per dag = totaal.
    $sql = 'SELECT o.id, o.created_at, o.start_date, o.end_date, u.name AS user_name, i.name AS bike_name, i.frame_number, i.price,
                   DATEDIFF(o.end_date, o.start_date) AS days,
                   DATEDIFF(o.end_date, o.start_date) * i.price AS total
            FROM rentals o
            INNER JOIN users u ON u.id = o.user_id
            INNER JOIN bikes i ON i.id = o.bike_id';
    $types = '';
    $params = array();

    if ($only_user_id !== null) {
        $sql .= ' WHERE o.user_id = ?';
        $types .= 'i';
        $params[] = $only_user_id;
    }

    $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

    return db_select_all($conn, $sql, $types, $params);
}
