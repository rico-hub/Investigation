<?php
// includes/complaint_form.php - voor create en edit
// Verwacht: $data, $categories, $admins, $form_action, $button_text
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <tr>
            <th><label for="title">Titel *</label></th>
            <td><input type="text" id="title" name="title" minlength="3" maxlength="150" required value="<?= e($data['title']) ?>"></td>
        </tr>
        <tr>
            <th><label for="category_id">Categorie *</label></th>
            <td>
                <select id="category_id" name="category_id" required>
                    <option value="">-- Kies een categorie --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= selected($data['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="priority">Prioriteit *</label></th>
            <td>
                <select id="priority" name="priority">
                    <?php foreach (complaint_priorities() as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= selected($data['priority'], $priority) ?>><?= e(ucfirst($priority)) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="description">Beschrijving *</label></th>
            <td><textarea id="description" name="description" required><?= e($data['description']) ?></textarea></td>
        </tr>

        <?php if (is_admin()): ?>
            <!-- Alleen admin ziet (en mag) status en behandelaar -->
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select id="status" name="status">
                        <?php foreach (complaint_statuses() as $status): ?>
                            <option value="<?= e($status) ?>" <?= selected($data['status'], $status) ?>><?= e(str_replace('_', ' ', $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="assigned_to">Behandelaar</label></th>
                <td>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">-- Niemand --</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?= e($admin['id']) ?>" <?= selected($data['assigned_to'], $admin['id']) ?>><?= e($admin['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <th></th>
            <td>
                <button type="submit" class="btn"><?= e($button_text) ?></button>
                <a href="index.php" class="btn btn-grey">Annuleren</a>
            </td>
        </tr>
    </table>
</form>
