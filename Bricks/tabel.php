<?php
require_once 'includes/init.php';

// ===== 1. VERWERKEN: status wijzigen (bouwsteen 10) =====
if (is_post()) {
    if (post_param('action') === 'set_status') {
        // In je echte app hier: require_admin();
        if (item_set_status($conn, post_param('item_id'), post_param('status'))) {
            set_flash('success', 'Status van item #' . post_param('item_id') . ' is nu: ' . post_param('status'));
        }
    } else {
        function_error('Onbekende actie.');
    }
    redirect('tabel.php?' . current_query());     // terug naar dezelfde filters
}

// ===== 2. DATA =====
$filters = array('priority' => get_param('priority'));

$items = items_get_all($conn, $filters);
if ($items === false) {
    $items = array();
}

// ===== 3. TONEN =====
$page_title = 'Tabel + filter';
require 'includes/header.php';
?>
<h1>Tabel + filter</h1>
<p class="lead">Kies een prioriteit en klik <b>Filteren</b>: kijk naar de URL (<code>?priority=hoog</code>). Verander daarna een status met de dropdown in een rij: je filter blijft staan.</p>

<section class="card brick">
    <div class="brick-head"><span class="num">8</span><h2>"Alle" optie voor een filter</h2></div>
    <p class="uitleg">&#129513; Lege value = niet filteren. <code>items_get_all()</code> voegt alleen <code>AND i.priority = ?</code> toe als het een geldige waarde is.</p>
    <div class="live"><?php require 'stukjes/d08_filter_alle.php'; ?></div>
    <?php show_code('stukjes/d08_filter_alle.php'); ?>
</section>

<p class="count"><strong><?= count($items) ?></strong> gevonden</p>

<table class="table">
    <thead>
        <tr>
            <th>Nr</th>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>Categorie</th>
            <th>Prioriteit</th>
            <th>Prijs</th>
            <th>Actief</th>
            <th>Tags</th>
            <th>Status wijzigen (bouwsteen 10)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($items) === 0): ?>
            <tr><td colspan="9" class="empty">Niets gevonden. <a href="tabel.php">Filters wissen</a></td></tr>
        <?php else: ?>
            <?php $nr = 1; ?>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $nr ?></td>
                    <td class="col-id"><?= e($item['id']) ?></td>
                    <td><a href="tonen.php?id=<?= e($item['id']) ?>"><?= e($item['name']) ?></a></td>
                    <td><?= e($item['category_name']) ?></td>
                    <td><?= e(priority_label($item['priority'])) ?></td>
                    <td class="nowrap">&euro; <?= e(number_format((float) $item['price'], 2, ',', '.')) ?></td>
                    <td>
                        <?php if ((int) $item['is_active'] === 1): ?>
                            Ja
                        <?php else: ?>
                            Nee
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['tag_names'] === null): ?>
                            -
                        <?php else: ?>
                            <?= e($item['tag_names']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <span class="status status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span>
                        <?php require 'stukjes/d10_status_in_tabel.php'; ?>
                    </td>
                </tr>
                <?php $nr++; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<section class="card brick">
    <div class="brick-head"><span class="num">10</span><h2>Status wijzigen in de tabel</h2></div>
    <p class="uitleg">&#129513; Elke rij heeft een eigen klein formulier met een hidden <code>item_id</code>. Daarom kan de tabel niet in 1 groot formulier: een formulier in een formulier mag niet.</p>
    <p class="fix">&#128295; Filters weg na OK? <code>current_query()</code> ontbreekt in de form-action of de redirect. Verkeerde rij veranderd? De hidden <code>item_id</code> klopt niet.</p>
    <?php show_code('stukjes/d10_status_in_tabel.php'); ?>
</section>

<?php require 'includes/footer.php'; ?>
