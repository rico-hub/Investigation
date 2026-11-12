<!-- Radio: ALLE radio's dezelfde name. Niets gekozen = niet verstuurd -->
<?php foreach (priority_labels() as $value => $label): ?>
    <label class="choice"><input type="radio" name="priority_radio" value="<?= e($value) ?>" <?= checked($data['priority_radio'], $value) ?>> <?= e($label) ?></label>
<?php endforeach; ?>
