<?php
require_once 'includes/init.php';
require_login();

$data = klant_empty_data();

if (is_post()) {
    require_admin();
    $data = klant_form_data();

    $new_id = klant_create($conn, $data);
    if ($new_id !== false) {
        log_action($conn, current_user_id(), 'klant_create', 'Klant #' . $new_id . ': ' . $data['naam']);
        set_flash('success', 'Klant "' . $data['naam'] . '" is toegevoegd.');
        redirect('klanten.php');
    }
}

$search = get_param('search');
$klanten = klanten_get_all($conn, $search);
if ($klanten === false) {
    $klanten = array();
}

$page_title = 'Klanten';
require 'includes/header.php';
?>

<h1>Klanten</h1>

<?php if (is_admin()): ?>
    <form method="post" action="klanten.php" class="card form">
        <h2>Klant toevoegen</h2>
        <table class="form-table">
            <tr>
                <th><label for="naam">Naam *</label></th>
                <td><input type="text" id="naam" name="naam" maxlength="100" required value="<?= e($data['naam']) ?>"></td>
            </tr>
            <tr>
                <th><label for="telefoon">Telefoon *</label></th>
                <td><input type="tel" id="telefoon" name="telefoon" maxlength="20" required value="<?= e($data['telefoon']) ?>"></td>
            </tr>
            <tr>
                <th><label for="email">E-mail</label></th>
                <td><input type="email" id="email" name="email" maxlength="150" value="<?= e($data['email']) ?>"></td>
            </tr>
            <tr>
                <th></th>
                <td><button type="submit" class="btn">Toevoegen</button></td>
            </tr>
        </table>
    </form>
<?php endif; ?>

<form method="get" action="klanten.php" class="card filter-bar">
    <input type="text" name="search" placeholder="Zoek op naam, telefoon of e-mail..." value="<?= e($search) ?>">
    <button type="submit" class="btn">Zoeken</button>
    <a href="klanten.php" class="btn btn-grey">Filters wissen</a>
</form>

<p class="count"><strong><?= count($klanten) ?></strong> klanten gevonden</p>

<table class="table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th>Naam</th>
            <th>Telefoon</th>
            <th>E-mail</th>
            <th>Afspraken</th>
            <th>Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($klanten) === 0): ?>
            <tr><td colspan="6" class="empty">Geen klanten gevonden.</td></tr>
        <?php else: ?>
            <?php foreach ($klanten as $klant): ?>
                <tr>
                    <td class="col-id"><?= e($klant['id']) ?></td>
                    <td><?= e($klant['naam']) ?></td>
                    <td><?= e($klant['telefoon']) ?></td>
                    <td>
                        <?php if ($klant['email'] === null): ?>
                            <em>-</em>
                        <?php else: ?>
                            <?= e($klant['email']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($klant['aantal_afspraken']) ?></td>
                    <td class="actions">
                        <?php if ((int) $klant['aantal_afspraken'] > 0): ?>
                            <a href="afspraken.php?search=<?= e(urlencode($klant['naam'])) ?>" class="btn btn-small btn-grey">Afspraken</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'includes/footer.php'; ?>
