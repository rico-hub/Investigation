<?php
require_once 'includes/init.php';
require_admin();

$data = afspraak_empty_data();
$data['beurt_id'] = get_param('beurt_id');

if (is_post()) {
    $data = afspraak_form_data();

    $new_id = afspraak_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'afspraak_create', 'Afspraak #' . $new_id . ': ' . $data['kenteken'] . ' bij beurt #' . $data['beurt_id']);
        set_flash('success', 'Afspraak voor ' . $data['kenteken'] . ' is ingepland.');
        redirect('index.php');
    }
}

$klanten = klanten_get_all($conn, '');
if ($klanten === false) {
    $klanten = array();
}

$beurten = beurten_met_plek($conn);
if ($beurten === false) {
    $beurten = array();
}

$page_title = 'Afspraak maken';
require 'includes/header.php';
?>

<h1>Afspraak maken</h1>
<p class="lead">In de lijst staan alleen komende beurten waar nog plek is. Nieuwe klant? <a href="klanten.php">Voeg die eerst toe</a>.</p>

<?php if (count($beurten) === 0): ?>
    <div class="card">
        <p>Er zijn geen komende beurten met vrije plekken. <a href="beurt_create.php">Maak eerst een beurt aan</a>.</p>
    </div>
<?php else: ?>
    <form method="post" action="afspraak_create.php" class="card form">
        <table class="form-table">
            <tr>
                <th><label for="beurt_id">Beurt *</label></th>
                <td>
                    <select id="beurt_id" name="beurt_id" required>
                        <option value="">-- Kies een beurt --</option>
                        <?php foreach ($beurten as $beurt): ?>
                            <option value="<?= e($beurt['id']) ?>" <?= selected($data['beurt_id'], $beurt['id']) ?>>
                                <?= e(format_date($beurt['datum'], false)) ?> <?= e(substr($beurt['tijd'], 0, 5)) ?> - <?= e($beurt['type_onderhoud']) ?>
                                (nog <?= e((int) $beurt['max_autos'] - (int) $beurt['geboekt']) ?> plek(ken))
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="klant_id">Klant *</label></th>
                <td>
                    <select id="klant_id" name="klant_id" required>
                        <option value="">-- Kies een klant --</option>
                        <?php foreach ($klanten as $klant): ?>
                            <option value="<?= e($klant['id']) ?>" <?= selected($data['klant_id'], $klant['id']) ?>><?= e($klant['naam']) ?> (<?= e($klant['telefoon']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="kenteken">Kenteken *</label></th>
                <td>
                    <input type="text" id="kenteken" name="kenteken" maxlength="8" placeholder="AB-123-C" required value="<?= e($data['kenteken']) ?>">
                    <p class="hint">8 tekens met 2 streepjes, bv. AB-123-C of 12-ABC-3</p>
                </td>
            </tr>
            <tr>
                <th></th>
                <td>
                    <button type="submit" class="btn">Inplannen</button>
                    <a href="index.php" class="btn btn-grey">Annuleren</a>
                </td>
            </tr>
        </table>
    </form>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
