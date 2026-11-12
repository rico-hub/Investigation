<?php
// =====================================================================
// cart.php - winkelmand bekijken, bike weghalen, legen, afrekenen (bestellen)
// PUZZELSTUK (optioneel): hoort bij functions/cart.php en functions/rentals.php
// =====================================================================
require_once 'includes/init.php';
require_login();

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('bike_id'));
        set_flash('success', 'Fiets uit je winkelmand gehaald.');
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Winkelmand is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'checkout') {
        $bike_ids = cart_get();
        if (rentals_checkout($conn, current_user_id(), $bike_ids, post_param('start_date'), post_param('end_date'))) {
            log_action($conn, current_user_id(), 'checkout', count($bike_ids) . ' fiets(en) gehuurd: #' . implode(', #', $bike_ids));
            cart_clear(); // KLAAR = sessie-data opruimen
            set_flash('success', 'Gelukt! Je hebt ' . count($bike_ids) . ' fiets(en) gehuurd.');
            redirect('rentals.php');
        }
        redirect('cart.php'); // fout staat in de flash
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$bikes = cart_get_bikes($conn);
if ($bikes === false) {
    $bikes = array();
}

$page_title = 'Winkelmand';
require 'includes/header.php';
?>

<h1>Winkelmand</h1>
<p class="count">
    <strong><?= count($bikes) ?></strong> fiets(en) in je winkelmand
    (maximaal <?= MAX_CART ?>)
</p>

<?php if (count($bikes) === 0): ?>
    <div class="card empty">
        Je winkelmand is leeg. <a href="index.php">Bekijk de fietsen</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Naam</th>
                <th>Type</th>
                <th>Prijs</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bikes as $bike): ?>
                <tr>
                    <td class="col-id"><?= e($bike['id']) ?></td>
                    <td><?= e($bike['name']) ?></td>
                    <td><?= e($bike['bike_type_name']) ?></td>
                    <td>&euro; <?= e(number_format((float) $bike['price'], 2, ',', '.')) ?></td>
                    <td><span class="status status-<?= e($bike['status']) ?>"><?= e($bike['status']) ?></span></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="bike_id" value="<?= e($bike['id']) ?>">
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
            <label for="start_date">Van</label>
            <input type="date" id="start_date" name="start_date" required>
            <label for="end_date">Tot</label>
            <input type="date" id="end_date" name="end_date" required>
            <button type="submit" class="btn">Huren</button>
        </form>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-red">Winkelmand legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
