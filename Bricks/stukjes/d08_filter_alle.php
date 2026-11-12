<!-- 8. Filter met "Alle": method="get", lege value = niet filteren -->
<form method="get" action="tabel.php" class="filter-bar">
    <select name="priority">
        <option value="">-- Alle prioriteiten --</option>
        <?php foreach (priority_labels() as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= selected($filters['priority'], $value) ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">&#128269; Filteren</button>
    <a href="tabel.php" class="btn btn-grey">&#129529; Filters wissen</a>
</form>
