<?php
require_once 'includes/init.php';

$pages = array(
    array('dropdowns.php', 'Dropdowns', '9 soorten live: vaste waarden, onthouden, key => label, uit de database, getallen, jaren/maanden, ja/nee, groepen.'),
    array('velden.php', 'Velden', 'Radio, 1 checkbox, meerdere checkboxes, getal, bedrag, datum, datum + tijd, tekst, e-mail, wachtwoord, verborgen.'),
    array('knoppen.php', 'Knoppen', '2 knoppen in 1 formulier (echt opslaan + redirect), en wanneer een link en wanneer een knop.'),
    array('tonen.php', 'Netjes tonen', 'Rauw uit de database naast netjes: geld, datum, ja/nee, leeg = streepje, inkorten, status-kleur, rijnummer.'),
    array('tabel.php', 'Tabel + filter', 'Dropdown met "Alle" als filter, en de status per rij wijzigen met een dropdown.'),
    array('opslaan.php', 'Alles samen: opslaan', '1 formulier met alle bouwstenen dat echt in de database opslaat, met 420-checks en waarden die blijven staan.'),
);

$page_title = 'Overzicht';
require 'includes/header.php';
?>
<h1>Bricks: de bouwstenen</h1>
<p class="lead">Alles van Notion pagina 7, maar dan <b>live</b>. Elk blok heeft: &#129513; wat het doet, het echte element om mee te klikken, en <b>Bekijk de code</b> eronder (precies de code die het blok maakt).</p>

<div class="grid">
    <?php foreach ($pages as $page): ?>
        <a href="<?= e($page[0]) ?>" class="card tile">
            <h2><?= e($page[1]) ?></h2>
            <p><?= e($page[2]) ?></p>
        </a>
    <?php endforeach; ?>
</div>

<div class="card puzzle">
    <b>Zo werkt elk formulierveld (4 puzzelstukjes):</b>
    <ol>
        <li><b>HTML</b>: het veld heeft een <code>name</code>, bv. <code>name="priority"</code></li>
        <li><b>Inlezen</b>: PHP leest met precies dezelfde naam: <code>post_param('priority')</code></li>
        <li><b>Checken + opslaan</b>: in je <code>..._validate()</code> (420), daarna de query (410)</li>
        <li><b>Terugzetten</b>: na een fout met <code>value</code>, <code>selected()</code> of <code>checked()</code></li>
    </ol>
    <p class="hint">HTML-checks als <code>required</code> staan hier expres uit, zodat je de PHP-check (Error-code 420) ziet.</p>
</div>

<div class="card">
    <h2>Mappen</h2>
    <ul>
        <li><code>stukjes/</code> = elke bouwsteen als los bestand. Die bestanden plak je in je eigen formulier.</li>
        <li><code>functions/bricks.php</code> = de lijsten (<code>priorities()</code>, <code>priority_labels()</code>), checks en queries.</li>
        <li><code>functions/helpers.php</code> en <code>functions/database.php</code> = de starter kit (Notion pagina 1).</li>
        <li><code>database.sql</code> = database <code>bricks</code> met dummy data. Opnieuw importeren = alles weer zoals in het begin.</li>
    </ul>
</div>

<?php require 'includes/footer.php'; ?>
