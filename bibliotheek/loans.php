<?php
// =====================================================================
// loans.php - user: eigen leningen / admin: alle leningen (+ user filter)
// =====================================================================
require_once 'includes/init.php';
require_login();

// POST: boek terugbrengen
if (is_post()) {
    if (post_param('action') === 'return') {
        $loan_id = post_param('loan_id');
        if (loan_return($conn, $loan_id, current_user_id(), is_admin())) {
            log_action($conn, current_user_id(), 'loan_return', 'Lening #' . $loan_id . ' teruggebracht');
            set_flash('success', 'Boek is teruggebracht.');
        }
    }
    redirect('loans.php?' . current_query());
}

$filters = loans_read_filters();

// Normale user: alleen eigen leningen. Admin: null = alles.
$only_user_id = current_user_id();
if (is_admin()) {
    $only_user_id = null;
}

$loans = loans_get_all($conn, $filters, $only_user_id);
if ($loans === false) {
    $loans = array();
}

$users = array();
if (is_admin()) {
    $users = users_get_all($conn);
    if ($users === false) {
        $users = array();
    }
}

$current_query = http_build_query($filters);

$page_title = 'Leningen';
require 'includes/header.php';
?>

<?php if (is_admin()): ?>
    <h1>Alle leningen</h1>
<?php else: ?>
    <h1>Mijn leningen</h1>
<?php endif; ?>

<form method="get" action="loans.php" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op titel..." value="<?= e($filters['search']) ?>">

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <option value="actief" <?= selected($filters['status'], 'actief') ?>>Actief</option>
        <option value="te_laat" <?= selected($filters['status'], 'te_laat') ?>>Te laat</option>
        <option value="teruggebracht" <?= selected($filters['status'], 'teruggebracht') ?>>Teruggebracht</option>
    </select>

    <?php if (is_admin()): ?>
        <select name="user_id">
            <option value="">-- Alle gebruikers --</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= e($user['id']) ?>" <?= selected($filters['user_id'], $user['id']) ?>><?= e($user['name']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="loans.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($loans) ?></strong> lening(en) gevonden</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Boek', 'title', $filters) ?></th>
            <?php if (is_admin()): ?>
                <th><?= sort_link('Gebruiker', 'user', $filters) ?></th>
            <?php endif; ?>
            <th><?= sort_link('Geleend op', 'loan_date', $filters) ?></th>
            <th><?= sort_link('Inleveren voor', 'due_date', $filters) ?></th>
            <th>Teruggebracht</th>
            <th>Status</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($loans) === 0): ?>
            <tr><td colspan="8" class="empty">Geen leningen gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($loans as $loan): ?>
                <?php $status = loan_status($loan); ?>
                <tr>
                    <td class="col-id"><?= e($loan['id']) ?></td>
                    <td><?= e($loan['title']) ?></td>
                    <?php if (is_admin()): ?>
                        <td><?= e($loan['user_name']) ?></td>
                    <?php endif; ?>
                    <td><?= e(format_date($loan['loan_date'], false)) ?></td>
                    <td><?= e(format_date($loan['due_date'], false)) ?></td>
                    <td><?= e(format_date($loan['returned_at'], true)) ?></td>
                    <td><span class="status status-<?= e($status) ?>"><?= e(str_replace('_', ' ', $status)) ?></span></td>
                    <td>
                        <?php if ($status !== 'teruggebracht'): ?>
                            <form method="post" action="loans.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="loan_id" value="<?= e($loan['id']) ?>">
                                <button type="submit" class="btn btn-small">Terugbrengen</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
