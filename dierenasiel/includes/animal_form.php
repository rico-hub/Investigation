<?php
// includes/animal_form.php - 1 formulier voor toevoegen EN wijzigen (in tabel-vorm)
// Verwacht: $data, $animal_types, $form_action, $button_text
// AANPASSEN: 1 <tr> per veld. name="..." moet gelijk zijn aan de key in animal_form_data().
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
            <th><label for="animal_type_id">Diersoort *</label></th>
            <td>
                <select id="animal_type_id" name="animal_type_id" required>
                    <option value="">-- Kies een diersoort --</option>
                    <?php foreach ($animal_types as $animal_type): ?>
                        <option value="<?= e($animal_type['id']) ?>" <?= selected($data['animal_type_id'], $animal_type['id']) ?>><?= e($animal_type['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <!-- HEEL GETAL -->
        <tr>
            <th><label for="age">Leeftijd (jaar) *</label></th>
            <td><input type="number" id="age" name="age" min="0" max="40" step="1" required value="<?= e($data['age']) ?>"></td>
        </tr>
        <!-- DROPDOWN MET VASTE WAARDEN -->
        <tr>
            <th><label for="status">Status *</label></th>
            <td>
                <select id="status" name="status">
                    <?php foreach (animal_statuses() as $status): ?>
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
