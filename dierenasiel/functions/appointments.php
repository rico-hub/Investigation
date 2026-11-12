<?php
// =====================================================================
// functions/appointments.php - bestellingen = de KOPPELTABEL tussen users en animals
// appointments heeft 2 foreign keys: user_id -> users.id  en  animal_id -> animals.id
// PUZZELSTUK (optioneel, hoort bij de winkelmand).
// =====================================================================

// Afrekenen: alle animals uit de winkelmand als rijen in appointments zetten.
// Alles of niets: gaat er 1 fout, dan wordt ALLES teruggedraaid (transactie).
function appointments_checkout($conn, $user_id, $animal_ids, $booking_date)
{
    if (count($animal_ids) === 0) {
        return function_error('Je bezoeklijst is leeg.');
    }

    // Checks voor de datum (Error-code 420)
    if (!is_valid_date($booking_date)) {
        return function_error('Kies een geldige datum.');
    }
    if ($booking_date < date('Y-m-d')) {
        return function_error('De datum mag niet in het verleden liggen.');
    }

    foreach ($animal_ids as $animal_id) {
        // Bestaat het dier nog en is het nog beschikbaar? (kan veranderd zijn sinds je het op je lijst zette)
        $animal = animal_find($conn, (int) $animal_id, false);
        if ($animal === false) {
            return false;
        }
        if ($animal === null || $animal['status'] !== 'beschikbaar') {
            return function_error('Dier #' . $animal_id . ' is niet meer beschikbaar. Haal het van je bezoeklijst.');
        }

        // Heeft het dier op die dag al een afspraak?
        $row = db_select_one(
            $conn,
            'SELECT COUNT(*) AS total FROM appointments WHERE animal_id = ? AND booking_date = ?',
            'is',
            array((int) $animal_id, $booking_date)
        );
        if ($row === false) {
            return false;
        }
        if ((int) $row['total'] > 0) {
            return function_error('Dier #' . $animal_id . ' heeft op die dag al een afspraak.');
        }
    }

    $conn->begin_transaction();

    foreach ($animal_ids as $animal_id) {
        $new_id = db_insert(
            $conn,
            'INSERT INTO appointments (user_id, animal_id, booking_date) VALUES (?, ?, ?)',
            'iis',
            array($user_id, (int) $animal_id, $booking_date)
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
function appointments_get_all($conn, $only_user_id)
{
    // JOIN via de 2 foreign keys: zo haal je de naam van de user en van het animal erbij
    $sql = 'SELECT o.id, o.created_at, o.booking_date, u.name AS user_name, i.name AS animal_name, i.age
            FROM appointments o
            INNER JOIN users u ON u.id = o.user_id
            INNER JOIN animals i ON i.id = o.animal_id';
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
