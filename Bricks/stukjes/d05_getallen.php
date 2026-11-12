<!-- 5. for-loop maakt de opties 1 t/m 10 -->
<select name="quantity">
    <?php for ($i = 1; $i <= 10; $i++): ?>
        <option value="<?= $i ?>" <?= selected($data['quantity'], $i) ?>><?= $i ?></option>
    <?php endfor; ?>
</select>
