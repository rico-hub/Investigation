<!-- 6a. Dit jaar en 5 jaar terug -->
<select name="year">
    <?php for ($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--): ?>
        <option value="<?= $y ?>" <?= selected($data['year'], $y) ?>><?= $y ?></option>
    <?php endfor; ?>
</select>

<!-- 6b. Maanden: nummer als value, naam als tekst (months() begint bij 1) -->
<select name="month">
    <?php foreach (months() as $number => $name): ?>
        <option value="<?= $number ?>" <?= selected($data['month'], $number) ?>><?= e($name) ?></option>
    <?php endforeach; ?>
</select>
