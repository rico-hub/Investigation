<?php
// =====================================================================
// index.php - lessenrooster met filters, zoeken, sorteren, winkelmand
// =====================================================================
require_once 'includes/init.php';

if (is_post()) {
    require_login();
    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('lesson_id'))) {
            set_flash('success', 'Les toegevoegd aan je winkelmand.');
        }
    }
    redirect('index.php?' . current_query());
}

$filters = lessons_read_filters();

$lessons = lessons_get_all($conn, $filters);
if ($lessons === false) {
    $lessons = array();
}

$total = lessons_count_all($conn);

$sports = sports_get_all($conn);
if ($sports === false) {
    $sports = array();
}

// Waar is de ingelogde gebruiker al voor ingeschreven? (om de juiste knop te tonen)
$my_lesson_ids = array();
if (is_logged_in()) {
    $my_lesson_ids = registrations_get_lesson_ids($conn, current_user_id());
}
$cart = cart_get();

$current_query = http_build_query($filters);

$page_title = 'Lessen';
require 'includes/header.php';
?>

<h1>Lessenrooster</h1>

<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op les, trainer of sport..." value="<?= e($filters['search']) ?>">

    <select name="sport_id">
        <option value="">-- Alle sporten --</option>
        <?php foreach ($sports as $sport): ?>
            <option value="<?= e($sport['id']) ?>" <?= selected($filters['sport_id'], $sport['id']) ?>><?= e($sport['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <?php foreach (lesson_filter_statuses() as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= selected($filters['status'], $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="index.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($lessons) ?></strong> van de <?= $total ?> lessen gevonden</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Les', 'title', $filters) ?></th>
            <th><?= sort_link('Sport', 'sport', $filters) ?></th>
            <th><?= sort_link('Trainer', 'trainer', $filters) ?></th>
            <th><?= sort_link('Datum', 'date', $filters) ?></th>
            <th>Tijd</th>
            <th><?= sort_link('Plekken', 'registered', $filters) ?></th>
            <th>Status</th>
            <th>Acties</th>
        </tr>
        <tr class="column-filters">
            <th><input type="text" name="f_id" form="filters" value="<?= e($filters['f_id']) ?>" placeholder="#"></th>
            <th><input type="text" name="f_title" form="filters" value="<?= e($filters['f_title']) ?>" placeholder="Filter les"></th>
            <th></th>
            <th><input type="text" name="f_trainer" form="filters" value="<?= e($filters['f_trainer']) ?>" placeholder="Filter trainer"></th>
            <th><input type="date" name="f_date" form="filters" value="<?= e($filters['f_date']) ?>"></th>
            <th></th>
            <th></th>
            <th></th>
            <th><button type="submit" form="filters" class="btn btn-small">Zoek</button></th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($lessons) === 0): ?>
            <tr><td colspan="9" class="empty">Geen lessen gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($lessons as $lesson): ?>
                <?php $status = lesson_status($lesson); ?>
                <tr>
                    <td class="col-id"><?= e($lesson['id']) ?></td>
                    <td><?= e($lesson['title']) ?></td>
                    <td><?= e($lesson['sport_name']) ?></td>
                    <td><?= e($lesson['trainer']) ?></td>
                    <td><?= e(format_date($lesson['lesson_date'], false)) ?></td>
                    <td><?= e(substr($lesson['start_time'], 0, 5)) ?></td>
                    <td><?= e($lesson['registered']) ?> / <?= e($lesson['max_participants']) ?></td>
                    <td><span class="status status-<?= e($status) ?>"><?= e(str_replace('_', ' ', $status)) ?></span></td>
                    <td class="actions">
                        <?php if (is_logged_in()): ?>
                            <?php if (in_array((int) $lesson['id'], $my_lesson_ids, true)): ?>
                                <span class="status status-actief">ingeschreven</span>
                            <?php elseif (in_array((int) $lesson['id'], $cart, true)): ?>
                                <span class="status">in winkelmand</span>
                            <?php elseif ($status === 'open_inschrijving'): ?>
                                <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="lesson_id" value="<?= e($lesson['id']) ?>">
                                    <button type="submit" class="btn btn-small">+ Winkelmand</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                            <a href="lesson_view.php?id=<?= e($lesson['id']) ?>" class="btn btn-small btn-grey">Deelnemers</a>
                            <a href="lesson_edit.php?id=<?= e($lesson['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="lesson_delete.php?id=<?= e($lesson['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
