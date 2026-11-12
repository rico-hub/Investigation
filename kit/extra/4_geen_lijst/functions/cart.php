<?php
// =====================================================================
// functions/cart.php - winkelmand (in de SESSIE, nog niet in de database)
// $_SESSION['cart'] = array met item-id's, bv. array(3, 7, 9)
// Pas bij "afrekenen" gaat het naar de database (orders_checkout in orders.php).
//
// PUZZELSTUK (optioneel). Geen winkelmand nodig? Verwijder dan:
//   functions/cart.php, functions/orders.php, cart.php, orders.php,
//   de 2 regels in includes/init.php, het cart-blok in config/site.php
//   en het "+ Winkelmand" formulier in index.php.
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

function cart_add($conn, $item_id)
{
    // Checks (420)
    if (!is_valid_id($item_id)) {
        return function_error('Ongeldig item.');
    }
    $item_id = (int) $item_id;

    $item = item_find($conn, $item_id, false);
    if ($item === false) {
        return false; // 410
    }
    if ($item === null) {
        return function_error('Dit item bestaat niet.');
    }
    // AANPASSEN: wanneer mag iets in de winkelmand?
    if ($item['status'] !== 'actief') {
        return function_error('Dit item is niet beschikbaar.');
    }

    $cart = cart_get();
    if (in_array($item_id, $cart, true)) {
        return function_error('Dit item zit al in je winkelmand.');
    }
    if (count($cart) >= MAX_CART) {
        return function_error('Je mag maximaal ' . MAX_CART . ' items in je winkelmand hebben.');
    }

    $cart[] = $item_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($item_id)
{
    $item_id = (int) $item_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $item_id) {
            $new_cart[] = $id;
        }
    }
    $_SESSION['cart'] = $new_cart;
}

// Klaar? Winkelmand uit de sessie halen.
function cart_clear()
{
    unset($_SESSION['cart']);
}

// De items in de winkelmand ophalen met 1 query: WHERE id IN (?, ?, ?)
function cart_get_items($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    // AANPASSEN: de kolommen die je in de winkelmand wilt zien
    $sql = 'SELECT i.id, i.name, i.price, i.status
            FROM items i
            WHERE i.deleted_at IS NULL AND i.id IN (' . db_placeholders(count($cart)) . ')';
    $types = str_repeat('i', count($cart)); // 'iii' voor 3 id's

    return db_select_all($conn, $sql, $types, $cart);
}
