<!-- 9. Groepen: SQL sorteert op categorie. Nieuwe groep = vorige sluiten, nieuwe openen -->
<select name="item_id">
    <option value="">-- Kies een item --</option>
    <?php $group = ''; ?>
    <?php foreach ($grouped_items as $grouped): ?>
        <?php if ($grouped['category_name'] !== $group): ?>
            <?php if ($group !== ''): ?>
                </optgroup>
            <?php endif; ?>
            <optgroup label="<?= e($grouped['category_name']) ?>">
            <?php $group = $grouped['category_name']; ?>
        <?php endif; ?>
        <option value="<?= e($grouped['id']) ?>" <?= selected($data['item_id'], $grouped['id']) ?>><?= e($grouped['name']) ?></option>
    <?php endforeach; ?>
    <?php if ($group !== ''): ?>
        </optgroup>
    <?php endif; ?>
</select>
