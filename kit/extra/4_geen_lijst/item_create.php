<?php
// =====================================================================
// item_create.php - CREATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

$data = item_empty_data();

if (is_post()) {
    $data = item_form_data(); // ingevulde waarden bewaren (sticky form)

    $new_id = item_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'item_create', 'Item #' . $new_id . ': ' . $data['name']);
        set_flash('success', 'Item "' . $data['name'] . '" is toegevoegd.');
        redirect('index.php');
    }
    // Fout? GEEN redirect: het formulier hieronder toont de ingevulde waarden + de foutmelding.
}

$page_title = 'Item toevoegen';
$form_action = 'item_create.php';
$button_text = 'Toevoegen';

require 'includes/header.php';
?>

<h1>Item toevoegen</h1>
<?php require 'includes/item_form.php'; ?>

<?php require 'includes/footer.php'; ?>
