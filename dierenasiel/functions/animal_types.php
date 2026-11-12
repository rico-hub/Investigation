<?php
// =====================================================================
// functions/animal_types.php - de "parent" tabel (voor de dropdown)
// PUZZELSTUK: heb je een andere parent (bv. brands)? Kopieer dit bestand,
// vervang animal_type/animal_types door jouw woord en zet het in includes/init.php.
// =====================================================================

// Alle categorieen, voor de dropdowns
function animal_types_get_all($conn)
{
    return db_select_all($conn, 'SELECT id, name FROM animal_types ORDER BY name', '', array());
}

// Bestaat deze categorie? (check voor je een animal opslaat, anders krijg je fout 1452)
function animal_type_exists($conn, $id)
{
    $row = db_select_one($conn, 'SELECT id FROM animal_types WHERE id = ?', 'i', array($id));
    if ($row === false || $row === null) {
        return false;
    }
    return true;
}
