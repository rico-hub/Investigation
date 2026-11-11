<?php
// =====================================================================
// complaint_edit.php?id=3 - UPDATE
//   user  = eigen klacht, alleen zolang status 'open'
//   admin = alles, ook status en behandelaar
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

// Rechten check VOORDAT we het formulier tonen
if (!complaint_can_edit($complaint, current_user_id(), is_admin())) {
    function_error('Je mag deze klacht niet (meer) wijzigen.');
    redirect('complaint_view.php?id=' . $id);
}

$data = array(
    'title' => $complaint['title'],
    'description' => $complaint['description'],
    'category_id' => $complaint['category_id'],
    'priority' => $complaint['priority'],
    'status' => $complaint['status'],
    'assigned_to' => (string) $complaint['assigned_to'],
);

if (is_post()) {
    $data = complaint_form_data();

    if (complaint_update($conn, $id, $data, current_user_id(), is_admin())) {
        log_action($conn, current_user_id(), 'complaint_update', 'Klacht #' . $id);
        set_flash('success', 'Klacht is bijgewerkt.');
        redirect('complaint_view.php?id=' . $id);
    }
}

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}
$admins = admins_get_all($conn);
if ($admins === false) {
    $admins = array();
}

$page_title = 'Klacht wijzigen';
$form_action = 'complaint_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Klacht #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/complaint_form.php'; ?>

<?php require 'includes/footer.php'; ?>
