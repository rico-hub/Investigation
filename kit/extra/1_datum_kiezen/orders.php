<?php
// =====================================================================
// orders.php - bestellingen. User ziet alleen zijn eigen, admin ziet alles.
// PUZZELSTUK (optioneel): voorbeeld van een lijst met 2 JOINs (2 foreign keys).
// =====================================================================
require_once 'includes/init.php';
require_login();

// Het id komt ALTIJD uit de sessie, nooit uit de URL of een formulier
$only_user_id = current_user_id();
if (is_admin()) {
    $only_user_id = null; // admin: geen filter op gebruiker
}

$orders = orders_get_all($conn, $only_user_id);
if ($orders === false) {
    $orders = array();
}

$page_title = 'Bestellingen';
require 'includes/header.php';
?>

<h1>Bestellingen</h1>
<p class="count"><strong><?= count($orders) ?></strong> bestelling(en)</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Datum</th>
            <th>Gebruiker</th>
            <th>Item</th>
            <th>Prijs</th>
            <th>Gekozen datum</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($orders) === 0): ?>
            <tr><td colspan="6" class="empty">Nog geen bestellingen.</td></tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td class="col-id"><?= e($order['id']) ?></td>
                    <td><?= e(format_date($order['created_at'], true)) ?></td>
                    <td><?= e($order['user_name']) ?></td>
                    <td><?= e($order['item_name']) ?></td>
                    <td>&euro; <?= e(number_format((float) $order['price'], 2, ',', '.')) ?></td>
                    <td><?= e(format_date($order['booking_date'], false)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
