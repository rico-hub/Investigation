<?php
// =====================================================================
// info.php - voorbeeld van "embedded":
//   1. PHP embedded in HTML (alternatieve syntax: if: ... endif;)
//   2. Een embedded kaart (iframe) van een externe site
//   3. Een embedded stukje van een andere pagina (include)
// =====================================================================
require_once 'includes/init.php';

$opening_hours = array(
    'Maandag'   => '09:00 - 17:00',
    'Dinsdag'   => '09:00 - 17:00',
    'Woensdag'  => '09:00 - 20:00',
    'Donderdag' => '09:00 - 17:00',
    'Vrijdag'   => '09:00 - 17:00',
    'Zaterdag'  => '10:00 - 14:00',
    'Zondag'    => 'Gesloten',
);

$page_title = 'Info';
require 'includes/header.php';
?>

<h1>Over de bibliotheek</h1>

<div class="grid-2">
    <div class="card">
        <h2>Openingstijden</h2>
        <table class="table">
            <thead>
                <tr><th>Dag</th><th>Tijd</th></tr>
            </thead>
            <tbody>
                <!-- PHP embedded in HTML: foreach met : en endforeach -->
                <?php foreach ($opening_hours as $day => $hours): ?>
                    <tr>
                        <td><?= e($day) ?></td>
                        <td>
                            <?php if ($hours === 'Gesloten'): ?>
                                <span class="status status-reparatie"><?= e($hours) ?></span>
                            <?php else: ?>
                                <?= e($hours) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Waar zijn we?</h2>
        <!-- Embedded kaart: een iframe laadt een andere website IN deze pagina -->
        <iframe
            class="embed"
            title="Kaart"
            src="https://www.openstreetmap.org/export/embed.html?bbox=4.88%2C52.36%2C4.91%2C52.38&amp;layer=mapnik"
            loading="lazy">
        </iframe>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
