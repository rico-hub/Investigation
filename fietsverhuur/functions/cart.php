<?php
// =====================================================================
// functions/cart.php - winkelmand (in de SESSIE, nog niet in de database)
// $_SESSION['cart'] = array met bike-id's, bv. array(3, 7, 9)
// Pas bij "afrekenen" gaat het naar de database (rentals_checkout in rentals.php).
//
// PUZZELSTUK (optioneel). Geen winkelmand nodig? Verwijder dan:
//   functions/cart.php, functions/rentals.php, cart.php, rentals.php,
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

function cart_add($conn, $bike_id)
{
    // Checks (420)
    if (!is_valid_id($bike_id)) {
        return function_error('Ongeldige fiets.');
    }
    $bike_id = (int) $bike_id;

    $bike = bike_find($conn, $bike_id, false);
    if ($bike === false) {
        return false; // 410
    }
    if ($bike === null) {
        return function_error('Deze fiets bestaat niet.');
    }
    // AANPASSEN: wanneer mag iets in de winkelmand?
    if ($bike['status'] !== 'beschikbaar') {
        return function_error('Deze fiets is niet beschikbaar.');
    }

    $cart = cart_get();
    if (in_array($bike_id, $cart, true)) {
        return function_error('Deze fiets zit al in je winkelmand.');
    }
    if (count($cart) >= MAX_CART) {
        return function_error('Je mag maximaal ' . MAX_CART . ' fietsen in je winkelmand hebben.');
    }

    $cart[] = $bike_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($bike_id)
{
    $bike_id = (int) $bike_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $bike_id) {
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

// De bikes in de winkelmand ophalen met 1 query: WHERE id IN (?, ?, ?)
function cart_get_bikes($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    // AANPASSEN: de kolommen die je in de winkelmand wilt zien
    $sql = 'SELECT i.id, i.name, i.price, i.status, c.name AS bike_type_name
            FROM bikes i
            INNER JOIN bike_types c ON c.id = i.bike_type_id
            WHERE i.deleted_at IS NULL AND i.id IN (' . db_placeholders(count($cart)) . ')';
    $types = str_repeat('i', count($cart)); // 'iii' voor 3 id's

    return db_select_all($conn, $sql, $types, $cart);
}
