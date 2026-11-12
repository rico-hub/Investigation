<!-- 10. Per rij een klein formulier: dropdown + OK. Terug naar dezelfde filters. -->
<form method="post" action="tabel.php?<?= e(current_query()) ?>" class="inline">
    <input type="hidden" name="action" value="set_status">
    <input type="hidden" name="item_id" value="<?= e($item['id']) ?>">
    <select name="status">
        <?php foreach (item_statuses() as $status): ?>
            <option value="<?= e($status) ?>" <?= selected($item['status'], $status) ?>><?= e($status) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-small">&#9989; OK</button>
</form>
