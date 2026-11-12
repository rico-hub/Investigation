<?php
// =====================================================================
// bike_edit.php?id=5 - UPDATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

// Check 1: is er een geldig id meegegeven?
$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige fiets gekozen.');
    redirect('index.php');
}
$id = (int) $id;

// Check 2: bestaat het bike?
$bike = bike_find($conn, $id, false);
if ($bike === false) {
    redirect('index.php'); // 410 staat al in de flash
} elseif ($bike === null) {
    function_error('Fiets #' . $id . ' bestaat niet.');
    redirect('index.php');
}

// Formulier vullen met de huidige waarden uit de database
// AANPASSEN: zelfde keys als bike_empty_data() in functions/bikes.php
$data = array(
    'name' => $bike['name'],
    'bike_type_id' => $bike['bike_type_id'],
    'price' => $bike['price'],
    'frame_number' => $bike['frame_number'],
    'status' => $bike['status'],
);

if (is_post()) {
    $data = bike_form_data();

    if (bike_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'bike_update', 'Fiets #' . $id . ': ' . $data['name']);
        set_flash('success', 'Fiets is bijgewerkt.');
        redirect('index.php');
    }
}

$bike_types = bike_types_get_all($conn);
if ($bike_types === false) {
    $bike_types = array();
}

$page_title = 'Fiets wijzigen';
$form_action = 'bike_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Fiets #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/bike_form.php'; ?>

<?php require 'includes/footer.php'; ?>
