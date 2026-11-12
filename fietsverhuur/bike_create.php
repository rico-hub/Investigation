<?php
// =====================================================================
// bike_create.php - CREATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

$data = bike_empty_data();

if (is_post()) {
    $data = bike_form_data(); // ingevulde waarden bewaren (sticky form)

    $new_id = bike_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'bike_create', 'Fiets #' . $new_id . ': ' . $data['name']);
        set_flash('success', 'Fiets "' . $data['name'] . '" is toegevoegd.');
        redirect('index.php');
    }
    // Fout? GEEN redirect: het formulier hieronder toont de ingevulde waarden + de foutmelding.
}

$bike_types = bike_types_get_all($conn);
if ($bike_types === false) {
    $bike_types = array();
}

$page_title = 'Fiets toevoegen';
$form_action = 'bike_create.php';
$button_text = 'Toevoegen';

require 'includes/header.php';
?>

<h1>Fiets toevoegen</h1>
<?php require 'includes/bike_form.php'; ?>

<?php require 'includes/footer.php'; ?>
