<?php
// grade_delete.php?id=4 - SOFT delete (docent)
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

if (is_post()) {
    if (grade_soft_delete($conn, $id)) {
        log_action($conn, current_user_id(), 'grade_delete', 'Cijfer #' . $id . ' (' . $grade['grade'] . ') van ' . $grade['student_name']);
        set_flash('success', 'Cijfer is verwijderd.');
    }
    redirect('index.php');
}

$page_title = 'Cijfer verwijderen';
require 'includes/header.php';
?>

<div class="card">
    <h1>Weet je het zeker?</h1>
    <p>
        Cijfer <strong><?= e(format_grade($grade['grade'])) ?></strong>
        voor <strong><?= e($grade['subject_name']) ?></strong> (<?= e($grade['description']) ?>)
        van <strong><?= e($grade['student_name']) ?></strong> verwijderen?
    </p>
    <p class="hint">Soft delete: het cijfer telt niet meer mee, maar staat nog in de database (deleted_at).</p>

    <form method="post" action="grade_delete.php?id=<?= e($id) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, verwijderen</button>
    </form>
    <a href="index.php" class="btn btn-grey">Annuleren</a>
</div>

<?php require 'includes/footer.php'; ?>
