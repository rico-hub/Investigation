<!-- 2. Vaste waarden uit priorities() + selected() = keuze blijft staan -->
<select name="priority">
    <option value="">-- Kies --</option>
    <?php foreach (priorities() as $priority): ?>
        <option value="<?= e($priority) ?>" <?= selected($data['priority'], $priority) ?>><?= e(ucfirst($priority)) ?></option>
    <?php endforeach; ?>
</select>
