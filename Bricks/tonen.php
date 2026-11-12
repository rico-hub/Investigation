<?php
require_once 'includes/init.php';

// ===== DATA: welk item? (?id= uit de URL, standaard 1) =====
$id = get_param('id');
if ($id === '') {
    $id = '1';
}
if (!is_valid_id($id)) {
    function_error('Geen geldig item gekozen.');
    redirect('tonen.php');
}

$item = item_find($conn, (int) $id);
if ($item === false) {
    $item = null;
} elseif ($item === null) {
    function_error('Item #' . $id . ' bestaat niet.');
    redirect('tonen.php');
}

$all_items = items_get_all($conn, array('priority' => ''));
if ($all_items === false) {
    $all_items = array();
}

// Elke bouwsteen: titel, kolom die je rauw ziet, bestand
$bricks = array(
    array('Geld', 'price', 'stukjes/t01_geld.php'),
    array('Datum', 'start_date', 'stukjes/t02_datum.php'),
    array('Datum + tijd', 'created_at', 'stukjes/t03_datum_tijd.php'),
    array('Ja / nee', 'is_active', 'stukjes/t04_ja_nee.php'),
    array('Leeg wordt streepje, enters blijven', 'description', 'stukjes/t05_leeg_streepje.php'),
    array('Lange tekst inkorten', 'description', 'stukjes/t06_inkorten.php'),
    array('Code naar tekst + status met kleur', 'priority', 'stukjes/t07_label_en_status.php'),
);

$page_title = 'Netjes tonen';
require 'includes/header.php';
?>
<h1>Waarden netjes tonen</h1>
<p class="lead">Links wat er <b>rauw</b> uit de database komt, rechts hoe je het <b>netjes</b> laat zien. Kies een ander item om te zien wat er gebeurt met lege waarden (Woordenboek en Tennisracket hebben een lege omschrijving).</p>

<!-- Item kiezen: dropdown uit de database met method="get" -->
<form method="get" action="tonen.php" class="card filter-bar">
    <label for="id"><b>Item:</b></label>
    <select name="id" id="id">
        <?php foreach ($all_items as $row): ?>
            <option value="<?= e($row['id']) ?>" <?= selected($id, $row['id']) ?>>#<?= e($row['id']) ?> <?= e($row['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">&#128065; Tonen</button>
</form>

<?php if ($item !== null): ?>
    <?php $nr = 1; ?>
    <?php foreach ($bricks as $brick): ?>
        <section class="card brick">
            <div class="brick-head"><span class="num"><?= $nr ?></span><h2><?= e($brick[0]) ?></h2></div>
            <div class="compare">
                <div class="raw">
                    <div class="label">Rauw: <code>$item['<?= e($brick[1]) ?>']</code></div>
                    <code><?= nl2br(e(raw_value($item[$brick[1]]))) ?></code>
                </div>
                <div class="nice">
                    <div class="label">Netjes</div>
                    <?php require $brick[2]; ?>
                </div>
            </div>
            <?php show_code($brick[2]); ?>
        </section>
        <?php $nr++; ?>
    <?php endforeach; ?>
<?php endif; ?>

<section class="card brick">
    <div class="brick-head"><span class="num"><?= count($bricks) + 1 ?></span><h2>Rijnummer (los van het id)</h2></div>
    <p class="uitleg">&#129513; <code>$nr</code> begint op 1 en telt per rij op. Het id kan gaten hebben (verwijderd), het rijnummer niet.</p>
    <div class="live"><?php require 'stukjes/t08_rijnummer.php'; ?></div>
    <?php show_code('stukjes/t08_rijnummer.php'); ?>
</section>

<?php require 'includes/footer.php'; ?>
