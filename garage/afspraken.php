<?php
require_once 'includes/init.php';
require_login();

$filters = afspraken_read_filters();

$afspraken = afspraken_get_all($conn, $filters);
if ($afspraken === false) {
    $afspraken = array();
}

$vandaag = date('Y-m-d');

$page_title = 'Afspraken';
require 'includes/header.php';
?>

<h1>Afspraken</h1>

<form method="get" action="afspraken.php" id="filters" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op klant of kenteken..." value="<?= e($filters['search']) ?>">

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

    <input type="hidden" name="beurt_id" value="<?= e($filters['beurt_id']) ?>">

    <button type="submit" class="btn">Filteren</button>
    <a href="afspraken.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count">
    <strong><?= count($afspraken) ?></strong> afspraken gevonden
    <?php if (is_valid_id($filters['beurt_id'])): ?>
        (alleen beurt #<?= e($filters['beurt_id']) ?>, <a href="afspraken.php">alle afspraken tonen</a>)
    <?php endif; ?>
</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Klant</th>
            <th>Telefoon</th>
            <th>Kenteken</th>
            <th>Type onderhoud</th>
            <th>Datum</th>
            <th>Tijd</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($afspraken) === 0): ?>
            <tr><td colspan="8" class="empty">Geen afspraken gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($afspraken as $afspraak): ?>
                <tr>
                    <td class="col-id"><?= e($afspraak['id']) ?></td>
                    <td><?= e($afspraak['klant_naam']) ?></td>
                    <td><?= e($afspraak['telefoon']) ?></td>
                    <td class="nowrap"><strong><?= e($afspraak['kenteken']) ?></strong></td>
                    <td><?= e($afspraak['type_onderhoud']) ?></td>
                    <td class="nowrap"><?= e(format_date($afspraak['datum'], false)) ?></td>
                    <td><?= e(substr($afspraak['tijd'], 0, 5)) ?></td>
                    <td class="actions">
                        <?php if (is_admin() && $afspraak['datum'] >= $vandaag): ?>
                            <a href="afspraak_cancel.php?id=<?= e($afspraak['id']) ?>" class="btn btn-small btn-red">Annuleren</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
