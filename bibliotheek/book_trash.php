<?php
// =====================================================================
// book_trash.php - prullenbak (soft deleted boeken), alleen admin
// =====================================================================
require_once 'includes/init.php';
require_admin();

if (is_post()) {
    if (post_param('action') === 'restore') {
        $id = post_param('book_id');
        if (!is_valid_id($id)) {
            function_error('Ongeldig boek.');
        } elseif (book_restore($conn, (int) $id)) {
            log_action($conn, current_user_id(), 'book_restore', 'Boek #' . $id);
            set_flash('success', 'Boek is teruggezet.');
        }
    }
    redirect('book_trash.php');
}

$books = books_get_deleted($conn);
if ($books === false) {
    $books = array();
}

$page_title = 'Prullenbak';
require 'includes/header.php';
?>

<h1>Prullenbak</h1>
<p class="count"><strong><?= count($books) ?></strong> verwijderde boeken</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Titel</th>
            <th>Auteur</th>
            <th>Categorie</th>
            <th>Verwijderd op</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($books) === 0): ?>
            <tr><td colspan="6" class="empty">De prullenbak is leeg.</td></tr>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td class="col-id"><?= e($book['id']) ?></td>
                    <td><?= e($book['title']) ?></td>
                    <td><?= e($book['author']) ?></td>
                    <td><?= e($book['category_name']) ?></td>
                    <td><?= e(format_date($book['deleted_at'], true)) ?></td>
                    <td class="actions">
                        <form method="post" action="book_trash.php" class="inline">
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="book_id" value="<?= e($book['id']) ?>">
                            <button type="submit" class="btn btn-small">Terugzetten</button>
                        </form>
                        <a href="book_delete.php?id=<?= e($book['id']) ?>&amp;type=hard" class="btn btn-small btn-red">Definitief verwijderen</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
