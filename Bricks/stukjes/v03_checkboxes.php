<!-- Meerdere checkboxes: [] in de name, dan krijgt PHP een array -->
<?php foreach ($tags as $tag): ?>
    <label class="choice"><input type="checkbox" name="tags[]" value="<?= e($tag['id']) ?>" <?= checked_in($data['tags'], $tag['id']) ?>> <?= e($tag['name']) ?></label>
<?php endforeach; ?>
