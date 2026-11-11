<?php
// =====================================================================
// functions/cart.php - WERKLIJST voor de admin (= winkelmand)
// De admin "pakt" klachten op in $_SESSION['cart'] en past ze daarna
// in 1 keer aan (bulk update). Daarna wordt de werklijst gewist.
// =====================================================================

function cart_get()
{
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return $_SESSION['cart'];
    }
    return array();
}

function cart_count()
{
    return count(cart_get());
}

function cart_add($conn, $complaint_id)
{
    if (!is_valid_id($complaint_id)) {
        return function_error('Ongeldige klacht.');
    }
    $complaint_id = (int) $complaint_id;

    $complaint = complaint_find($conn, $complaint_id, false);
    if ($complaint === false) {
        return false;
    }
    if ($complaint === null) {
        return function_error('Klacht bestaat niet.');
    }

    $cart = cart_get();
    if (in_array($complaint_id, $cart, true)) {
        return function_error('Deze klacht staat al op je werklijst.');
    }
    if (count($cart) >= 20) {
        return function_error('Je werklijst is vol (max. 20).');
    }

    $cart[] = $complaint_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($complaint_id)
{
    $complaint_id = (int) $complaint_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $complaint_id) {
            $new_cart[] = $id;
        }
    }
    $_SESSION['cart'] = $new_cart;
}

function cart_clear()
{
    unset($_SESSION['cart']);
}

function cart_get_items($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    $sql = 'SELECT k.id, k.title, k.status, k.priority, u.name AS user_name
            FROM complaints k
            INNER JOIN users u ON u.id = k.user_id
            WHERE k.deleted_at IS NULL AND k.id IN (' . db_placeholders(count($cart)) . ')';

    return db_select_all($conn, $sql, str_repeat('i', count($cart)), $cart);
}
