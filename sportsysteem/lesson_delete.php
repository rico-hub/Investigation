<?php
// lesson_delete.php?id=3 - SOFT delete (admin). Alleen als er geen inschrijvingen zijn.
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

if (is_post()) {
    if (lesson_soft_delete($conn, $id)) {
        log_action($conn, current_user_id(), 'lesson_delete', 'Les #' . $id . ': ' . $lesson['title']);
        set_flash('success', 'Les is verwijderd.');
    }
    redirect('index.php');
}

$page_title = 'Les verwijderen';
require 'includes/header.php';
?>

<div class="card">
    <h1>Weet je het zeker?</h1>
    <p>Les <strong><?= e($lesson['title']) ?></strong> op <?= e(format_date($lesson['lesson_date'], false)) ?> verwijderen?</p>
    <?php if ((int) $lesson['registered'] > 0): ?>
        <p class="flash flash-error">Let op: er zijn <?= e($lesson['registered']) ?> inschrijvingen. Verwijderen wordt geweigerd; annuleer de les liever.</p>
    <?php endif; ?>

    <form method="post" action="lesson_delete.php?id=<?= e($id) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, verwijderen</button>
    </form>
    <a href="index.php" class="btn btn-grey">Annuleren</a>
</div>

<?php require 'includes/footer.php'; ?>
