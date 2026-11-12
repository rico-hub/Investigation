<?php
// =====================================================================
// functions/colors.php - de "parent" tabel (voor de dropdown)
// PUZZELSTUK: heb je een andere parent (bv. brands)? Kopieer dit bestand,
// vervang color/colors door jouw woord en zet het in includes/init.php.
// =====================================================================

// Alle categorieen, voor de dropdowns
function colors_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM colors ORDER BY name', '', array());
}

// Bestaat deze categorie? (check voor je een item opslaat, anders krijg je fout 1452)
function color_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM colors WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}
