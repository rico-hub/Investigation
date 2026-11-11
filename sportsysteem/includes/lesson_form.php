<?php
// includes/lesson_form.php - Verwacht: $data, $sports, $form_action, $button_text
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <tr>
            <th><label for="sport_id">Sport *</label></th>
            <td>
                <select id="sport_id" name="sport_id" required>
                    <option value="">-- Kies een sport --</option>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?= e($sport['id']) ?>" <?= selected($data['sport_id'], $sport['id']) ?>><?= e($sport['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="title">Titel *</label></th>
            <td><input type="text" id="title" name="title" maxlength="150" required value="<?= e($data['title']) ?>"></td>
        </tr>
        <tr>
            <th><label for="trainer">Trainer *</label></th>
            <td><input type="text" id="trainer" name="trainer" maxlength="100" required value="<?= e($data['trainer']) ?>"></td>
        </tr>
        <tr>
            <th><label for="lesson_date">Datum *</label></th>
            <td><input type="date" id="lesson_date" name="lesson_date" required value="<?= e($data['lesson_date']) ?>"></td>
        </tr>
        <tr>
            <th><label for="start_time">Starttijd *</label></th>
            <td><input type="time" id="start_time" name="start_time" required value="<?= e($data['start_time']) ?>"></td>
        </tr>
        <tr>
            <th><label for="max_participants">Max. deelnemers *</label></th>
            <td><input type="number" id="max_participants" name="max_participants" min="1" max="50" required value="<?= e($data['max_participants']) ?>"></td>
        </tr>
        <tr>
            <th><label for="status">Status</label></th>
            <td>
                <select id="status" name="status">
                    <?php foreach (lesson_db_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($data['status'], $status) ?>><?= e(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th></th>
            <td>
                <button type="submit" class="btn"><?= e($button_text) ?></button>
                <a href="index.php" class="btn btn-grey">Annuleren</a>
            </td>
        </tr>
    </table>
</form>
