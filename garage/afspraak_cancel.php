<?php
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige afspraak gekozen.');
    redirect('afspraken.php');
}
$id = (int) $id;

$afspraak = afspraak_find($conn, $id);
if ($afspraak === false) {
    redirect('afspraken.php');
} elseif ($afspraak === null) {
    function_error('Afspraak #' . $id . ' bestaat niet.');
    redirect('afspraken.php');
}

if (is_post()) {
    if (afspraak_cancel($conn, $id)) {
        log_action($conn, current_user_id(), 'afspraak_cancel', 'Afspraak #' . $id . ': ' . $afspraak['kenteken'] . ', ' . $afspraak['type_onderhoud'] . ' op ' . $afspraak['datum']);
        set_flash('success', 'Afspraak voor ' . $afspraak['kenteken'] . ' is geannuleerd. De plek is weer vrij.');
    }
    redirect('afspraken.php');
}

$page_title = 'Afspraak annuleren';
require 'includes/header.php';
?>

<div class="card">
    <h1>Afspraak annuleren?</h1>
    <p><strong><?= e($afspraak['klant_naam']) ?></strong> met kenteken <strong><?= e($afspraak['kenteken']) ?></strong><br>
        <?= e($afspraak['type_onderhoud']) ?> op <?= e(format_date($afspraak['datum'], false)) ?> om <?= e(substr($afspraak['tijd'], 0, 5)) ?></p>
    <p>De afspraak wordt verwijderd en de plek komt weer vrij.</p>

    <form method="post" action="afspraak_cancel.php?id=<?= e($id) ?>" class="inline">
        <button type="submit" class="btn btn-red">Ja, annuleren</button>
    </form>
    <a href="afspraken.php" class="btn btn-grey">Terug</a>
</div>

<?php require 'includes/footer.php'; ?>
