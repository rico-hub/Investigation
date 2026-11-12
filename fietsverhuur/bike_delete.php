<?php
// =====================================================================
// bike_delete.php?id=5             -> soft delete (naar prullenbak)
// bike_delete.php?id=5&type=hard   -> definitief verwijderen
// Eerst een bevestigingspagina (GET), verwijderen gebeurt pas bij POST.
// =====================================================================
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige fiets gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$type = 'soft';
if (get_param('type') === 'hard') {
    $type = 'hard';
}

// Bij hard delete zoeken we ook in de prullenbak
$include_deleted = false;
if ($type === 'hard') {
    $include_deleted = true;
}

$bike = bike_find($conn, $id, $include_deleted);
if ($bike === false) {
    redirect('index.php');
} elseif ($bike === null) {
    function_error('Fiets #' . $id . ' bestaat niet.');
    redirect('index.php');
}

if (is_post()) {
    if ($type === 'hard') {
        if (bike_hard_delete($conn, $id)) {
            log_action($conn, current_user_id(), 'bike_hard_delete', 'Fiets #' . $id . ': ' . $bike['name']);
            set_flash('success', 'Fiets is definitief verwijderd.');
        }
        redirect('bike_trash.php');
    } else {
        if (bike_soft_delete($conn, $id)) {
            log_action($conn, current_user_id(), 'bike_soft_delete', 'Fiets #' . $id . ': ' . $bike['name']);
            set_flash('success', 'Fiets staat in de prullenbak. Je kunt hem daar terugzetten.');
        }
        redirect('index.php');
    }
}

$page_title = 'Fiets verwijderen';
require 'includes/header.php';
?>

<div class="card">
    <h1>Weet je het zeker?</h1>

    <?php if ($type === 'hard'): ?>
        <p>Je gaat <strong><?= e($bike['name']) ?></strong> <strong>DEFINITIEF</strong> verwijderen. Dit kan niet ongedaan worden.</p>
    <?php else: ?>
        <p><strong><?= e($bike['name']) ?></strong> gaat naar de prullenbak (soft delete). Je kunt het later terugzetten.</p>
    <?php endif; ?>

    <form method="post" action="bike_delete.php?id=<?= e($id) ?>&amp;type=<?= e($type) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, verwijderen</button>
    </form>

    <?php if ($type === 'hard'): ?>
        <a href="bike_trash.php" class="btn btn-grey">Annuleren</a>
    <?php else: ?>
        <a href="index.php" class="btn btn-grey">Annuleren</a>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>
