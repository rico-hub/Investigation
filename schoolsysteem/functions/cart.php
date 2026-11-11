<?php
// =====================================================================
// functions/cart.php - SELECTIE leerlingen (docent)
// Docent kiest leerlingen in $_SESSION['cart'] en voert daarna in 1 keer
// een toets in voor allemaal. Daarna wordt de selectie gewist.
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

function cart_add($conn, $student_id)
{
    if (!is_valid_id($student_id)) {
        return function_error('Ongeldige leerling.');
    }
    $student_id = (int) $student_id;

    $student = student_find($conn, $student_id);
    if ($student === false) {
        return false;
    }
    if ($student === null) {
        return function_error('Deze leerling bestaat niet.');
    }

    $cart = cart_get();
    if (in_array($student_id, $cart, true)) {
        return function_error($student['name'] . ' zit al in je selectie.');
    }
    if (count($cart) >= 40) {
        return function_error('Je selectie is vol (max. 40).');
    }

    $cart[] = $student_id;
    $_SESSION['cart'] = $cart;
    return true;
}

// Hele klas in 1 keer toevoegen
function cart_add_class($conn, $class_id)
{
    if (!is_valid_id($class_id)) {
        return function_error('Kies eerst een klas.');
    }
    $students = students_get_all($conn, $class_id, '');
    if ($students === false) {
        return false;
    }
    if (count($students) === 0) {
        return function_error('Deze klas heeft geen leerlingen.');
    }

    $cart = cart_get();
    $added = 0;
    foreach ($students as $student) {
        if (!in_array((int) $student['id'], $cart, true)) {
            $cart[] = (int) $student['id'];
            $added = $added + 1;
        }
    }
    $_SESSION['cart'] = $cart;
    return $added;
}

function cart_remove($student_id)
{
    $student_id = (int) $student_id;
    $new_cart = array();
    foreach (cart_get() as $id) {
        if ($id !== $student_id) {
            $new_cart[] = $id;
        }
    }
    $_SESSION['cart'] = $new_cart;
}

function cart_clear()
{
    unset($_SESSION['cart']);
}

function cart_get_students($conn)
{
    $cart = cart_get();
    if (count($cart) === 0) {
        return array();
    }

    $sql = "SELECT u.id, u.name, c.name AS class_name
            FROM users u
            LEFT JOIN classes c ON c.id = u.class_id
            WHERE u.role = 'user' AND u.id IN (" . db_placeholders(count($cart)) . ')
            ORDER BY c.name, u.name';

    return db_select_all($conn, $sql, str_repeat('i', count($cart)), $cart);
}
