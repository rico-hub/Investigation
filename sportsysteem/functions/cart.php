<?php
// =====================================================================
// functions/cart.php - winkelmand met gekozen LESSEN (in de sessie)
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

function cart_add($conn, $lesson_id)
{
    if (!is_valid_id($lesson_id)) {
        return function_error('Ongeldige les.');
    }
    $lesson_id = (int) $lesson_id;

    $lesson = lesson_find($conn, $lesson_id);
    if ($lesson === false) {
        return false;
    }
    if ($lesson === null) {
        return function_error('Deze les bestaat niet.');
    }
    if (lesson_status($lesson) !== 'open_inschrijving') {
        return function_error('Voor deze les kun je je niet inschrijven (vol, geannuleerd of geweest).');
    }
    if (in_array($lesson_id, registrations_get_lesson_ids($conn, current_user_id()), true)) {
        return function_error('Je bent al ingeschreven voor deze les.');
    }

    $cart = cart_get();
    if (in_array($lesson_id, $cart, true)) {
        return function_error('Deze les zit al in je winkelmand.');
    }
    if (count($cart) >= MAX_CART) {
        return function_error('Je kunt maximaal ' . MAX_CART . ' lessen tegelijk kiezen.');
    }

    $cart[] = $lesson_id;
    $_SESSION['cart'] = $cart;
    return true;
}

function cart_remove($lesson_id)
{
    $lesson_id = (int) $lesson_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $lesson_id) {
            $new_cart[] = $id;
        }
    }
    $_SESSION['cart'] = $new_cart;
}

function cart_clear()
{
    unset($_SESSION['cart']);
}

function cart_get_lessons($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    $sql = 'SELECT l.id, l.title, l.trainer, l.lesson_date, l.start_time, l.max_participants, l.status,
                   s.name AS sport_name, COUNT(r.id) AS registered
            FROM lessons l
            INNER JOIN sports s ON s.id = l.sport_id
            LEFT JOIN registrations r ON r.lesson_id = l.id
            WHERE l.deleted_at IS NULL AND l.id IN (' . db_placeholders(count($cart)) . ')
            GROUP BY l.id, s.name
            ORDER BY l.lesson_date, l.start_time';

    return db_select_all($conn, $sql, str_repeat('i', count($cart)), $cart);
}
