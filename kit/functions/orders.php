<?php
// =====================================================================
// functions/orders.php - bestellingen = de KOPPELTABEL tussen users en items
// orders heeft 2 foreign keys: user_id -> users.id  en  item_id -> items.id
// PUZZELSTUK (optioneel, hoort bij de winkelmand).
// =====================================================================

// Afrekenen: alle items uit de winkelmand als rijen in orders zetten.
// Alles of niets: gaat er 1 fout, dan wordt ALLES teruggedraaid (transactie).
function orders_checkout($conn, $user_id, $item_ids)
{
    if (count($item_ids) === 0) {
        return function_error('Je winkelmand is leeg.');
    }

    $conn->begin_transaction();

    foreach ($item_ids as $item_id) {
        $new_id = db_insert(
            $conn,
            'INSERT INTO orders (user_id, item_id) VALUES (?, ?)',
            'ii',
            array($user_id, (int) $item_id)
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
function orders_get_all($conn, $only_user_id)
{
    // JOIN via de 2 foreign keys: zo haal je de naam van de user en van het item erbij
    $sql = 'SELECT o.id, o.created_at, u.name AS user_name, i.name AS item_name, i.price
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            INNER JOIN items i ON i.id = o.item_id';
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
