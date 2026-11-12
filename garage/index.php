<?php
require_once 'includes/init.php';
require_login();

$filters = beurten_read_filters();

$beurten = beurten_get_all($conn, $filters);
if ($beurten === false) {
    $beurten = array(); // fout (410) staat al in de flash
}

$total = beurten_count_all($conn);
$vandaag = date('Y-m-d');

$page_title = 'Overzicht';
require 'includes/header.php';
?>

<h1>Overzicht beurten</h1>
<p class="lead">De werkplaats heeft <?= WERKPLAATS_PLEKKEN ?> plekken per dag. Elke beurt reserveert een aantal plekken op zijn dag.</p>

<form method="get" action="index.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op type onderhoud of kenteken..." value="<?= e($filters['search']) ?>">

    <select name="type">
        <option value="">-- Alle types --</option>
        <?php foreach (beurt_types() as $type): ?>
            <option value="<?= e($type) ?>" <?= selected($filters['type'], $type) ?>><?= e($type) ?></option>
        <?php endforeach; ?>
    </select>

    <select name="periode">
        <option value="">-- Alle periodes --</option>
        <option value="komend" <?= selected($filters['periode'], 'komend') ?>>Vandaag en later</option>
        <option value="verleden" <?= selected($filters['periode'], 'verleden') ?>>Verleden</option>
    </select>

    <input type="hidden" name="sort" value="<?= e($filters['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($filters['dir']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="index.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count">
    <strong><?= count($beurten) ?></strong> van de <?= $total ?> beurten gevonden
</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id"><?= sort_link('ID', 'id', $filters) ?></th>
            <th><?= sort_link('Naam (type onderhoud)', 'type', $filters) ?></th>
            <th>Kentekens</th>
            <th><?= sort_link('Datum', 'datum', $filters) ?></th>
            <th>Tijd</th>
            <th><?= sort_link('Plekken over', 'plekken', $filters) ?></th>
            <th>Werkplaats (dag)</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($beurten) === 0): ?>
            <tr>
                <td colspan="8" class="empty">Geen beurten gevonden. <a href="index.php">Filters wissen</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($beurten as $beurt): ?>
                <?php
                $plekken_over = (int) $beurt['max_autos'] - (int) $beurt['geboekt'];
                $dag_vrij = WERKPLAATS_PLEKKEN - (int) $beurt['dag_gereserveerd'];
                $is_verleden = false;
                if ($beurt['datum'] < $vandaag) {
                    $is_verleden = true;
                }
                ?>
                <tr>
                    <td class="col-id"><?= e($beurt['id']) ?></td>
                    <td><?= e($beurt['type_onderhoud']) ?></td>
                    <td>
                        <?php if ($beurt['kentekens'] === null): ?>
                            <em>nog geen</em>
                        <?php else: ?>
                            <?= e($beurt['kentekens']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="nowrap"><?= e(format_date($beurt['datum'], false)) ?></td>
                    <td><?= e(substr($beurt['tijd'], 0, 5)) ?></td>
                    <td>
                        <?php if ($plekken_over <= 0): ?>
                            <span class="status status-vol">vol</span>
                        <?php else: ?>
                            <span class="status status-beschikbaar"><?= e($plekken_over) ?> van <?= e($beurt['max_autos']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($dag_vrij <= 0): ?>
                            <span class="status status-vol">dag vol</span>
                        <?php else: ?>
                            <?= e($dag_vrij) ?> van <?= WERKPLAATS_PLEKKEN ?> vrij
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <?php if (is_admin() && !$is_verleden && $plekken_over > 0): ?>
                            <a href="afspraak_create.php?beurt_id=<?= e($beurt['id']) ?>" class="btn btn-small">+ Afspraak</a>
                        <?php endif; ?>
                        <?php if ((int) $beurt['geboekt'] > 0): ?>
                            <a href="afspraken.php?beurt_id=<?= e($beurt['id']) ?>" class="btn btn-small btn-grey">Afspraken</a>
                        <?php elseif (is_admin()): ?>
                            <a href="beurt_delete.php?id=<?= e($beurt['id']) ?>" class="btn btn-small btn-red">Verwijderen</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
