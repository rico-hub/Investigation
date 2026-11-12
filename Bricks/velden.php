<?php
require_once 'includes/init.php';

// ===== 1. VERWERKEN =====
$data = velden_empty_data();
$received = null;

if (is_post()) {
    $data = velden_form_data();
    $received = $_POST;
    if (isset($received['password'])) {
        $received['password'] = '(verborgen, ' . strlen($received['password']) . ' tekens)';
    }
    if (velden_validate($data)) {
        set_flash('success', 'Alles geldig! In een echte app sla je nu op en doe je een redirect.');
    }
}

// ===== 2. DATA OPHALEN =====
$tags = tags_get_all($conn);
if ($tags === false) {
    $tags = array();
}

// ===== 3. TONEN =====
$page_title = 'Velden';
require 'includes/header.php';
?>
<h1>Andere velden</h1>
<p class="lead">Vul in en klik <b>Versturen</b>. Probeer ook fouten: geen radio kiezen, datum in het verleden, kort wachtwoord. Let op wat er na een fout <b>blijft staan</b> en wat <b>leeg</b> wordt.</p>

<?php if ($received !== null): ?>
    <?php show_received('Wat kwam er binnen in $_POST?', $received); ?>
<?php endif; ?>

<form method="post" action="velden.php">

    <section class="card brick">
        <div class="brick-head"><span class="num">A</span><h2>Radio buttons</h2></div>
        <p class="uitleg">&#129513; Zelfde als een dropdown, maar alle keuzes zichtbaar. Alle radio's hebben <b>dezelfde name</b>. Niets gekozen? Dan staat <code>priority_radio</code> niet eens in $_POST.</p>
        <div class="live"><?php require 'stukjes/v01_radio.php'; ?></div>
        <?php show_code('stukjes/v01_radio.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">B</span><h2>1 checkbox (ja/nee)</h2></div>
        <p class="uitleg">&#129513; Niet aangevinkt = <b>niet verstuurd</b>. Kijk in de tabel hierboven: <code>is_featured</code> ontbreekt dan. <code>velden_form_data()</code> maakt er zelf <code>'0'</code> van.</p>
        <div class="live"><?php require 'stukjes/v02_checkbox.php'; ?></div>
        <?php show_code('stukjes/v02_checkbox.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">C</span><h2>Meerdere checkboxes</h2></div>
        <p class="uitleg">&#129513; De <code>[]</code> in <code>name="tags[]"</code> maakt er een lijst van. Inlezen met <code>post_array()</code>, terugzetten met <code>checked_in()</code>.</p>
        <div class="live"><?php require 'stukjes/v03_checkboxes.php'; ?></div>
        <p class="fix">&#128295; Alleen de laatste komt aan? De <code>[]</code> vergeten.</p>
        <?php show_code('stukjes/v03_checkboxes.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">D</span><h2>Getal en bedrag</h2></div>
        <p class="uitleg">&#129513; <code>step="0.01"</code> = centen toegestaan. Zonder step weigert de browser 9.99.</p>
        <div class="live"><?php require 'stukjes/v04_getal_bedrag.php'; ?></div>
        <?php show_code('stukjes/v04_getal_bedrag.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">E</span><h2>Datum (niet in het verleden)</h2></div>
        <p class="uitleg">&#129513; <code>min</code> grijst oude datums uit in de kalender. PHP checkt het ook (<code>is_valid_date()</code> en vergelijken met vandaag).</p>
        <div class="live"><?php require 'stukjes/v05_datum.php'; ?></div>
        <?php show_code('stukjes/v05_datum.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">F</span><h2>Datum + tijd</h2></div>
        <p class="uitleg">&#129513; Mag leeg blijven. Kijk na versturen naar de <code>T</code> in de waarde: die vervang je door een spatie voor een DATETIME-kolom.</p>
        <div class="live"><?php require 'stukjes/v06_datum_tijd.php'; ?></div>
        <?php show_code('stukjes/v06_datum_tijd.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">G</span><h2>Tekst, e-mail, wachtwoord, lange tekst, verborgen</h2></div>
        <p class="uitleg">&#129513; Na een fout blijven naam, e-mail en omschrijving staan. Het wachtwoord <b>niet</b> (expres). Het verborgen veld <code>secret_id</code> zie je niet, maar staat wel in $_POST.</p>
        <div class="live stack"><?php require 'stukjes/v07_tekst.php'; ?></div>
        <?php show_code('stukjes/v07_tekst.php'); ?>
    </section>

    <div class="submit-bar">
        <button type="submit" class="btn">&#128232; Versturen</button>
        <a href="velden.php" class="btn btn-grey">&#129529; Alles leegmaken</a>
    </div>
</form>

<?php require 'includes/footer.php'; ?>
