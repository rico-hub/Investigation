<?php
// =====================================================================
// complaint_trash.php - prullenbak (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

if (is_post()) {
    if (post_param('action') === 'restore') {
        $id = post_param('complaint_id');
        if (!is_valid_id($id)) {
            function_error('Ongeldige klacht.');
        } elseif (complaint_restore($conn, (int) $id)) {
            log_action($conn, current_user_id(), 'complaint_restore', 'Klacht #' . $id);
            set_flash('success', 'Klacht is teruggezet.');
        }
    }
    redirect('complaint_trash.php');
}

$complaints = complaints_get_deleted($conn);
if ($complaints === false) {
    $complaints = array();
}

$page_title = 'Prullenbak';
require 'includes/header.php';
?>

<h1>Prullenbak</h1>
<p class="count"><strong><?= count($complaints) ?></strong> verwijderde klachten</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Titel</th>
            <th>Ingediend door</th>
            <th>Status</th>
            <th>Verwijderd op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($complaints) === 0): ?>
            <tr><td colspan="6" class="empty">De prullenbak is leeg.</td></tr>
        <?php else: ?>
            <?php foreach ($complaints as $complaint): ?>
                <tr>
                    <td class="col-id"><?= e($complaint['id']) ?></td>
                    <td><?= e($complaint['title']) ?></td>
                    <td><?= e($complaint['user_name']) ?></td>
                    <td><?= e($complaint['status']) ?></td>
                    <td><?= e(format_date($complaint['deleted_at'], true)) ?></td>
                    <td class="actions">
                        <form method="post" action="complaint_trash.php" class="inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="complaint_id" value="<?= e($complaint['id']) ?>">
                            <button type="submit" class="btn btn-small">Terugzetten</button>
                        </form>
                        <a href="complaint_delete.php?id=<?= e($complaint['id']) ?>&amp;type=hard" class="btn btn-small btn-red">Definitief verwijderen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
