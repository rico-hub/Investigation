<?php
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige beurt gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$beurt = beurt_find($conn, $id);
if ($beurt === false) {
    redirect('index.php');
} elseif ($beurt === null) {
    function_error('Beurt #' . $id . ' bestaat niet.');
    redirect('index.php');
}

if (is_post()) {
    if (beurt_delete($conn, $beurt)) {
        log_action($conn, current_user_id(), 'beurt_delete', 'Beurt #' . $id . ': ' . $beurt['type_onderhoud'] . ' op ' . $beurt['datum']);
        set_flash('success', 'Beurt is verwijderd. De plekken in de werkplaats zijn weer vrij.');
    }
    redirect('index.php');
}

$page_title = 'Beurt verwijderen';
require 'includes/header.php';
?>

<div class="card">
    <h1>Weet je het zeker?</h1>
    <p>Je gaat de beurt <strong><?= e($beurt['type_onderhoud']) ?></strong> op
        <strong><?= e(format_date($beurt['datum'], false)) ?> om <?= e(substr($beurt['tijd'], 0, 5)) ?></strong> verwijderen.</p>

    <form method="post" action="beurt_delete.php?id=<?= e($id) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, verwijderen</button>
    </form>
    <a href="index.php" class="btn btn-grey">Annuleren</a>
</div>

<?php require 'includes/footer.php'; ?>
