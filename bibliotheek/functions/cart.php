<?php
// =====================================================================
// functions/cart.php - winkelmand (in de SESSIE, nog niet in de database)
// $_SESSION['cart'] = array met boek-id's, bv. array(3, 7, 9)
// Pas bij "afrekenen" gaat het naar de database (loans_checkout).
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

function cart_add($conn, $book_id)
{
    // Checks (420)
    if (!is_valid_id($book_id)) {
        return function_error('Ongeldig boek.');
    }
    $book_id = (int) $book_id;

    $book = book_find($conn, $book_id, false);
    if ($book === false) {
        return false; // 410
    }
    if ($book === null) {
        return function_error('Dit boek bestaat niet.');
    }
    if ($book['status'] !== 'beschikbaar') {
        return function_error('Dit boek is niet beschikbaar.');
    }

    $cart = cart_get();
    if (in_array($book_id, $cart, true)) {
        return function_error('Dit boek zit al in je winkelmand.');
    }

    $active = loans_count_active($conn, current_user_id());
    if ($active === false) {
        return false;
    }
    if ($active + count($cart) >= MAX_LOANS) {
        return function_error('Je mag maximaal ' . MAX_LOANS . ' boeken tegelijk lenen (geleend + winkelmand).');
    }

    $cart[] = $book_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($book_id)
{
    $book_id = (int) $book_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $book_id) {
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

// De boeken in de winkelmand ophalen met 1 query: WHERE id IN (?, ?, ?)
function cart_get_books($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    $sql = 'SELECT b.id, b.title, b.author, b.status, c.name AS category_name
            FROM books b
            INNER JOIN categories c ON c.id = b.category_id
            WHERE b.deleted_at IS NULL AND b.id IN (' . db_placeholders(count($cart)) . ')';
    $types = str_repeat('i', count($cart)); // 'iii' voor 3 id's

    return db_select_all($conn, $sql, $types, $cart);
}
