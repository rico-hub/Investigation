<?php
// =====================================================================
// animal_trash.php - prullenbak (soft deleted animals), alleen admin
// =====================================================================
require_once 'includes/init.php';
require_admin();

if (is_post()) {
    if (post_param('action') === 'restore') {
        $id = post_param('animal_id');
        if (!is_valid_id($id)) {
            function_error('Ongeldig dier.');
        } elseif (animal_restore($conn, (int) $id)) {
            log_action($conn, current_user_id(), 'animal_restore', 'Dier #' . $id);
            set_flash('success', 'Dier is teruggezet.');
        }
    }
    redirect('animal_trash.php');
}

$animals = animals_get_deleted($conn);
if ($animals === false) {
    $animals = array();
}

$page_title = 'Prullenbak';
require 'includes/header.php';
?>

<h1>Prullenbak</h1>
<p class="count"><strong><?= count($animals) ?></strong> verwijderde dieren</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>Diersoort</th>
            <th>Verwijderd op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($animals) === 0): ?>
            <tr><td colspan="5" class="empty">De prullenbak is leeg.</td></tr>
        <?php else: ?>
            <?php foreach ($animals as $animal): ?>
                <tr>
                    <td class="col-id"><?= e($animal['id']) ?></td>
                    <td><?= e($animal['name']) ?></td>
                    <td><?= e($animal['animal_type_name']) ?></td>
                    <td><?= e(format_date($animal['deleted_at'], true)) ?></td>
                    <td class="actions">
                        <form method="post" action="animal_trash.php" class="inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="animal_id" value="<?= e($animal['id']) ?>">
                            <button type="submit" class="btn btn-small">Terugzetten</button>
                        </form>
                        <a href="animal_delete.php?id=<?= e($animal['id']) ?>&amp;type=hard" class="btn btn-small btn-red">Definitief verwijderen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
