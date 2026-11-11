<?php
// =====================================================================
// functions/registrations.php - inschrijvingen (koppeltabel users <-> lessons)
// =====================================================================

// Id's van de lessen waar deze user al voor ingeschreven is -> array(1, 2, 7)
function registrations_get_lesson_ids($conn, $user_id)
{
    $rows = db_select_all($conn, 'SELECT lesson_id FROM registrations WHERE user_id = ?', 'i', array($user_id));
    $ids = array();
    if ($rows === false) {
        return $ids;
    }
    foreach ($rows as $row) {
        $ids[] = (int) $row['lesson_id'];
    }
    return $ids;
}

// Mijn inschrijvingen. Statusfilter: 'komend' of 'afgelopen'.
function registrations_get_by_user($conn, $user_id, $status)
{
    $sql = 'SELECT r.id, r.created_at, l.id AS lesson_id, l.title, l.trainer, l.lesson_date, l.start_time,
                   l.status AS lesson_status, s.name AS sport_name
            FROM registrations r
            INNER JOIN lessons l ON l.id = r.lesson_id
            INNER JOIN sports s ON s.id = l.sport_id
            WHERE r.user_id = ?';

    if ($status === 'komend') {
        $sql .= ' AND l.lesson_date >= CURDATE()';
    } elseif ($status === 'afgelopen') {
        $sql .= ' AND l.lesson_date < CURDATE()';
    }

    $sql .= ' ORDER BY l.lesson_date ASC, l.start_time ASC';

    return db_select_all($conn, $sql, 'i', array($user_id));
}

// Deelnemers van 1 les (admin)
function registrations_get_by_lesson($conn, $lesson_id)
{
    return db_select_all(
        $conn,
        'SELECT r.id, r.created_at, u.id AS user_id, u.name, u.email
         FROM registrations r
         INNER JOIN users u ON u.id = r.user_id
         WHERE r.lesson_id = ?
         ORDER BY r.created_at ASC',
        'i',
        array($lesson_id)
    );
}

// ---------------------------------------------------------------------
// AFREKENEN: inschrijven voor alle lessen in de winkelmand.
// Veilig bij meerdere gebruikers tegelijk:
//   SELECT ... FOR UPDATE zet een LOCK op de les-rij tot COMMIT/ROLLBACK.
//   Iemand anders die tegelijk inschrijft moet wachten -> nooit meer
//   inschrijvingen dan max_participants.
// ---------------------------------------------------------------------
function registrations_checkout($conn, $user_id, $lesson_ids)
{
    if (count($lesson_ids) === 0) {
        return function_error('Je winkelmand is leeg.');
    }

    $conn->begin_transaction();

    foreach ($lesson_ids as $lesson_id) {
        // 1. Les ophalen EN locken
        $lesson = db_select_one(
            $conn,
            'SELECT id, title, lesson_date, max_participants, status FROM lessons WHERE id = ? AND deleted_at IS NULL FOR UPDATE',
            'i',
            array($lesson_id)
        );
        if ($lesson === false) {
            $conn->rollback();
            return false;
        }
        if ($lesson === null) {
            $conn->rollback();
            return function_error('Les #' . $lesson_id . ' bestaat niet meer.');
        }
        if ($lesson['status'] !== 'gepland' || $lesson['lesson_date'] < date('Y-m-d')) {
            $conn->rollback();
            return function_error('Les "' . $lesson['title'] . '" is geannuleerd of al geweest.');
        }

        // 2. Al ingeschreven?
        $existing = db_select_one($conn, 'SELECT id FROM registrations WHERE user_id = ? AND lesson_id = ?', 'ii', array($user_id, $lesson_id));
        if ($existing === false) {
            $conn->rollback();
            return false;
        }
        if ($existing !== null) {
            $conn->rollback();
            return function_error('Je bent al ingeschreven voor "' . $lesson['title'] . '".');
        }

        // 3. Nog plek?
        $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM registrations WHERE lesson_id = ?', 'i', array($lesson_id));
        if ($row === false) {
            $conn->rollback();
            return false;
        }
        if ((int) $row['total'] >= (int) $lesson['max_participants']) {
            $conn->rollback();
            return function_error('Les "' . $lesson['title'] . '" is net vol geraakt. Er is niets opgeslagen.');
        }

        // 4. Inschrijven
        $new_id = db_insert($conn, 'INSERT INTO registrations (user_id, lesson_id) VALUES (?, ?)', 'ii', array($user_id, $lesson_id));
        if ($new_id === false) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return true;
}

// Uitschrijven (HARD delete: een inschrijving hoeft geen geschiedenis te hebben)
// User: alleen eigen en alleen voor komende lessen. Admin: alles.
function registration_cancel($conn, $registration_id, $user_id, $user_is_admin)
{
    if (!is_valid_id($registration_id)) {
        return function_error('Ongeldige inschrijving.');
    }

    $registration = db_select_one(
        $conn,
        'SELECT r.id, r.user_id, l.lesson_date, l.title
         FROM registrations r
         INNER JOIN lessons l ON l.id = r.lesson_id
         WHERE r.id = ?',
        'i',
        array((int) $registration_id)
    );
    if ($registration === false) {
        return false;
    }
    if ($registration === null) {
        return function_error('Inschrijving niet gevonden.');
    }
    if (!$user_is_admin) {
        if ((int) $registration['user_id'] !== $user_id) {
            return function_error('Dit is niet jouw inschrijving.');
        }
        if ($registration['lesson_date'] < date('Y-m-d')) {
            return function_error('Deze les is al geweest.');
        }
    }

    $result = db_change($conn, 'DELETE FROM registrations WHERE id = ?', 'i', array((int) $registration_id));
    if ($result === false) {
        return false;
    }
    return true;
}
