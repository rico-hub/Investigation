<!-- Rijnummer 1, 2, 3 (los van het id) -->
<table class="table">
    <thead><tr><th>Nr</th><th>ID</th><th>Naam</th></tr></thead>
    <tbody>
        <?php $nr = 1; ?>
        <?php foreach ($all_items as $row): ?>
            <tr><td><?= $nr ?></td><td class="col-id"><?= e($row['id']) ?></td><td><?= e($row['name']) ?></td></tr>
            <?php $nr++; ?>
        <?php endforeach; ?>
    </tbody>
</table>
