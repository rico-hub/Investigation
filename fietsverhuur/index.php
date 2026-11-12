<?php
// =====================================================================
// index.php - READ: lijst met tabel, searchbar, filters, sorteren,
//             TotalCount, knoppen en "toevoegen aan winkelmand"
// Elke pagina heeft dezelfde 3 blokken: 1. VERWERKEN  2. DATA OPHALEN  3. TONEN
// =====================================================================
require_once 'includes/init.php';

// ---- 1. VERWERKEN (POST): bike in winkelmand -----------------------
// (geen winkelmand? haal dit hele if-blok weg)
if (is_post()) {
    require_login();

    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('bike_id'))) {
            set_flash('success', 'Fiets toegevoegd aan je winkelmand.');
        }
    }

    // PRG: Post -> Redirect -> Get. Terug naar de lijst MET dezelfde filters.
    redirect('index.php?' . current_query());
}

// ---- 2. DATA OPHALEN (GET) ---------------------------------------
$filters = bikes_read_filters();

$bikes = bikes_get_all($conn, $filters);
if ($bikes === false) {
    $bikes = array(); // fout (410) staat al in de flash
}

$total = bikes_count_all($conn);

$bike_types = bike_types_get_all($conn);
if ($bike_types === false) {
    $bike_types = array();
}

// Querystring van de huidige filters (voor de formulieren in de tabel)
$current_query = http_build_query($filters);

// ---- 3. TONEN (HTML) ---------------------------------------------
$page_title = 'Fietsen';
require 'includes/header.php';
?>

<h1>Fietsen</h1>

<!-- Filterbalk -->
<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op naam of framenummer..." value="<?= e($filters['search']) ?>">

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <?php foreach (bike_statuses() as $status): ?>
            <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e(ucfirst(str_replace('_', ' ', $status))) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="bike_type_id">
        <option value="">-- Alle types --</option>
        <?php foreach ($bike_types as $bike_type): ?>
            <option value="<?= e($bike_type['id']) ?>" <?= selected($filters['bike_type_id'], $bike_type['id']) ?>><?= e($bike_type['name']) ?></option>
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
    <strong><?= count($bikes) ?></strong> van de <?= $total ?> fietsen gevonden
</p>

<table class="table">
    <thead>
        <!-- AANPASSEN: kolomkoppen. sort_link('Tekst', 'naam uit de whitelist in bikes_get_all', $filters) -->
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Naam', 'name', $filters) ?></th>
            <th><?= sort_link('Framenummer', 'frame', $filters) ?></th>
            <th><?= sort_link('Type', 'bike_type', $filters) ?></th>
            <th><?= sort_link('Prijs per dag', 'price', $filters) ?></th>
            <th><?= sort_link('Status', 'status', $filters) ?></th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($bikes) === 0): ?>
            <tr>
                <!-- AANPASSEN: colspan = aantal kolommen -->
                <td colspan="7" class="empty">Geen fietsen gevonden. <a href="index.php">Filters wissen</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($bikes as $bike): ?>
                <!-- AANPASSEN: 1 <td> per kolom, zelfde volgorde als de koppen -->
                <tr>
                    <td class="col-id"><?= e($bike['id']) ?></td>
                    <td><?= e($bike['name']) ?></td>
                    <td><?= e($bike['frame_number']) ?></td>
                    <td><?= e($bike['bike_type_name']) ?></td>
                    <td>&euro; <?= e(number_format((float) $bike['price'], 2, ',', '.')) ?></td>
                    <td><span class="status status-<?= e($bike['status']) ?>"><?= e(str_replace('_', ' ', $bike['status'])) ?></span></td>
                    <td class="actions">
                        <?php if (is_logged_in() && $bike['status'] === 'beschikbaar'): ?>
                            <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="bike_id" value="<?= e($bike['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Winkelmand</button>
                            </form>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                            <a href="bike_edit.php?id=<?= e($bike['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="bike_delete.php?id=<?= e($bike['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
