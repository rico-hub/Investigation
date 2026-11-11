<?php
// registrations.php - mijn inschrijvingen + uitschrijven
require_once 'includes/init.php';
require_login();

if (is_post()) {
    if (post_param('action') === 'cancel') {
        $registration_id = post_param('registration_id');
        // false = ook een admin mag hier alleen z'n EIGEN inschrijvingen beheren
        if (registration_cancel($conn, $registration_id, current_user_id(), false)) {
            log_action($conn, current_user_id(), 'registration_cancel', 'Inschrijving #' . $registration_id);
            set_flash('success', 'Je bent uitgeschreven.');
        }
    }
    redirect('registrations.php?' . current_query());
}

$status = get_param('status');

$registrations = registrations_get_by_user($conn, current_user_id(), $status);
if ($registrations === false) {
    $registrations = array();
}

$page_title = 'Mijn inschrijvingen';
require 'includes/header.php';
?>

<h1>Mijn inschrijvingen</h1>

<form method="get" action="registrations.php" class="card filter-bar">
    <select name="status">
        <option value="">-- Alles --</option>
        <option value="komend" <?= selected($status, 'komend') ?>>Komend</option>
        <option value="afgelopen" <?= selected($status, 'afgelopen') ?>>Afgelopen</option>
    </select>
    <button type="submit" class="btn">Filteren</button>
    <a href="registrations.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($registrations) ?></strong> inschrijving(en)</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Les</th>
            <th>Sport</th>
            <th>Trainer</th>
            <th>Datum</th>
            <th>Tijd</th>
            <th>Status les</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($registrations) === 0): ?>
            <tr><td colspan="8" class="empty">Geen inschrijvingen. <a href="index.php">Bekijk het rooster</a></td></tr>
        <?php else: ?>
            <?php foreach ($registrations as $r): ?>
                <tr>
                    <td class="col-id"><?= e($r['id']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><?= e($r['sport_name']) ?></td>
                    <td><?= e($r['trainer']) ?></td>
                    <td><?= e(format_date($r['lesson_date'], false)) ?></td>
                    <td><?= e(substr($r['start_time'], 0, 5)) ?></td>
                    <td><span class="status status-<?= e($r['lesson_status']) ?>"><?= e($r['lesson_status']) ?></span></td>
                    <td>
                        <?php if ($r['lesson_date'] >= date('Y-m-d')): ?>
                            <form method="post" action="registrations.php?<?= e(http_build_query(array('status' => $status))) ?>" class="inline">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="registration_id" value="<?= e($r['id']) ?>">
                                <button type="submit" class="btn btn-small btn-red">Uitschrijven</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
