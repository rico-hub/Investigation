<?php
// =====================================================================
// item_trash.php - prullenbak (soft deleted items), alleen admin
// =====================================================================
require_once 'includes/init.php';
require_admin();

if (is_post()) {
    if (post_param('action') === 'restore') {
        $id = post_param('item_id');
        if (!is_valid_id($id)) {
            function_error('Ongeldig item.');
        } elseif (item_restore($conn, (int) $id)) {
            log_action($conn, current_user_id(), 'item_restore', 'Item #' . $id);
            set_flash('success', 'Item is teruggezet.');
        }
    }
    redirect('item_trash.php');
}

$items = items_get_deleted($conn);
if ($items === false) {
    $items = array();
}

$page_title = 'Prullenbak';
require 'includes/header.php';
?>

<h1>Prullenbak</h1>
<p class="count"><strong><?= count($items) ?></strong> verwijderde items</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>Verwijderd op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($items) === 0): ?>
            <tr><td colspan="4" class="empty">De prullenbak is leeg.</td></tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="col-id"><?= e($item['id']) ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e(format_date($item['deleted_at'], true)) ?></td>
                    <td class="actions">
                        <form method="post" action="item_trash.php" class="inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                            <button type="submit" class="btn btn-small">Terugzetten</button>
                        </form>
                        <a href="item_delete.php?id=<?= e($item['id']) ?>&amp;type=hard" class="btn btn-small btn-red">Definitief verwijderen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
