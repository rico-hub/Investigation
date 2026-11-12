<?php
// =====================================================================
// animal_edit.php?id=5 - UPDATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

// Check 1: is er een geldig id meegegeven?
$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldig dier gekozen.');
    redirect('index.php');
}
$id = (int) $id;

// Check 2: bestaat het animal?
$animal = animal_find($conn, $id, false);
if ($animal === false) {
    redirect('index.php'); // 410 staat al in de flash
} elseif ($animal === null) {
    function_error('Dier #' . $id . ' bestaat niet.');
    redirect('index.php');
}

// Formulier vullen met de huidige waarden uit de database
// AANPASSEN: zelfde keys als animal_empty_data() in functions/animals.php
$data = array(
    'name' => $animal['name'],
    'animal_type_id' => $animal['animal_type_id'],
    'age' => (string) $animal['age'],
    'status' => $animal['status'],
);

if (is_post()) {
    $data = animal_form_data();

    if (animal_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'animal_update', 'Dier #' . $id . ': ' . $data['name']);
        set_flash('success', 'Dier is bijgewerkt.');
        redirect('index.php');
    }
}

$animal_types = animal_types_get_all($conn);
if ($animal_types === false) {
    $animal_types = array();
}

$page_title = 'Dier wijzigen';
$form_action = 'animal_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Dier #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/animal_form.php'; ?>

<?php require 'includes/footer.php'; ?>
