<?php
// =====================================================================
// book_edit.php?id=5 - UPDATE (alleen admin)
// =====================================================================
require_once 'includes/init.php';
require_admin();

// Check 1: is er een geldig id meegegeven?
$id = get_param('id');
if (!is_valid_id($id)) {
    function_error('Geen geldig boek gekozen.');
    redirect('index.php');
}
$id = (int) $id;

// Check 2: bestaat het boek?
$book = book_find($conn, $id, false);
if ($book === false) {
    redirect('index.php'); // 410 staat al in de flash
} elseif ($book === null) {
    function_error('Boek #' . $id . ' bestaat niet.');
    redirect('index.php');
}

// Formulier vullen met de huidige waarden uit de database
$data = array(
    'title' => $book['title'],
    'author' => $book['author'],
    'category_id' => $book['category_id'],
    'publish_year' => (string) $book['publish_year'],
    'status' => $book['status'],
);

if (is_post()) {
    $data = book_form_data();

    if (book_update($conn, $id, $data)) {
        log_action($conn, current_user_id(), 'book_update', 'Boek #' . $id . ': ' . $data['title']);
        set_flash('success', 'Boek is bijgewerkt.');
        redirect('index.php');
    }
}

$categories = categories_get_all($conn);
if ($categories === false) {
    $categories = array();
}

$page_title = 'Boek wijzigen';
$form_action = 'book_edit.php?id=' . $id;
$button_text = 'Opslaan';

require 'includes/header.php';
?>

<h1>Boek #<?= e($id) ?> wijzigen</h1>
<?php require 'includes/book_form.php'; ?>

<?php require 'includes/footer.php'; ?>
