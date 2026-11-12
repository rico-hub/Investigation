<?php
// =====================================================================
// rentals.php - verhuringen. Klant ziet alleen zijn eigen, admin ziet alles.
// =====================================================================
require_once 'includes/init.php';
require_login();

// Het id komt ALTIJD uit de sessie, nooit uit de URL of een formulier
$only_user_id = current_user_id();
if (is_admin()) {
    $only_user_id = null; // admin: geen filter op gebruiker
}

$rentals = rentals_get_all($conn, $only_user_id);
if ($rentals === false) {
    $rentals = array();
}

$page_title = 'Verhuringen';
if (is_admin()) {
    $page_title = 'Alle verhuringen';
} else {
    $page_title = 'Mijn verhuringen';
}
require 'includes/header.php';
?>

<h1><?= e($page_title) ?></h1>
<p class="count"><strong><?= count($rentals) ?></strong> verhuring(en)</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Geboekt op</th>
            <th>Klant</th>
            <th>Fiets</th>
            <th>Van</th>
            <th>Tot</th>
            <th>Dagen</th>
            <th>Prijs per dag</th>
            <th>Totaal</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($rentals) === 0): ?>
            <tr><td colspan="9" class="empty">Nog geen verhuringen.</td></tr>
        <?php else: ?>
            <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td class="col-id"><?= e($rental['id']) ?></td>
                    <td><?= e(format_date($rental['created_at'], true)) ?></td>
                    <td><?= e($rental['user_name']) ?></td>
                    <td><?= e($rental['bike_name']) ?> <span class="hint">(<?= e($rental['frame_number']) ?>)</span></td>
                    <td><?= e(format_date($rental['start_date'], false)) ?></td>
                    <td><?= e(format_date($rental['end_date'], false)) ?></td>
                    <td><?= e($rental['days']) ?></td>
                    <td>&euro; <?= e(number_format((float) $rental['price'], 2, ',', '.')) ?></td>
                    <td><strong>&euro; <?= e(number_format((float) $rental['total'], 2, ',', '.')) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
