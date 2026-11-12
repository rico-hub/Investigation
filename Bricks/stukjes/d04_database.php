<!-- 4. Opties uit de tabel categories: value = id, tekst = name -->
<select name="category_id">
    <option value="">-- Kies een categorie --</option>
    <?php foreach ($categories as $category): ?>
        <option value="<?= e($category['id']) ?>" <?= selected($data['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
    <?php endforeach; ?>
</select>
