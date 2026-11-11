<?php
// grade_create.php - 1 cijfer invoeren (docent). Meerdere tegelijk: via Leerlingen > Selectie.
require_once 'includes/init.php';
require_admin();

$data = grade_empty_data();

// Vanuit het rapport: grade_create.php?student_id=2 -> leerling alvast gekozen
if (is_valid_id(get_param('student_id'))) {
    $data['student_id'] = get_param('student_id');
}

if (is_post()) {
    $data = grade_form_data();

    // teacher_id = de ingelogde docent (uit de sessie)
    $new_id = grade_create($conn, $data, current_user_id());
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'grade_create', 'Cijfer #' . $new_id . ' (' . $data['grade'] . ') voor leerling #' . $data['student_id']);
        set_flash('success', 'Cijfer opgeslagen.');
        redirect('index.php');
    }
}

$students = students_get_all($conn, '', '');
if ($students === false) {
    $students = array();
}
$subjects = subjects_get_all($conn);
if ($subjects === false) {
    $subjects = array();
}

$page_title = 'Cijfer invoeren';
$form_action = 'grade_create.php';
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Cijfer invoeren</h1>
<?php require 'includes/grade_form.php'; ?>

<?php require 'includes/footer.php'; ?>
