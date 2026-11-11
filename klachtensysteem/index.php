<?php
// =====================================================================
// index.php - klachtenlijst
//   user  = alleen eigen klachten
//   admin = alle klachten + "op werklijst zetten"
// =====================================================================
require_once 'includes/init.php';
require_login();

// POST: klacht op de werklijst (admin)
if (is_post()) {
    require_admin();
    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('complaint_id'))) {
            set_flash('success', 'Klacht staat op je werklijst.');
        }
    }
    redirect('index.php?' . current_query());
}

$filters = complaints_read_filters();

$only_user_id = current_user_id();
if (is_admin()) {
    $only_user_id = null;
}

$complaints = complaints_get_all($conn, $filters, $only_user_id);
if ($complaints === false) {
    $complaints = array();
}

$counts = complaints_count_by_status($conn, $only_user_id);

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

$current_query = http_build_query($filters);

$page_title = 'Klachten';
require 'includes/header.php';
?>

<?php if (is_admin()): ?>
    <h1>Alle klachten</h1>
<?php else: ?>
    <h1>Mijn klachten</h1>
    <p><a href="complaint_create.php" class="btn">+ Nieuwe klacht indienen</a></p>
<?php endif; ?>

<!-- StatusFilter als knoppen, met TotalCount per status -->
<div class="button-row tabs">
    <?php if ($filters['status'] === ''): ?>
        <a href="<?= e(status_tab_link($filters, '')) ?>" class="btn btn-small">Alle (<?= $counts['totaal'] ?>)</a>
    <?php else: ?>
        <a href="<?= e(status_tab_link($filters, '')) ?>" class="btn btn-small btn-grey">Alle (<?= $counts['totaal'] ?>)</a>
    <?php endif; ?>

    <?php foreach (complaint_statuses() as $status): ?>
        <?php if ($filters['status'] === $status): ?>
            <a href="<?= e(status_tab_link($filters, $status)) ?>" class="btn btn-small"><?= e(str_replace('_', ' ', $status)) ?> (<?= $counts[$status] ?>)</a>
        <?php else: ?>
            <a href="<?= e(status_tab_link($filters, $status)) ?>" class="btn btn-small btn-grey"><?= e(str_replace('_', ' ', $status)) ?> (<?= $counts[$status] ?>)</a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek in titel of beschrijving..." value="<?= e($filters['search']) ?>">

    <select name="priority">
        <option value="">-- Alle prioriteiten --</option>
        <?php foreach (complaint_priorities() as $priority): ?>
            <option value="<?= e($priority) ?>" <?= selected($filters['priority'], $priority) ?>><?= e(ucfirst($priority)) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="category_id">
        <option value="">-- Alle categorieën --</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['id']) ?>" <?= selected($filters['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="index.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($complaints) ?></strong> van de <?= $counts['totaal'] ?> klachten gevonden</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Titel', 'title', $filters) ?></th>
            <th><?= sort_link('Categorie', 'category', $filters) ?></th>
            <th><?= sort_link('Ingediend door', 'user', $filters) ?></th>
            <th><?= sort_link('Prioriteit', 'priority', $filters) ?></th>
            <th><?= sort_link('Status', 'status', $filters) ?></th>
            <th><?= sort_link('Datum', 'created', $filters) ?></th>
            <th>Acties</th>
        </tr>
        <tr class="column-filters">
            <th><input type="text" name="f_id" form="filters" value="<?= e($filters['f_id']) ?>" placeholder="#"></th>
            <th><input type="text" name="f_title" form="filters" value="<?= e($filters['f_title']) ?>" placeholder="Filter titel"></th>
            <th></th>
            <th><input type="text" name="f_user" form="filters" value="<?= e($filters['f_user']) ?>" placeholder="Filter naam"></th>
            <th></th>
            <th></th>
            <th></th>
            <th><button type="submit" form="filters" class="btn btn-small">Zoek</button></th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($complaints) === 0): ?>
            <tr><td colspan="8" class="empty">Geen klachten gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($complaints as $complaint): ?>
                <tr>
                    <td class="col-id"><?= e($complaint['id']) ?></td>
                    <td><a href="complaint_view.php?id=<?= e($complaint['id']) ?>"><?= e($complaint['title']) ?></a></td>
                    <td><?= e($complaint['category_name']) ?></td>
                    <td><?= e($complaint['user_name']) ?></td>
                    <td><?= e($complaint['priority']) ?></td>
                    <td><span class="status status-<?= e($complaint['status']) ?>"><?= e(str_replace('_', ' ', $complaint['status'])) ?></span></td>
                    <td><?= e(format_date($complaint['created_at'], false)) ?></td>
                    <td class="actions">
                        <a href="complaint_view.php?id=<?= e($complaint['id']) ?>" class="btn btn-small btn-grey">Bekijken</a>
                        <?php if (is_admin()): ?>
                            <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="complaint_id" value="<?= e($complaint['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Werklijst</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
