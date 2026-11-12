<?php
// =====================================================================
// index.php - READ: lijst met tabel, searchbar, filters, sorteren,
//             TotalCount, knoppen en "toevoegen aan winkelmand"
// Elke pagina heeft dezelfde 3 blokken: 1. VERWERKEN  2. DATA OPHALEN  3. TONEN
// =====================================================================
require_once 'includes/init.php';

// ---- 1. VERWERKEN (POST): item in winkelmand -----------------------
// (geen winkelmand? haal dit hele if-blok weg)
if (is_post()) {
    require_login();

    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('item_id'))) {
            set_flash('success', 'Item toegevoegd aan je winkelmand.');
        }
    }

    // PRG: Post -> Redirect -> Get. Terug naar de lijst MET dezelfde filters.
    redirect('index.php?' . current_query());
}

// ---- 2. DATA OPHALEN (GET) ---------------------------------------
$filters = items_read_filters();

$items = items_get_all($conn, $filters);
if ($items === false) {
    $items = array(); // fout (410) staat al in de flash
}

$total = items_count_all($conn);

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

$colors = colors_get_all($conn);
if ($colors === false) {
    $colors = array();
}

// Querystring van de huidige filters (voor de formulieren in de tabel)
$current_query = http_build_query($filters);

// ---- 3. TONEN (HTML) ---------------------------------------------
$page_title = 'Items';
require 'includes/header.php';
?>

<h1>Items</h1>

<!-- Filterbalk -->
<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op naam of categorie..." value="<?= e($filters['search']) ?>">

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <?php foreach (item_statuses() as $status): ?>
            <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="category_id">
        <option value="">-- Alle categorieën --</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['id']) ?>" <?= selected($filters['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="color_id">
        <option value="">-- Alle kleuren --</option>
        <?php foreach ($colors as $color): ?>
            <option value="<?= e($color['id']) ?>" <?= selected($filters['color_id'], $color['id']) ?>><?= e($color['name']) ?></option>
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
    <strong><?= count($items) ?></strong> van de <?= $total ?> items gevonden
</p>

<table class="table">
    <thead>
        <!-- AANPASSEN: kolomkoppen. sort_link('Tekst', 'naam uit de whitelist in items_get_all', $filters) -->
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Naam', 'name', $filters) ?></th>
            <th><?= sort_link('Categorie', 'category', $filters) ?></th>
            <th><?= sort_link('Kleur', 'color', $filters) ?></th>
            <th><?= sort_link('Prijs', 'price', $filters) ?></th>
            <th><?= sort_link('Datum', 'date', $filters) ?></th>
            <th><?= sort_link('Status', 'status', $filters) ?></th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($items) === 0): ?>
            <tr>
                <!-- AANPASSEN: colspan = aantal kolommen -->
                <td colspan="8" class="empty">Geen items gevonden. <a href="index.php">Filters wissen</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <!-- AANPASSEN: 1 <td> per kolom, zelfde volgorde als de koppen -->
                <tr>
                    <td class="col-id"><?= e($item['id']) ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td><?= e($item['category_name']) ?></td>
                    <td><?= e($item['color_name']) ?></td>
                    <td>&euro; <?= e(number_format((float) $item['price'], 2, ',', '.')) ?></td>
                    <td><?= e(format_date($item['item_date'], false)) ?></td>
                    <td><span class="status status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span></td>
                    <td class="actions">
                        <?php if (is_logged_in() && $item['status'] === 'actief'): ?>
                            <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Winkelmand</button>
                            </form>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                            <a href="item_edit.php?id=<?= e($item['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="item_delete.php?id=<?= e($item['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
