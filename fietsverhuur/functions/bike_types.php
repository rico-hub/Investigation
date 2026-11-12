<?php
// =====================================================================
// functions/bike_types.php - de "parent" tabel (voor de dropdown)
// PUZZELSTUK: heb je een andere parent (bv. brands)? Kopieer dit bestand,
// vervang bike_type/bike_types door jouw woord en zet het in includes/init.php.
// =====================================================================

// Alle categorieen, voor de dropdowns
function bike_types_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM bike_types ORDER BY name', '', array());
}

// Bestaat deze categorie? (check voor je een bike opslaat, anders krijg je fout 1452)
function bike_type_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM bike_types WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}
