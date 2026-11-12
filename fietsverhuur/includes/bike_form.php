<?php
// includes/bike_form.php - 1 formulier voor toevoegen EN wijzigen (in tabel-vorm)
// Verwacht: $data, $bike_types, $form_action, $button_text
// AANPASSEN: 1 <tr> per veld. name="..." moet gelijk zijn aan de key in bike_form_data().
// Meer soorten velden (radio, checkbox, textarea...): map Bricks/stukjes of Notion pagina 7.
?>
<form method="post" action="<?= e($form_action) ?>" class="card form">
    <table class="form-table">
        <!-- TEKST -->
        <tr>
            <th><label for="name">Naam *</label></th>
            <td><input type="text" id="name" name="name" maxlength="150" required value="<?= e($data['name']) ?>"></td>
        </tr>
        <!-- TEKST: framenummer (uniek) -->
        <tr>
            <th><label for="frame_number">Framenummer *</label></th>
            <td><input type="text" id="frame_number" name="frame_number" maxlength="20" required value="<?= e($data['frame_number']) ?>"></td>
        </tr>
        <!-- DROPDOWN UIT DE DATABASE (foreign key); gekozen waarde blijft behouden na een fout -->
        <tr>
            <th><label for="bike_type_id">Type *</label></th>
            <td>
                <select id="bike_type_id" name="bike_type_id" required>
                    <option value="">-- Kies een type --</option>
                    <?php foreach ($bike_types as $bike_type): ?>
                        <option value="<?= e($bike_type['id']) ?>" <?= selected($data['bike_type_id'], $bike_type['id']) ?>><?= e($bike_type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <!-- BEDRAG -->
        <tr>
            <th><label for="price">Prijs per dag (&euro;) *</label></th>
            <td><input type="number" id="price" name="price" min="0" max="999999.99" step="0.01" required value="<?= e($data['price']) ?>"></td>
        </tr>
        <!-- DROPDOWN MET VASTE WAARDEN -->
        <tr>
            <th><label for="status">Status *</label></th>
            <td>
                <select id="status" name="status">
                    <?php foreach (bike_statuses() as $status): ?>
                        <option value="<?= e($status) ?>" <?= selected($data['status'], $status) ?>><?= e(ucfirst(str_replace('_', ' ', $status))) ?></option>
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
