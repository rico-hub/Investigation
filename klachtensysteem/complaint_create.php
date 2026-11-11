<?php
// =====================================================================
// complaint_create.php - CREATE (iedere ingelogde gebruiker)
// =====================================================================
require_once 'includes/init.php';
require_login();

$data = complaint_empty_data();

if (is_post()) {
    $data = complaint_form_data();

    // user_id komt uit de SESSIE, niet uit het formulier
    $new_id = complaint_create($conn, current_user_id(), $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'complaint_create', 'Klacht #' . $new_id . ': ' . $data['title']);
        set_flash('success', 'Je klacht is ingediend (nummer #' . $new_id . ').');
        redirect('complaint_view.php?id=' . $new_id);
    }
}

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}
$admins = array(); // bij aanmaken kiest niemand een behandelaar

$page_title = 'Nieuwe klacht';
$form_action = 'complaint_create.php';
$button_text = 'Klacht indienen';

require 'includes/header.php';
?>

<h1>Nieuwe klacht</h1>
<?php require 'includes/complaint_form.php'; ?>

<?php require 'includes/footer.php'; ?>
