<?php
// =====================================================================
// cart.php - winkelmand bekijken, item weghalen, legen, afrekenen (lenen)
// =====================================================================
require_once 'includes/init.php';
require_login();

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('book_id'));
        set_flash('success', 'Boek uit je winkelmand gehaald.');
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Winkelmand is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'checkout') {
        $book_ids = cart_get();
        if (loans_checkout($conn, current_user_id(), $book_ids)) {
            log_action($conn, current_user_id(), 'checkout', count($book_ids) . ' boek(en) geleend: #' . implode(', #', $book_ids));
            cart_clear(); // KLAAR = sessie-data opruimen
            set_flash('success', 'Gelukt! Je hebt ' . count($book_ids) . ' boek(en) geleend. Breng ze binnen 21 dagen terug.');
            redirect('loans.php');
        }
        redirect('cart.php'); // fout staat in de flash
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$books = cart_get_books($conn);
if ($books === false) {
    $books = array();
}

$page_title = 'Winkelmand';
require 'includes/header.php';
?>

<h1>Winkelmand</h1>
<p class="count">
    <strong><?= count($books) ?></strong> boek(en) in je winkelmand
    (maximaal <?= MAX_LOANS ?> tegelijk lenen)
</p>

<?php if (count($books) === 0): ?>
    <div class="card empty">
        Je winkelmand is leeg. <a href="index.php">Bekijk de boeken</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Titel</th>
                <th>Auteur</th>
                <th>Categorie</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td class="col-id"><?= e($book['id']) ?></td>
                    <td><?= e($book['title']) ?></td>
                    <td><?= e($book['author']) ?></td>
                    <td><?= e($book['category_name']) ?></td>
                    <td><span class="status status-<?= e($book['status']) ?>"><?= e($book['status']) ?></span></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="book_id" value="<?= e($book['id']) ?>">
                            <button type="submit" class="btn btn-small btn-grey">Weghalen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="button-row">
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="btn">Boeken lenen</button>
        </form>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-red">Winkelmand legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
