<?php
// =====================================================================
// functions/cart.php - winkelmand (in de SESSIE, nog niet in de database)
// $_SESSION['cart'] = array met animal-id's, bv. array(3, 7, 9)
// Pas bij "afrekenen" gaat het naar de database (appointments_checkout in appointments.php).
//
// PUZZELSTUK (optioneel). Geen winkelmand nodig? Verwijder dan:
//   functions/cart.php, functions/appointments.php, cart.php, appointments.php,
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

function cart_add($conn, $animal_id)
{
    // Checks (420)
    if (!is_valid_id($animal_id)) {
        return function_error('Ongeldig dier.');
    }
    $animal_id = (int) $animal_id;

    $animal = animal_find($conn, $animal_id, false);
    if ($animal === false) {
        return false; // 410
    }
    if ($animal === null) {
        return function_error('Dit dier bestaat niet.');
    }
    // AANPASSEN: wanneer mag iets in de winkelmand?
    if ($animal['status'] !== 'beschikbaar') {
        return function_error('Dit dier is niet beschikbaar.');
    }

    $cart = cart_get();
    if (in_array($animal_id, $cart, true)) {
        return function_error('Dit dier staat al op je bezoeklijst.');
    }
    if (count($cart) >= MAX_CART) {
        return function_error('Je mag maximaal ' . MAX_CART . ' dieren op je bezoeklijst zetten.');
    }

    $cart[] = $animal_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($animal_id)
{
    $animal_id = (int) $animal_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $animal_id) {
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

// De animals in de winkelmand ophalen met 1 query: WHERE id IN (?, ?, ?)
function cart_get_animals($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    // AANPASSEN: de kolommen die je in de winkelmand wilt zien
    $sql = 'SELECT i.id, i.name, i.age, i.status, c.name AS animal_type_name
            FROM animals i
            INNER JOIN animal_types c ON c.id = i.animal_type_id
            WHERE i.deleted_at IS NULL AND i.id IN (' . db_placeholders(count($cart)) . ')';
    $types = str_repeat('i', count($cart)); // 'iii' voor 3 id's

    return db_select_all($conn, $sql, $types, $cart);
}
