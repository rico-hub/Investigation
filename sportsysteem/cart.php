<?php
// cart.php - gekozen lessen bekijken en in 1 keer inschrijven
require_once 'includes/init.php';
require_login();

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('lesson_id'));
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Winkelmand is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'checkout') {
        $lesson_ids = cart_get();
        if (registrations_checkout($conn, current_user_id(), $lesson_ids)) {
            log_action($conn, current_user_id(), 'checkout', 'Ingeschreven voor les #' . implode(', #', $lesson_ids));
            cart_clear(); // klaar = winkelmand uit de sessie
            set_flash('success', 'Je bent ingeschreven voor ' . count($lesson_ids) . ' les(sen).');
            redirect('registrations.php');
        }
        redirect('cart.php');
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$lessons = cart_get_lessons($conn);
if ($lessons === false) {
    $lessons = array();
}

$page_title = 'Winkelmand';
require 'includes/header.php';
?>

<h1>Winkelmand</h1>
<p class="count"><strong><?= count($lessons) ?></strong> les(sen) gekozen (max. <?= MAX_CART ?>)</p>

<?php if (count($lessons) === 0): ?>
    <div class="card empty">Je winkelmand is leeg. <a href="index.php">Bekijk het rooster</a></div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Les</th>
                <th>Sport</th>
                <th>Datum</th>
                <th>Tijd</th>
                <th>Plekken</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessons as $lesson): ?>
                <tr>
                    <td class="col-id"><?= e($lesson['id']) ?></td>
                    <td><?= e($lesson['title']) ?></td>
                    <td><?= e($lesson['sport_name']) ?></td>
                    <td><?= e(format_date($lesson['lesson_date'], false)) ?></td>
                    <td><?= e(substr($lesson['start_time'], 0, 5)) ?></td>
                    <td><?= e($lesson['registered']) ?> / <?= e($lesson['max_participants']) ?></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="lesson_id" value="<?= e($lesson['id']) ?>">
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
            <button type="submit" class="btn">Inschrijven</button>
        </form>
        <form method="post" action="cart.php" class="inline">
            <input type="hidden" name="action" value="clear">
            <button type="submit" class="btn btn-red">Winkelmand legen</button>
        </form>
    </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
