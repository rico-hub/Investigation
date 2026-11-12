<?php
// =====================================================================
// cart.php - winkelmand bekijken, item weghalen, legen, afrekenen (bestellen)
// PUZZELSTUK (optioneel): hoort bij functions/cart.php en functions/orders.php
// =====================================================================
require_once 'includes/init.php';
require_login();

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('item_id'));
        set_flash('success', 'Item uit je winkelmand gehaald.');
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Winkelmand is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'checkout') {
        $item_ids = cart_get();
        if (orders_checkout($conn, current_user_id(), $item_ids)) {
            log_action($conn, current_user_id(), 'checkout', count($item_ids) . ' item(s) besteld: #' . implode(', #', $item_ids));
            cart_clear(); // KLAAR = sessie-data opruimen
            set_flash('success', 'Gelukt! Je hebt ' . count($item_ids) . ' item(s) besteld.');
            redirect('orders.php');
        }
        redirect('cart.php'); // fout staat in de flash
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$items = cart_get_items($conn);
if ($items === false) {
    $items = array();
}

$page_title = 'Winkelmand';
require 'includes/header.php';
?>

<h1>Winkelmand</h1>
<p class="count">
    <strong><?= count($items) ?></strong> item(s) in je winkelmand
    (maximaal <?= MAX_CART ?>)
</p>

<?php if (count($items) === 0): ?>
    <div class="card empty">
        Je winkelmand is leeg. <a href="index.php">Bekijk de items</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Naam</th>
                <th>Categorie</th>
                <th>Prijs</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="col-id"><?= e($item['id']) ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category_name']) ?></td>
                    <td>&euro; <?= e(number_format((float) $item['price'], 2, ',', '.')) ?></td>
                    <td><span class="status status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
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
            <button type="submit" class="btn">Bestellen</button>
        </form>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-red">Winkelmand legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
