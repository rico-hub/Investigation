<?php
// =====================================================================
// cart.php - winkelmand bekijken, animal weghalen, legen, afrekenen (bestellen)
// PUZZELSTUK (optioneel): hoort bij functions/cart.php en functions/appointments.php
// =====================================================================
require_once 'includes/init.php';
require_login();

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('animal_id'));
        set_flash('success', 'Dier van je bezoeklijst gehaald.');
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Bezoeklijst is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'checkout') {
        $animal_ids = cart_get();
        if (appointments_checkout($conn, current_user_id(), $animal_ids, post_param('booking_date'))) {
            log_action($conn, current_user_id(), 'checkout', count($animal_ids) . ' dier(en), afspraak voor: #' . implode(', #', $animal_ids));
            cart_clear(); // KLAAR = sessie-data opruimen
            set_flash('success', 'Gelukt! Je afspraak voor ' . count($animal_ids) . ' dier(en) is gemaakt.');
            redirect('appointments.php');
        }
        redirect('cart.php'); // fout staat in de flash
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$animals = cart_get_animals($conn);
if ($animals === false) {
    $animals = array();
}

$page_title = 'Bezoeklijst';
require 'includes/header.php';
?>

<h1>Bezoeklijst</h1>
<p class="count">
    <strong><?= count($animals) ?></strong> dier(en) op je bezoeklijst
    (maximaal <?= MAX_CART ?>)
</p>

<?php if (count($animals) === 0): ?>
    <div class="card empty">
        Je bezoeklijst is leeg. <a href="index.php">Bekijk de dieren</a>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Naam</th>
                <th>Diersoort</th>
                <th>Leeftijd</th>
                <th>Status</th>
                <th>Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($animals as $animal): ?>
                <tr>
                    <td class="col-id"><?= e($animal['id']) ?></td>
                    <td><?= e($animal['name']) ?></td>
                    <td><?= e($animal['animal_type_name']) ?></td>
                    <td><?= e($animal['age']) ?> jaar</td>
                    <td><span class="status status-<?= e($animal['status']) ?>"><?= e($animal['status']) ?></span></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="animal_id" value="<?= e($animal['id']) ?>">
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
            <label for="booking_date">Datum bezoek</label>
            <input type="date" id="booking_date" name="booking_date" required>
            <button type="submit" class="btn">Afspraak maken</button>
        </form>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-red">Bezoeklijst legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
