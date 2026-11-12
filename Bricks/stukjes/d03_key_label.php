<!-- 3. key = value (database), waarde = tekst (scherm) -->
<select name="priority_label">
    <option value="">-- Kies --</option>
    <?php foreach (priority_labels() as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= selected($data['priority_label'], $value) ?>><?= e($label) ?></option>
    <?php endforeach; ?>
</select>
