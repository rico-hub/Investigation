<?php
// includes/item_form.php - 1 formulier voor toevoegen EN wijzigen (in tabel-vorm)
// Verwacht: $data, $categories, $form_action, $button_text
// AANPASSEN: 1 <tr> per veld. name="..." moet gelijk zijn aan de key in item_form_data().
// Meer soorten velden (radio, checkbox, textarea...): map Bricks/stukjes of Notion pagina 7.
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <!-- TEKST -->
        <tr>
            <th><label for="name">Naam *</label></th>
            <td><input type="text" id="name" name="name" maxlength="150" required value="<?= e($data['name']) ?>"></td>
        </tr>
        <!-- DROPDOWN UIT DE DATABASE (foreign key); gekozen waarde blijft behouden na een fout -->
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
        <!-- BEDRAG -->
        <tr>
            <th><label for="price">Prijs (&euro;) *</label></th>
            <td><input type="number" id="price" name="price" min="0" max="999999.99" step="0.01" required value="<?= e($data['price']) ?>"></td>
        </tr>
        <!-- DATUM (mag leeg) -->
        <tr>
            <th><label for="item_date">Datum</label></th>
            <td><input type="date" id="item_date" name="item_date" value="<?= e($data['item_date']) ?>"></td>
        </tr>
        <!-- DROPDOWN MET VASTE WAARDEN -->
        <tr>
            <th><label for="status">Status *</label></th>
            <td>
                <select id="status" name="status">
                    <?php foreach (item_statuses() as $status): ?>
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
