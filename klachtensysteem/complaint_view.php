<?php
// =====================================================================
// complaint_view.php?id=3 - details + reacties (READ van 1 record)
// =====================================================================
require_once 'includes/init.php';
require_login();

$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldige klacht gekozen.');
    redirect('index.php');
}
$id = (int) $id;

$complaint = complaint_find($conn, $id, false);
if ($complaint === false) {
    redirect('index.php');
} elseif ($complaint === null) {
    function_error('Klacht #' . $id . ' bestaat niet.');
    redirect('index.php');
}

// Rechten: een user mag NIET andermans klacht zien door het id in de URL te veranderen
if (!complaint_can_view($complaint, current_user_id(), is_admin())) {
    function_error('Dit is niet jouw klacht.');
    redirect('index.php');
}

// POST: reactie plaatsen
$message = '';
if (is_post()) {
    $message = post_param('message');
    $reaction_id = reaction_add($conn, $id, current_user_id(), is_admin(), $message);
    if ($reaction_id !== false) {
        log_action($conn, current_user_id(), 'reaction_add', 'Reactie op klacht #' . $id);
        set_flash('success', 'Reactie geplaatst.');
        redirect('complaint_view.php?id=' . $id);
    }
}

$reactions = reactions_get($conn, $id);
if ($reactions === false) {
    $reactions = array();
}

$page_title = 'Klacht #' . $id;
require 'includes/header.php';
?>

<div class="card">
    <h1>#<?= e($complaint['id']) ?> <?= e($complaint['title']) ?></h1>

    <table class="form-table">
        <tr><th>Status</th><td><span class="status status-<?= e($complaint['status']) ?>"><?= e(str_replace('_', ' ', $complaint['status'])) ?></span></td></tr>
        <tr><th>Prioriteit</th><td><?= e($complaint['priority']) ?></td></tr>
        <tr><th>Categorie</th><td><?= e($complaint['category_name']) ?></td></tr>
        <tr><th>Ingediend door</th><td><?= e($complaint['user_name']) ?></td></tr>
        <tr>
            <th>Behandelaar</th>
            <td>
                <?php if ($complaint['assigned_name'] === null): ?>
                    <em>nog niet toegewezen</em>
                <?php else: ?>
                    <?= e($complaint['assigned_name']) ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>Ingediend op</th><td><?= e(format_date($complaint['created_at'], true)) ?></td></tr>
        <tr><th>Laatst gewijzigd</th><td><?= e(format_date($complaint['updated_at'], true)) ?></td></tr>
        <tr><th>Beschrijving</th><td><?= nl2br(e($complaint['description'])) ?></td></tr>
    </table>

    <div class="button-row">
        <?php if (complaint_can_edit($complaint, current_user_id(), is_admin())): ?>
            <a href="complaint_edit.php?id=<?= e($id) ?>" class="btn btn-grey">Wijzigen</a>
            <a href="complaint_delete.php?id=<?= e($id) ?>" class="btn btn-red">Verwijderen</a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-grey">Terug</a>
    </div>
</div>

<h2>Reacties (<?= count($reactions) ?>)</h2>

<?php if (count($reactions) === 0): ?>
    <div class="card empty">Nog geen reacties.</div>
<?php else: ?>
    <?php foreach ($reactions as $reaction): ?>
        <div class="card">
            <strong><?= e($reaction['user_name']) ?></strong>
            <?php if ($reaction['role'] === 'admin'): ?>
                <span class="status status-in_behandeling">medewerker</span>
            <?php endif; ?>
            <span class="hint"><?= e(format_date($reaction['created_at'], true)) ?></span>
            <p><?= nl2br(e($reaction['message'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($complaint['status'] !== 'gesloten'): ?>
    <form method="post" action="complaint_view.php?id=<?= e($id) ?>" class="card">
        <label for="message"><strong>Reageren</strong></label>
        <textarea id="message" name="message" maxlength="2000" required><?= e($message) ?></textarea>
        <p><button type="submit" class="btn">Plaats reactie</button></p>
    </form>
<?php else: ?>
    <p class="hint">Deze klacht is gesloten.</p>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
