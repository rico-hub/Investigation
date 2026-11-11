<?php
// =====================================================================
// cart.php - WERKLIJST (admin): opgepakte klachten in 1 keer bijwerken
// =====================================================================
require_once 'includes/init.php';
require_admin();

$new_status = 'in_behandeling'; // standaard keuze in de dropdown

if (is_post()) {
    $action = post_param('action');

    if ($action === 'remove') {
        cart_remove(post_param('complaint_id'));
        redirect('cart.php');
    } elseif ($action === 'clear') {
        cart_clear();
        set_flash('success', 'Werklijst is leeg gemaakt.');
        redirect('cart.php');
    } elseif ($action === 'apply') {
        $new_status = post_param('status');
        $assign_to_me = false;
        if (post_param('assign_to_me') === '1') {
            $assign_to_me = true;
        }

        $ids = cart_get();
        if (complaints_bulk_update($conn, $ids, $new_status, $assign_to_me, current_user_id())) {
            log_action($conn, current_user_id(), 'complaint_bulk', count($ids) . ' klachten -> ' . $new_status . ': #' . implode(', #', $ids));
            cart_clear(); // klaar = werklijst uit de sessie
            set_flash('success', count($ids) . ' klacht(en) bijgewerkt naar "' . $new_status . '".');
            redirect('index.php');
        }
        // fout: pagina opnieuw tonen, gekozen status blijft staan
    } else {
        function_error('Onbekende actie.');
        redirect('cart.php');
    }
}

$items = cart_get_items($conn);
if ($items === false) {
    $items = array();
}

$page_title = 'Werklijst';
require 'includes/header.php';
?>

<h1>Werklijst</h1>
<p class="count"><strong><?= count($items) ?></strong> klacht(en) op je werklijst</p>

<?php if (count($items) === 0): ?>
    <div class="card empty">Je werklijst is leeg. Klik bij een klacht op "+ Werklijst".</div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th>Titel</th>
                <th>Ingediend door</th>
                <th>Prioriteit</th>
                <th>Status nu</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td class="col-id"><?= e($item['id']) ?></td>
                    <td><?= e($item['title']) ?></td>
                    <td><?= e($item['user_name']) ?></td>
                    <td><?= e($item['priority']) ?></td>
                    <td><span class="status status-<?= e($item['status']) ?>"><?= e(str_replace('_', ' ', $item['status'])) ?></span></td>
                    <td>
                        <form method="post" action="cart.php" class="inline">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="complaint_id" value="<?= e($item['id']) ?>">
                            <button type="submit" class="btn btn-small btn-grey">Weghalen</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form method="post" action="cart.php" class="card filter-bar">
        <input type="hidden" name="action" value="apply">
        <label for="status"><strong>Nieuwe status voor alles:</strong></label>
        <select id="status" name="status">
            <?php foreach (complaint_statuses() as $status): ?>
                <option value="<?= e($status) ?>" <?= selected($new_status, $status) ?>><?= e(str_replace('_', ' ', $status)) ?></option>
            <?php endforeach; ?>
        </select>
        <label><input type="checkbox" name="assign_to_me" value="1" checked> Aan mij toewijzen</label>
        <button type="submit" class="btn">Toepassen</button>
    </form>

    <form method="post" action="cart.php" class="inline">
        <input type="hidden" name="action" value="clear">
        <button type="submit" class="btn btn-red">Werklijst legen</button>
    </form>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
