<?php
// =====================================================================
// animal_create.php - CREATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

$data = animal_empty_data();

if (is_post()) {
    $data = animal_form_data(); // ingevulde waarden bewaren (sticky form)

    $new_id = animal_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'animal_create', 'Dier #' . $new_id . ': ' . $data['name']);
        set_flash('success', 'Dier "' . $data['name'] . '" is toegevoegd.');
        redirect('index.php');
    }
    // Fout? GEEN redirect: het formulier hieronder toont de ingevulde waarden + de foutmelding.
}

$animal_types = animal_types_get_all($conn);
if ($animal_types === false) {
    $animal_types = array();
}

$page_title = 'Dier toevoegen';
$form_action = 'animal_create.php';
$button_text = 'Toevoegen';

require 'includes/header.php';
?>

<h1>Dier toevoegen</h1>
<?php require 'includes/animal_form.php'; ?>

<?php require 'includes/footer.php'; ?>
