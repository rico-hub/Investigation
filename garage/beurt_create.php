<?php
require_once 'includes/init.php';
require_admin();

$data = beurt_empty_data();

if (is_post()) {
    $data = beurt_form_data(); // ingevulde waarden bewaren (sticky form)

    $new_id = beurt_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'beurt_create', 'Beurt #' . $new_id . ': ' . $data['type_onderhoud'] . ' op ' . $data['datum'] . ' (' . $data['max_autos'] . ' auto\'s)');
        set_flash('success', 'Beurt "' . $data['type_onderhoud'] . '" op ' . format_date($data['datum'], false) . ' is aangemaakt.');
        redirect('index.php');
    }
    // Fout? GEEN redirect: het formulier toont de ingevulde waarden + de foutmelding.
}

$page_title = 'Beurt aanmaken';
require 'includes/header.php';
?>

<h1>Beurt aanmaken</h1>
<p class="lead">Per dag zijn er <?= WERKPLAATS_PLEKKEN ?> plekken. Zijn er op die dag al beurten, dan kun je alleen de plekken gebruiken die nog over zijn.</p>

<form method="post" action="beurt_create.php" class="card form">
    <table class="form-table">
        <tr>
            <th><label for="type_onderhoud">Naam (type onderhoud) *</label></th>
            <td>
                <select id="type_onderhoud" name="type_onderhoud" required>
                    <option value="">-- Kies een type --</option>
                    <?php foreach (beurt_types() as $type): ?>
                        <option value="<?= e($type) ?>" <?= selected($data['type_onderhoud'], $type) ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="datum">Datum *</label></th>
            <td><input type="date" id="datum" name="datum" min="<?= date('Y-m-d') ?>" required value="<?= e($data['datum']) ?>"></td>
        </tr>
        <tr>
            <th><label for="tijd">Tijd *</label></th>
            <td><input type="time" id="tijd" name="tijd" required value="<?= e($data['tijd']) ?>"></td>
        </tr>
        <tr>
            <th><label for="max_autos">Max. auto's *</label></th>
            <td><input type="number" id="max_autos" name="max_autos" min="1" max="<?= WERKPLAATS_PLEKKEN ?>" step="1" required value="<?= e($data['max_autos']) ?>"></td>
        </tr>
        <tr>
            <th></th>
            <td>
                <button type="submit" class="btn">Aanmaken</button>
                <a href="index.php" class="btn btn-grey">Annuleren</a>
            </td>
        </tr>
    </table>
</form>

<?php require 'includes/footer.php'; ?>
