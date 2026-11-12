<?php
// =====================================================================
// item_edit.php?id=5 - UPDATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

// Check 1: is er een geldig id meegegeven?
$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldig item gekozen.');
    redirect('index.php');
}
$id = (int) $id;

// Check 2: bestaat het item?
$item = item_find($conn, $id, false);
if ($item === false) {
    redirect('index.php'); // 410 staat al in de flash
} elseif ($item === null) {
    function_error('Item #' . $id . ' bestaat niet.');
    redirect('index.php');
}

// Formulier vullen met de huidige waarden uit de database
// AANPASSEN: zelfde keys als item_empty_data() in functions/items.php
$data = array(
    'name' => $item['name'],
    'category_id' => $item['category_id'],
    'color_id' => $item['color_id'],
    'price' => $item['price'],
    'item_date' => (string) $item['item_date'],
    'status' => $item['status'],
);

if (is_post()) {
    $data = item_form_data();

    if (item_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'item_update', 'Item #' . $id . ': ' . $data['name']);
        set_flash('success', 'Item is bijgewerkt.');
        redirect('index.php');
    }
}

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

$colors = colors_get_all($conn);
if ($colors === false) {
    $colors = array();
}

$page_title = 'Item wijzigen';
$form_action = 'item_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Item #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/item_form.php'; ?>

<?php require 'includes/footer.php'; ?>
