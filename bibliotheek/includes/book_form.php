<?php
// includes/book_form.php - formulier voor toevoegen EN wijzigen (in tabel-vorm)
// Verwacht: $data, $categories, $form_action, $button_text
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <tr>
            <th><label for="title">Titel *</label></th>
            <td><input type="text" id="title" name="title" maxlength="200" required value="<?= e($data['title']) ?>"></td>
        </tr>
        <tr>
            <th><label for="author">Auteur *</label></th>
            <td><input type="text" id="author" name="author" maxlength="150" required value="<?= e($data['author']) ?>"></td>
        </tr>
        <tr>
            <th><label for="category_id">Categorie *</label></th>
            <td>
                <!-- Dropdown uit de database; gekozen waarde blijft behouden na een fout -->
                <select id="category_id" name="category_id" required>
                    <option value="">-- Kies een categorie --</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>" <?= selected($data['category_id'], $category['id']) ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="publish_year">Jaar</label></th>
            <td><input type="number" id="publish_year" name="publish_year" min="1450" max="2100" value="<?= e($data['publish_year']) ?>"></td>
        </tr>
        <tr>
            <th><label for="status">Status *</label></th>
            <td>
                <select id="status" name="status">
                    <?php foreach (book_statuses() as $status): ?>
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
