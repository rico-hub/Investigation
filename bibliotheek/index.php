<?php
// =====================================================================
// index.php - boekenlijst: tabel, searchbar, filters, kolomfilters,
//             sorteren, TotalCount, knoppen, toevoegen aan winkelmand
// =====================================================================
require_once 'includes/init.php';

// ---- 1. VERWERKEN (POST): boek in winkelmand -----------------------
if (is_post()) {
    require_login();

    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('book_id'))) {
            set_flash('success', 'Boek toegevoegd aan je winkelmand.');
        }
    }

    // PRG: Post -> Redirect -> Get. Terug naar de lijst MET dezelfde filters.
    redirect('index.php?' . current_query());
}

// ---- 2. DATA OPHALEN (GET) ---------------------------------------
$filters = books_read_filters();

$books = books_get_all($conn, $filters);
if ($books === false) {
    $books = array(); // fout (410) staat al in de flash
}

$total = books_count_all($conn);

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

// Querystring van de huidige filters (voor de formulieren in de tabel)
$current_query = http_build_query($filters);

// ---- 3. TONEN (HTML) ---------------------------------------------
$page_title = 'Boeken';
require 'includes/header.php';
?>

<h1>Boeken</h1>

<!-- Filterbalk -->
<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op titel, auteur of categorie..." value="<?= e($filters['search']) ?>">

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <?php foreach (book_statuses() as $status): ?>
            <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="category_id">
        <option value="">-- Alle categorieën --</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['id']) ?>" <?= selected($filters['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- sortering bewaren als je opnieuw filtert -->
    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="index.php" class="btn btn-grey">Filters wissen</a>
</form>

<!-- TotalCount -->
<p class="count">
    <strong><?= count($books) ?></strong> van de <?= $total ?> boeken gevonden
</p>

<table class="table">
    <thead>
        <!-- Kolomkoppen: klik = sorteren, pijltje = filter-dropdown (js/column-filter.js) -->
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Titel', 'title', $filters) ?></th>
            <th><?= sort_link('Auteur', 'author', $filters) ?></th>
            <th><?= sort_link('Jaar', 'year', $filters) ?></th>
            <th><?= sort_link('Categorie', 'category', $filters) ?></th>
            <th><?= sort_link('Status', 'status', $filters) ?></th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($books) === 0): ?>
            <tr>
                <td colspan="7" class="empty">Geen boeken gevonden. <a href="index.php">Filters wissen</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td class="col-id"><?= e($book['id']) ?></td>
                    <td><?= e($book['title']) ?></td>
                    <td><?= e($book['author']) ?></td>
                    <td><?= e($book['publish_year']) ?></td>
                    <td><?= e($book['category_name']) ?></td>
                    <td><span class="status status-<?= e($book['status']) ?>"><?= e($book['status']) ?></span></td>
                    <td class="actions">
                        <?php if (is_logged_in() && $book['status'] === 'beschikbaar'): ?>
                            <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="book_id" value="<?= e($book['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Winkelmand</button>
                            </form>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                            <a href="book_edit.php?id=<?= e($book['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="book_delete.php?id=<?= e($book['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
