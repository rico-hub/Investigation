<?php
// =====================================================================
// functions/categories.php
// =====================================================================

// Voor de dropdowns
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
