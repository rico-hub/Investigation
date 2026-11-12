<?php
require_once 'includes/init.php';

// ===== 1. VERWERKEN =====
$data = dropdowns_empty_data();
$received = null;

if (is_post()) {
    $data = dropdowns_form_data();      // ingevulde waarden bewaren
    $received = $_POST;                 // alleen voor de demo: laten zien wat er binnenkwam
    if (dropdowns_validate($conn, $data)) {
        set_flash('success', 'Alles geldig! In een echte app sla je nu op en doe je een redirect.');
    }
    // Bewust GEEN redirect: zo zie je welke dropdowns hun keuze onthouden
}

// ===== 2. DATA OPHALEN =====
$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}
$grouped_items = items_grouped($conn);
if ($grouped_items === false) {
    $grouped_items = array();
}

// ===== 3. TONEN =====
$page_title = 'Dropdowns';
require 'includes/header.php';
?>
<h1>Dropdowns</h1>
<p class="lead">Kies iets in elke dropdown en klik <b>Versturen</b>. Kijk dan: welke dropdown springt terug naar <i>-- Kies --</i>? En laat er 1 leeg om de <b>Error-code 420</b> te zien.</p>

<?php if ($received !== null): ?>
    <?php show_received('Wat kwam er binnen in $_POST?', $received); ?>
<?php endif; ?>

<form method="post" action="dropdowns.php">

    <section class="card brick">
        <div class="brick-head"><span class="num">1</span><h2>Vaste waarden in de HTML</h2></div>
        <p class="uitleg">&#129513; De opties typ je zelf. <code>value</code> = wat naar PHP gaat, de tekst ertussen = wat je ziet. <b>Let op:</b> na versturen staat hij weer op <i>-- Kies --</i>, want er is geen <code>selected()</code>.</p>
        <div class="live"><?php require 'stukjes/d01_vast_html.php'; ?></div>
        <?php show_code('stukjes/d01_vast_html.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">2</span><h2>Vaste waarden die de keuze onthouden</h2></div>
        <p class="uitleg">&#129513; De lijst staat 1 keer in <code>priorities()</code>. <code>foreach</code> maakt de opties, <code>selected()</code> zet je keuze terug. Dezelfde lijst gebruikt de check.</p>
        <div class="live"><?php require 'stukjes/d02_vast_onthouden.php'; ?></div>
        <p class="fix">&#128295; Springt hij terug? <code>selected()</code> vergeten, of <code>$data['priority']</code> bestaat niet.</p>
        <?php show_code('stukjes/d02_vast_onthouden.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">3</span><h2>Waarde en tekst verschillend</h2></div>
        <p class="uitleg">&#129513; Je ziet <i>Spoed!</i>, maar PHP krijgt <code>hoog</code>. Kijk na versturen in de tabel <i>Wat kwam er binnen</i>.</p>
        <div class="live"><?php require 'stukjes/d03_key_label.php'; ?></div>
        <?php show_code('stukjes/d03_key_label.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">4</span><h2>Uit de database</h2></div>
        <p class="uitleg">&#129513; Opties uit de tabel <code>categories</code>. <code>value</code> = het <b>id</b>, tekst = de <b>naam</b>. De check kijkt of het id echt bestaat.</p>
        <div class="live"><?php require 'stukjes/d04_database.php'; ?></div>
        <p class="fix">&#128295; Leeg? Database niet ge&iuml;mporteerd of de SELECT faalt (dan zie je een 410). Test: zet met F12 de value op 999 en verstuur.</p>
        <?php show_code('stukjes/d04_database.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">5</span><h2>Getallen 1 t/m 10</h2></div>
        <p class="uitleg">&#129513; Een <code>for</code>-loop maakt de 10 opties. Voor aantal, cijfer, personen.</p>
        <div class="live"><?php require 'stukjes/d05_getallen.php'; ?></div>
        <?php show_code('stukjes/d05_getallen.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">6</span><h2>Jaren en maanden</h2></div>
        <p class="uitleg">&#129513; Jaren: loop van dit jaar terug. Maanden: nummer als value, naam als tekst. Standaard staat de huidige maand gekozen.</p>
        <div class="live"><?php require 'stukjes/d06_jaren_maanden.php'; ?></div>
        <?php show_code('stukjes/d06_jaren_maanden.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">7</span><h2>Ja / Nee</h2></div>
        <p class="uitleg">&#129513; Voor een kolom <code>TINYINT(1)</code>. In de database 1 of 0, op het scherm Ja of Nee.</p>
        <div class="live"><?php require 'stukjes/d07_ja_nee.php'; ?></div>
        <?php show_code('stukjes/d07_ja_nee.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">8</span><h2>"Alle" optie voor een filter</h2></div>
        <p class="uitleg">&#129513; Staat live op <a href="tabel.php">Tabel + filter</a>, want een filter is een eigen formulier met <code>method="get"</code>.</p>
        <?php show_code('stukjes/d08_filter_alle.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">9</span><h2>Met groepen (optgroup)</h2></div>
        <p class="uitleg">&#129513; Klap hem open: items staan onder hun categorie. De SQL sorteert eerst op categorie, de loop opent een nieuwe groep als de categorie verandert.</p>
        <div class="live"><?php require 'stukjes/d09_optgroup.php'; ?></div>
        <?php show_code('stukjes/d09_optgroup.php'); ?>
    </section>

    <section class="card brick">
        <div class="brick-head"><span class="num">10</span><h2>Status wijzigen in de tabel</h2></div>
        <p class="uitleg">&#129513; Staat live op <a href="tabel.php">Tabel + filter</a>: per rij een eigen klein formulier.</p>
        <?php show_code('stukjes/d10_status_in_tabel.php'); ?>
    </section>

    <div class="submit-bar">
        <button type="submit" class="btn">&#128232; Versturen</button>
        <a href="dropdowns.php" class="btn btn-grey">&#129529; Alles leegmaken</a>
    </div>
</form>

<?php require 'includes/footer.php'; ?>
