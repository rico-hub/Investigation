<?php
// =====================================================================
// bike_trash.php - prullenbak (soft deleted bikes), alleen admin
// =====================================================================
require_once 'includes/init.php';
require_admin();

if (is_post()) {
    if (post_param('action') === 'restore') {
        $id = post_param('bike_id');
        if (!is_valid_id($id)) {
            function_error('Ongeldige fiets.');
        } elseif (bike_restore($conn, (int) $id)) {
            log_action($conn, current_user_id(), 'bike_restore', 'Fiets #' . $id);
            set_flash('success', 'Fiets is teruggezet.');
        }
    }
    redirect('bike_trash.php');
}

$bikes = bikes_get_deleted($conn);
if ($bikes === false) {
    $bikes = array();
}

$page_title = 'Prullenbak';
require 'includes/header.php';
?>

<h1>Prullenbak</h1>
<p class="count"><strong><?= count($bikes) ?></strong> verwijderde fietsen</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>Type</th>
            <th>Verwijderd op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($bikes) === 0): ?>
            <tr><td colspan="5" class="empty">De prullenbak is leeg.</td></tr>
        <?php else: ?>
            <?php foreach ($bikes as $bike): ?>
                <tr>
                    <td class="col-id"><?= e($bike['id']) ?></td>
                    <td><?= e($bike['name']) ?></td>
                    <td><?= e($bike['bike_type_name']) ?></td>
                    <td><?= e(format_date($bike['deleted_at'], true)) ?></td>
                    <td class="actions">
                        <form method="post" action="bike_trash.php" class="inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="bike_id" value="<?= e($bike['id']) ?>">
                            <button type="submit" class="btn btn-small">Terugzetten</button>
                        </form>
                        <a href="bike_delete.php?id=<?= e($bike['id']) ?>&amp;type=hard" class="btn btn-small btn-red">Definitief verwijderen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
