<?php
// functions/categories.php

function categories_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM categories ORDER BY name', '', array());
}

function category_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM categories WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}

// Admins voor de dropdown "toegewezen aan"
function admins_get_all($conn)
{
    return db_select_all($conn, "SELECT id, name FROM users WHERE role = 'admin' ORDER BY name", '', array());
}

function admin_exists($conn, $id)
{
    $row = db_select_one($conn, "SELECT id FROM users WHERE id = ? AND role = 'admin'", 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}
