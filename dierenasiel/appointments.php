<?php
// =====================================================================
// appointments.php - bestellingen. User ziet alleen zijn eigen, admin ziet alles.
// PUZZELSTUK (optioneel): voorbeeld van een lijst met 2 JOINs (2 foreign keys).
// =====================================================================
require_once 'includes/init.php';
require_login();

// Het id komt ALTIJD uit de sessie, nooit uit de URL of een formulier
$only_user_id = current_user_id();
if (is_admin()) {
    $only_user_id = null; // admin: geen filter op gebruiker
}

$appointments = appointments_get_all($conn, $only_user_id);
if ($appointments === false) {
    $appointments = array();
}

$page_title = 'Afspraken';
require 'includes/header.php';
?>

<h1>Afspraken</h1>
<p class="count"><strong><?= count($appointments) ?></strong> afspraak/afspraken</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Gemaakt op</th>
            <th>Gebruiker</th>
            <th>Dier</th>
            <th>Leeftijd</th>
            <th>Datum afspraak</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($appointments) === 0): ?>
            <tr><td colspan="6" class="empty">Nog geen afspraken.</td></tr>
        <?php else: ?>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td class="col-id"><?= e($appointment['id']) ?></td>
                    <td><?= e(format_date($appointment['created_at'], true)) ?></td>
                    <td><?= e($appointment['user_name']) ?></td>
                    <td><?= e($appointment['animal_name']) ?></td>
                    <td><?= e($appointment['age']) ?> jaar</td>
                    <td><?= e(format_date($appointment['booking_date'], false)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
