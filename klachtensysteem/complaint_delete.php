<?php
// =====================================================================
// complaint_delete.php?id=3            -> soft delete (user: eigen + open, admin: alles)
// complaint_delete.php?id=3&type=hard  -> hard delete (alleen admin, vanuit prullenbak)
// =====================================================================
require_once 'includes/init.php';
require_login();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige klacht gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$type = 'soft';
if (get_param('type') === 'hard') {
    require_admin(); // hard delete = alleen admin
    $type = 'hard';
}

$include_deleted = false;
if ($type === 'hard') {
    $include_deleted = true;
}

$complaint = complaint_find($conn, $id, $include_deleted);
if ($complaint === false) {
    redirect('index.php');
} elseif ($complaint === null) {
    function_error('Klacht #' . $id . ' bestaat niet.');
    redirect('index.php');
}

if (is_post()) {
    if ($type === 'hard') {
        if (complaint_hard_delete($conn, $id)) {
            log_action($conn, current_user_id(), 'complaint_hard_delete', 'Klacht #' . $id . ': ' . $complaint['title']);
            set_flash('success', 'Klacht en alle reacties zijn definitief verwijderd.');
        }
        redirect('complaint_trash.php');
    } else {
        if (complaint_soft_delete($conn, $id, current_user_id(), is_admin())) {
            log_action($conn, current_user_id(), 'complaint_soft_delete', 'Klacht #' . $id . ': ' . $complaint['title']);
            set_flash('success', 'Klacht is verwijderd.');
            cart_remove($id); // ook van de werklijst af
            redirect('index.php');
        }
        redirect('complaint_view.php?id=' . $id);
    }
}

$page_title = 'Klacht verwijderen';
require 'includes/header.php';
?>

<div class="card">
    <h1>Weet je het zeker?</h1>
    <?php if ($type === 'hard'): ?>
        <p>Klacht <strong><?= e($complaint['title']) ?></strong> wordt <strong>DEFINITIEF</strong> verwijderd,
           inclusief alle reacties (ON DELETE CASCADE).</p>
    <?php else: ?>
        <p>Klacht <strong><?= e($complaint['title']) ?></strong> wordt verwijderd.</p>
    <?php endif; ?>

    <form method="post" action="complaint_delete.php?id=<?= e($id) ?>&amp;type=<?= e($type) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, verwijderen</button>
    </form>
    <a href="index.php" class="btn btn-grey">Annuleren</a>
</div>

<?php require 'includes/footer.php'; ?>
