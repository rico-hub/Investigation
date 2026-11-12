<?php
// =====================================================================
// index.php - READ: lijst met tabel, searchbar, filters, sorteren,
//             TotalCount, knoppen en "toevoegen aan winkelmand"
// Elke pagina heeft dezelfde 3 blokken: 1. VERWERKEN  2. DATA OPHALEN  3. TONEN
// =====================================================================
require_once 'includes/init.php';

// ---- 1. VERWERKEN (POST): animal in winkelmand -----------------------
// (geen winkelmand? haal dit hele if-blok weg)
if (is_post()) {
    require_login();

    if (post_param('action') === 'add_to_cart') {
        if (cart_add($conn, post_param('animal_id'))) {
            set_flash('success', 'Dier toegevoegd aan je bezoeklijst.');
        }
    }

    // PRG: Post -> Redirect -> Get. Terug naar de lijst MET dezelfde filters.
    redirect('index.php?' . current_query());
}

// ---- 2. DATA OPHALEN (GET) ---------------------------------------
$filters = animals_read_filters();

$animals = animals_get_all($conn, $filters);
if ($animals === false) {
    $animals = array(); // fout (410) staat al in de flash
}

$total = animals_count_all($conn);

$animal_types = animal_types_get_all($conn);
if ($animal_types === false) {
    $animal_types = array();
}

// Querystring van de huidige filters (voor de formulieren in de tabel)
$current_query = http_build_query($filters);

// ---- 3. TONEN (HTML) ---------------------------------------------
$page_title = 'Dieren';
require 'includes/header.php';
?>

<h1>Dieren</h1>

<!-- Filterbalk -->
<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op naam of diersoort..." value="<?= e($filters['search']) ?>">

    <select name="status">
        <option value="">-- Alle statussen --</option>
        <?php foreach (animal_statuses() as $status): ?>
            <option value="<?= e($status) ?>" <?= selected($filters['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="animal_type_id">
        <option value="">-- Alle diersoorten --</option>
        <?php foreach ($animal_types as $animal_type): ?>
            <option value="<?= e($animal_type['id']) ?>" <?= selected($filters['animal_type_id'], $animal_type['id']) ?>><?= e($animal_type['name']) ?></option>
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
    <strong><?= count($animals) ?></strong> van de <?= $total ?> dieren gevonden
</p>

<table class="table">
    <thead>
        <!-- AANPASSEN: kolomkoppen. sort_link('Tekst', 'naam uit de whitelist in animals_get_all', $filters) -->
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Naam', 'name', $filters) ?></th>
            <th><?= sort_link('Diersoort', 'animal_type', $filters) ?></th>
            <th><?= sort_link('Leeftijd', 'age', $filters) ?></th>
            <th><?= sort_link('Status', 'status', $filters) ?></th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($animals) === 0): ?>
            <tr>
                <!-- AANPASSEN: colspan = aantal kolommen -->
                <td colspan="6" class="empty">Geen dieren gevonden. <a href="index.php">Filters wissen</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($animals as $animal): ?>
                <!-- AANPASSEN: 1 <td> per kolom, zelfde volgorde als de koppen -->
                <tr>
                    <td class="col-id"><?= e($animal['id']) ?></td>
                    <td><?= e($animal['name']) ?></td>
                    <td><?= e($animal['animal_type_name']) ?></td>
                    <td><?= e($animal['age']) ?> jaar</td>
                    <td><span class="status status-<?= e($animal['status']) ?>"><?= e($animal['status']) ?></span></td>
                    <td class="actions">
                        <?php if (is_logged_in() && $animal['status'] === 'beschikbaar'): ?>
                            <form method="post" action="index.php?<?= e($current_query) ?>" class="inline">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="animal_id" value="<?= e($animal['id']) ?>">
                                <button type="submit" class="btn btn-small">+ Bezoeklijst</button>
                            </form>
                        <?php endif; ?>

                        <?php if (is_admin()): ?>
                            <a href="animal_edit.php?id=<?= e($animal['id']) ?>" class="btn btn-small btn-grey">Wijzigen</a>
                            <a href="animal_delete.php?id=<?= e($animal['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
