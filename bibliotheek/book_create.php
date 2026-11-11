<?php
// =====================================================================
// book_create.php - CREATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

$data = book_empty_data();

if (is_post()) {
    $data = book_form_data(); // ingevulde waarden bewaren (sticky form)

    $new_id = book_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'book_create', 'Boek #' . $new_id . ': ' . $data['title']);
        set_flash('success', 'Boek "' . $data['title'] . '" is toegevoegd.');
        redirect('index.php');
    }
    // Fout? GEEN redirect: het formulier hieronder toont de ingevulde waarden + de foutmelding.
}

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

$page_title = 'Boek toevoegen';
$form_action = 'book_create.php';
$button_text = 'Toevoegen';

require 'includes/header.php';
?>

<h1>Boek toevoegen</h1>
<?php require 'includes/book_form.php'; ?>

<?php require 'includes/footer.php'; ?>
