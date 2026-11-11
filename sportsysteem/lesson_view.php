<?php
// lesson_view.php?id=3 - deelnemers van een les (admin), iemand uitschrijven
require_once 'includes/init.php';
require_admin();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige les gekozen.');
    redirect('index.php');
}
$id = (int) $id;

if (is_post()) {
    if (post_param('action') === 'cancel') {
        $registration_id = post_param('registration_id');
        if (registration_cancel($conn, $registration_id, current_user_id(), true)) {
            log_action($conn, current_user_id(), 'registration_cancel', 'Inschrijving #' . $registration_id . ' (les #' . $id . ') door admin');
            set_flash('success', 'Deelnemer is uitgeschreven.');
        }
    }
    redirect('lesson_view.php?id=' . $id);
}

$lesson = lesson_find($conn, $id);
if ($lesson === false) {
    redirect('index.php');
} elseif ($lesson === null) {
    function_error('Les #' . $id . ' bestaat niet.');
    redirect('index.php');
}

$participants = registrations_get_by_lesson($conn, $id);
if ($participants === false) {
    $participants = array();
}

$page_title = 'Deelnemers';
require 'includes/header.php';
?>

<div class="card">
    <h1><?= e($lesson['title']) ?></h1>
    <p>
        <?= e($lesson['sport_name']) ?> &middot; trainer <?= e($lesson['trainer']) ?> &middot;
        <?= e(format_date($lesson['lesson_date'], false)) ?> om <?= e(substr($lesson['start_time'], 0, 5)) ?>
    </p>
    <p class="count"><strong><?= count($participants) ?></strong> van de <?= e($lesson['max_participants']) ?> plekken bezet</p>
</div>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>E-mail</th>
            <th>Ingeschreven op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($participants) === 0): ?>
            <tr><td colspan="5" class="empty">Nog niemand ingeschreven.</td></tr>
        <?php else: ?>
            <?php foreach ($participants as $p): ?>
                <tr>
                    <td class="col-id"><?= e($p['id']) ?></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['email']) ?></td>
                    <td><?= e(format_date($p['created_at'], true)) ?></td>
                    <td>
                        <form method="post" action="lesson_view.php?id=<?= e($id) ?>" class="inline">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="registration_id" value="<?= e($p['id']) ?>">
                            <button type="submit" class="btn btn-small btn-red">Uitschrijven</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<a href="index.php" class="btn btn-grey">Terug</a>

<?php require 'includes/footer.php'; ?>
