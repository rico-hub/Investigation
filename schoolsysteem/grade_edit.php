<?php
// grade_edit.php?id=4 - UPDATE (docent)
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldig cijfer gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$grade = grade_find($conn, $id);
if ($grade === false) {
    redirect('index.php');
} elseif ($grade === null) {
    function_error('Cijfer #' . $id . ' bestaat niet.');
    redirect('index.php');
}

$data = array(
    'student_id' => $grade['student_id'],
    'subject_id' => $grade['subject_id'],
    'description' => $grade['description'],
    'grade' => format_grade($grade['grade']),
    'weight' => (string) $grade['weight'],
    'test_date' => $grade['test_date'],
);

if (is_post()) {
    $data = grade_form_data();

    if (grade_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'grade_update', 'Cijfer #' . $id . ' -> ' . $data['grade']);
        set_flash('success', 'Cijfer is bijgewerkt.');
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

$page_title = 'Cijfer wijzigen';
$form_action = 'grade_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Cijfer #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/grade_form.php'; ?>

<?php require 'includes/footer.php'; ?>
