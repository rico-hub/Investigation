<?php
// =====================================================================
// logs.php - logboek: wie deed wat en wanneer (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

$filters = array(
    'action' => get_param('action'),
    'search' => get_param('search'),
);

$logs = logs_get_all($conn, $filters);
if ($logs === false) {
    $logs = array();
}

$actions = logs_get_actions($conn);
if ($actions === false) {
    $actions = array();
}

$page_title = 'Logboek';
require 'includes/header.php';
?>

<h1>Logboek</h1>

<form method="get" action="logs.php" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek in details of naam..." value="<?= e($filters['search']) ?>">
    <select name="action">
        <option value="">-- Alle acties --</option>
        <?php foreach ($actions as $row): ?>
            <option value="<?= e($row['action']) ?>" <?= selected($filters['action'], $row['action']) ?>><?= e($row['action']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filteren</button>
    <a href="logs.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($logs) ?></strong> regels (max. 200)</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Tijd</th>
            <th>Gebruiker</th>
            <th>Actie</th>
            <th>Details</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($logs) === 0): ?>
            <tr><td colspan="6" class="empty">Geen regels gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="col-id"><?= e($log['id']) ?></td>
                    <td><?= e(format_date($log['created_at'], true)) ?></td>
                    <td>
                        <?php if ($log['user_name'] === null): ?>
                            <em>onbekend</em>
                        <?php else: ?>
                            <?= e($log['user_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['details']) ?></td>
                    <td><?= e($log['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
