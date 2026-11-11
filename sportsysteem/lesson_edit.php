<?php
// lesson_edit.php?id=3 - UPDATE (admin). Annuleren = status op 'geannuleerd'.
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige les gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$lesson = lesson_find($conn, $id);
if ($lesson === false) {
    redirect('index.php');
} elseif ($lesson === null) {
    function_error('Les #' . $id . ' bestaat niet.');
    redirect('index.php');
}

$data = array(
    'sport_id' => $lesson['sport_id'],
    'title' => $lesson['title'],
    'trainer' => $lesson['trainer'],
    'lesson_date' => $lesson['lesson_date'],
    'start_time' => substr($lesson['start_time'], 0, 5), // 19:00:00 -> 19:00
    'max_participants' => (string) $lesson['max_participants'],
    'status' => $lesson['status'],
);

if (is_post()) {
    $data = lesson_form_data();

    if (lesson_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'lesson_update', 'Les #' . $id . ' (' . $data['status'] . ')');
        set_flash('success', 'Les is bijgewerkt.');
        redirect('index.php');
    }
}

$sports = sports_get_all($conn);
if ($sports === false) {
    $sports = array();
}

$page_title = 'Les wijzigen';
$form_action = 'lesson_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Les #<?= e($id) ?> wijzigen</h1>
<p class="hint">Ingeschreven: <?= e($lesson['registered']) ?>. Les afgelast? Zet de status op "geannuleerd".</p>
<?php require 'includes/lesson_form.php'; ?>

<?php require 'includes/footer.php'; ?>
