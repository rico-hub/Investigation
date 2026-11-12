<?php
require_once 'includes/init.php';

// ===== 1. VERWERKEN: welke knop is gedrukt? =====
if (is_post()) {
    $action = post_param('action');
    $item_id = post_param('item_id');

    if ($action === 'approve') {
        if (item_set_status($conn, $item_id, 'actief')) {
            set_flash('success', 'Je drukte op Goedkeuren (action = approve). Status is nu actief.');
        }
    } elseif ($action === 'reject') {
        if (item_set_status($conn, $item_id, 'inactief')) {
            set_flash('success', 'Je drukte op Afwijzen (action = reject). Status is nu inactief.');
        }
    } else {
        function_error('Onbekende actie.');
    }
    redirect('knoppen.php');           // PRG: na een POST altijd redirect
}

// ===== 2. DATA =====
$item = item_find($conn, 1);
if ($item === false || $item === null) {
    $item = array('id' => 0, 'name' => '(item 1 niet gevonden)', 'status' => '-');
}

// ===== 3. TONEN =====
$page_title = 'Knoppen';
require 'includes/header.php';
?>
<h1>Knoppen</h1>

<section class="card brick">
    <div class="brick-head"><span class="num">1</span><h2>2 knoppen in 1 formulier</h2></div>
    <p class="uitleg">&#129513; Beide knoppen hebben <code>name="action"</code>, maar een andere <code>value</code>. PHP kiest met <code>if / elseif</code> wat er moet gebeuren, en doet daarna een <b>redirect</b> (druk maar op F5: er wordt niets dubbel verstuurd).</p>
    <div class="live">
        <p>Item <b>#<?= e($item['id']) ?> <?= e($item['name']) ?></b>, status nu: <span class="status status-<?= e($item['status']) ?>"><?= e($item['status']) ?></span></p>
        <?php require 'stukjes/k01_twee_knoppen.php'; ?>
    </div>
    <p class="fix">&#128295; Er gebeurt niets? De knop mist <code>name="action"</code>, of hij staat buiten de <code>form</code>. &quot;Onbekende actie&quot;? De value is anders gespeld dan in de <code>if</code>.</p>
    <?php show_code('stukjes/k01_twee_knoppen.php'); ?>
    <?php show_code('knoppen.php'); ?>
</section>

<section class="card brick">
    <div class="brick-head"><span class="num">2</span><h2>Link of knop?</h2></div>
    <p class="uitleg">&#129513; <b>Alleen kijken of naar een andere pagina</b> = een link (<code>a</code> met class <code>btn</code>). <b>Iets veranderen in de database</b> = formulier met POST, zoals hierboven. Een link kan per ongeluk geopend worden, dus nooit verwijderen of wijzigen via een link.</p>
    <div class="live"><?php require 'stukjes/k02_link_of_knop.php'; ?></div>
    <?php show_code('stukjes/k02_link_of_knop.php'); ?>
</section>

<?php require 'includes/footer.php'; ?>
