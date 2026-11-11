<?php
// lesson_create.php - CREATE (admin)
require_once 'includes/init.php';
require_admin();

$data = lesson_empty_data();

if (is_post()) {
    $data = lesson_form_data();

    $new_id = lesson_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'lesson_create', 'Les #' . $new_id . ': ' . $data['title']);
        set_flash('success', 'Les "' . $data['title'] . '" is toegevoegd.');
        redirect('index.php');
    }
}

$sports = sports_get_all($conn);
if ($sports === false) {
    $sports = array();
}

$page_title = 'Les toevoegen';
$form_action = 'lesson_create.php';
$button_text = 'Toevoegen';

require 'includes/header.php';
?>

<h1>Les toevoegen</h1>
<?php require 'includes/lesson_form.php'; ?>

<?php require 'includes/footer.php'; ?>
