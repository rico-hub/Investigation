<?php
// =====================================================================
// functions/loans.php - leningen (afrekenen winkelmand, terugbrengen)
// =====================================================================

// Statusfilter voor leningen (berekend, staat niet als kolom in de tabel)
function loan_statuses()
{
    return array('actief', 'te_laat', 'teruggebracht');
}

function loans_read_filters()
{
    return array(
        'search'  => get_param('search'),
        'status'  => get_param('status'),
        'user_id' => get_param('user_id'), // alleen voor admin
        'sort'    => get_param('sort'),
        'dir'     => get_param('dir'),
    );
}

// $only_user_id = id van de gebruiker (normale user ziet alleen eigen leningen)
//               = null (admin ziet alles)
function loans_get_all($conn, $filters, $only_user_id)
{
    $sql = 'SELECT l.id, l.loan_date, l.due_date, l.returned_at, b.id AS book_id, b.title, u.name AS user_name
            FROM loans l
            INNER JOIN books b ON b.id = l.book_id
            INNER JOIN users u ON u.id = l.user_id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    if ($only_user_id !== null) {
        $sql .= ' AND l.user_id = ?';
        $types .= 'i';
        $params[] = $only_user_id;
    } elseif (is_valid_id($filters['user_id'])) {
        $sql .= ' AND l.user_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['user_id'];
    }

    if ($filters['search'] !== '') {
        $sql .= ' AND b.title LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['search'] . '%';
    }

    // StatusFilter met berekende status
    if ($filters['status'] === 'actief') {
        $sql .= ' AND l.returned_at IS NULL AND l.due_date >= CURDATE()';
    } elseif ($filters['status'] === 'te_laat') {
        $sql .= ' AND l.returned_at IS NULL AND l.due_date < CURDATE()';
    } elseif ($filters['status'] === 'teruggebracht') {
        $sql .= ' AND l.returned_at IS NOT NULL';
    }

    $columns = array('id' => 'l.id', 'title' => 'b.title', 'user' => 'u.name', 'loan_date' => 'l.loan_date', 'due_date' => 'l.due_date');
    $order = 'l.id';
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

// Status van 1 lening uitrekenen voor in de tabel
function loan_status($loan)
{
    if ($loan['returned_at'] !== null) {
        return 'teruggebracht';
    } elseif ($loan['due_date'] < date('Y-m-d')) {
        return 'te_laat';
    } else {
        return 'actief';
    }
}

// Hoeveel boeken heeft deze gebruiker nu geleend? (false bij db fout)
function loans_count_active($conn, $user_id)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM loans WHERE user_id = ? AND returned_at IS NULL', 'i', array($user_id));
    if ($row === false) {
        return false;
    }
    return (int) $row['total'];
}

// Gebruikers voor de admin dropdown
function users_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM users ORDER BY name', '', array());
}

// ---------------------------------------------------------------------
// AFREKENEN: alle boeken uit de winkelmand lenen.
// TRANSACTIE: alles lukt, of er gebeurt niets (rollback).
// Veilig bij meerdere gebruikers: de UPDATE lukt alleen als het boek NOG beschikbaar is.
// ---------------------------------------------------------------------
function loans_checkout($conn, $user_id, $book_ids)
{
    if (count($book_ids) === 0) {
        return function_error('Je winkelmand is leeg.');
    }

    $active = loans_count_active($conn, $user_id);
    if ($active === false) {
        return false;
    }
    if ($active + count($book_ids) > MAX_LOANS) {
        return function_error('Je mag maximaal ' . MAX_LOANS . ' boeken tegelijk lenen. Je hebt er nu ' . $active . '.');
    }

    $conn->begin_transaction();

    foreach ($book_ids as $book_id) {
        // Stap 1: boek op 'uitgeleend' zetten, ALLEEN als het nog 'beschikbaar' is
        $changed = db_change(
            $conn,
            "UPDATE books SET status = 'uitgeleend' WHERE id = ? AND status = 'beschikbaar' AND deleted_at IS NULL",
            'i',
            array($book_id)
        );
        if ($changed === false) {
            $conn->rollback();
            return false;
        }
        if ($changed !== 1) {
            // Iemand anders was sneller (of het boek is verwijderd)
            $conn->rollback();
            return function_error('Boek #' . $book_id . ' is niet meer beschikbaar. Er is niets geleend; haal het boek uit je winkelmand.');
        }

        // Stap 2: lening opslaan (inleveren binnen 21 dagen)
        $loan_id = db_insert(
            $conn,
            'INSERT INTO loans (user_id, book_id, due_date) VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 21 DAY))',
            'ii',
            array($user_id, $book_id)
        );
        if ($loan_id === false) {
            $conn->rollback();
            return false;
        }
    }

    $conn->commit();
    return true;
}

// ---------------------------------------------------------------------
// TERUGBRENGEN. Eigen lening, of admin mag alles.
// ---------------------------------------------------------------------
function loan_return($conn, $loan_id, $user_id, $user_is_admin)
{
    if (!is_valid_id($loan_id)) {
        return function_error('Ongeldige lening.');
    }

    $loan = db_select_one($conn, 'SELECT id, user_id, book_id, returned_at FROM loans WHERE id = ?', 'i', array((int) $loan_id));
    if ($loan === false) {
        return false;
    }
    if ($loan === null) {
        return function_error('Lening niet gevonden.');
    }
    // Rechten check: niet andermans boek terugbrengen
    if (!$user_is_admin && (int) $loan['user_id'] !== $user_id) {
        return function_error('Dit is niet jouw lening.');
    }
    if ($loan['returned_at'] !== null) {
        return function_error('Dit boek is al teruggebracht.');
    }

    $conn->begin_transaction();

    $changed = db_change($conn, 'UPDATE loans SET returned_at = NOW() WHERE id = ? AND returned_at IS NULL', 'i', array((int) $loan['id']));
    if ($changed === false || $changed !== 1) {
        $conn->rollback();
        if ($changed === false) {
            return false;
        }
        return function_error('Dit boek is net al teruggebracht.');
    }

    $changed = db_change($conn, "UPDATE books SET status = 'beschikbaar' WHERE id = ?", 'i', array((int) $loan['book_id']));
    if ($changed === false) {
        $conn->rollback();
        return false;
    }

    $conn->commit();
    return true;
}
