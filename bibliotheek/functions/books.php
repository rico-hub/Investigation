<?php
// =====================================================================
// functions/books.php - CRUD voor boeken + filters/zoeken/sorteren
// =====================================================================

// Toegestane statussen (moet gelijk zijn aan de ENUM in database.sql)
function book_statuses()
{
    return array('beschikbaar', 'uitgeleend', 'reparatie');
}

// ---------------------------------------------------------------------
// READ - lijst met filters
// ---------------------------------------------------------------------

// Alle filters uit de URL lezen. Lege string = filter staat uit.
function books_read_filters()
{
    return array(
        'search'      => get_param('search'),       // searchbar
        'status'      => get_param('status'),       // statusfilter (dropdown)
        'category_id' => get_param('category_id'),  // dropdown
        'f_id'        => get_param('f_id'),         // kolomfilters
        'f_title'     => get_param('f_title'),
        'f_author'    => get_param('f_author'),
        'f_year'      => get_param('f_year'),
        'sort'        => get_param('sort'),         // sorteren op kolom
        'dir'         => get_param('dir'),
    );
}

function books_get_all($conn, $filters)
{
    $sql = 'SELECT b.id, b.title, b.author, b.publish_year, b.status, c.name AS category_name
            FROM books b
            INNER JOIN categories c ON c.id = b.category_id
            WHERE b.deleted_at IS NULL';
    $types = '';
    $params = array();

    // Searchbar: zoekt in meerdere kolommen tegelijk
    if ($filters['search'] !== '') {
        $sql .= ' AND (b.title LIKE ? OR b.author LIKE ? OR c.name LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // StatusFilter: alleen als het een toegestane status is
    if ($filters['status'] !== '' && in_array($filters['status'], book_statuses(), true)) {
        $sql .= ' AND b.status = ?';
        $types .= 's';
        $params[] = $filters['status'];
    }

    // Categorie dropdown
    if (is_valid_id($filters['category_id'])) {
        $sql .= ' AND b.category_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['category_id'];
    }

    // Kolomfilters (de invulvakjes onder de kolomkoppen)
    if (is_valid_id($filters['f_id'])) {
        $sql .= ' AND b.id = ?';
        $types .= 'i';
        $params[] = (int) $filters['f_id'];
    }
    if ($filters['f_title'] !== '') {
        $sql .= ' AND b.title LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_title'] . '%';
    }
    if ($filters['f_author'] !== '') {
        $sql .= ' AND b.author LIKE ?';
        $types .= 's';
        $params[] = '%' . $filters['f_author'] . '%';
    }
    if ($filters['f_year'] !== '' && ctype_digit($filters['f_year'])) {
        $sql .= ' AND b.publish_year = ?';
        $types .= 'i';
        $params[] = (int) $filters['f_year'];
    }

    // Sorteren: kolomnamen kun je NIET met ? binden, dus een whitelist gebruiken
    $columns = array(
        'id'       => 'b.id',
        'title'    => 'b.title',
        'author'   => 'b.author',
        'year'     => 'b.publish_year',
        'category' => 'c.name',
        'status'   => 'b.status',
    );
    $order = 'b.id';
    if (isset($columns[$filters['sort']])) {
        $order = $columns[$filters['sort']];
    }
    $direction = 'ASC';
    if ($filters['dir'] === 'desc') {
        $direction = 'DESC';
    }
    $sql .= ' ORDER BY ' . $order . ' ' . $direction;

    return db_select_all($conn, $sql, $types, $params);
}

// TotalCount: alle (niet verwijderde) boeken, zonder filters
function books_count_all($conn)
{
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM books WHERE deleted_at IS NULL', '', array());
    if ($row === false || $row === null) {
        return 0;
    }
    return (int) $row['total'];
}

// 1 boek ophalen. $include_deleted = true om ook de prullenbak te doorzoeken.
// Geeft array, null (niet gevonden) of false (db fout).
function book_find($conn, $id, $include_deleted)
{
    $sql = 'SELECT id, category_id, title, author, publish_year, status, deleted_at FROM books WHERE id = ?';
    if (!$include_deleted) {
        $sql .= ' AND deleted_at IS NULL';
    }
    return db_select_one($conn, $sql, 'i', array($id));
}

// Prullenbak (soft deleted boeken)
function books_get_deleted($conn)
{
    return db_select_all(
        $conn,
        'SELECT b.id, b.title, b.author, b.deleted_at, c.name AS category_name
         FROM books b
         INNER JOIN categories c ON c.id = b.category_id
         WHERE b.deleted_at IS NOT NULL
         ORDER BY b.deleted_at DESC',
        '',
        array()
    );
}

// ---------------------------------------------------------------------
// Formulier data
// ---------------------------------------------------------------------

// Lege waarden voor een nieuw formulier
function book_empty_data()
{
    return array(
        'title' => '',
        'author' => '',
        'category_id' => '',
        'publish_year' => '',
        'status' => 'beschikbaar',
    );
}

// Waarden uit het verzonden formulier
function book_form_data()
{
    return array(
        'title' => post_param('title'),
        'author' => post_param('author'),
        'category_id' => post_param('category_id'),
        'publish_year' => post_param('publish_year'),
        'status' => post_param('status'),
    );
}

// Alle checks voor een boek (Error-code 420). true = goed.
function book_validate($conn, $data)
{
    if ($data['title'] === '') {
        return function_error('Titel is verplicht.');
    }
    if (mb_strlen($data['title']) > 200) {
        return function_error('Titel mag maximaal 200 tekens zijn.');
    }
    if ($data['author'] === '') {
        return function_error('Auteur is verplicht.');
    }
    if (!is_valid_id($data['category_id'])) {
        return function_error('Kies een categorie.');
    }
    if (!category_exists($conn, (int) $data['category_id'])) {
        return function_error('Deze categorie bestaat niet.');
    }
    if ($data['publish_year'] !== '') {
        if (!ctype_digit($data['publish_year'])) {
            return function_error('Jaar moet een getal zijn.');
        } elseif ((int) $data['publish_year'] < 1450 || (int) $data['publish_year'] > 2100) {
            return function_error('Jaar moet tussen 1450 en 2100 liggen.');
        }
    }
    if (!in_array($data['status'], book_statuses(), true)) {
        return function_error('Ongeldige status.');
    }
    return true;
}

// Leeg jaar -> NULL in de database
function book_year_or_null($year)
{
    if ($year === '') {
        return null;
    }
    return (int) $year;
}

// ---------------------------------------------------------------------
// CREATE - geeft nieuw id of false
// ---------------------------------------------------------------------
function book_create($conn, $data)
{
    if (!book_validate($conn, $data)) {
        return false;
    }

    return db_insert(
        $conn,
        'INSERT INTO books (category_id, title, author, publish_year, status) VALUES (?, ?, ?, ?, ?)',
        'issis',
        array((int) $data['category_id'], $data['title'], $data['author'], book_year_or_null($data['publish_year']), $data['status'])
    );
}

// ---------------------------------------------------------------------
// UPDATE - geeft true of false
// ---------------------------------------------------------------------
function book_update($conn, $id, $data)
{
    $book = book_find($conn, $id, false);
    if ($book === false) {
        return false;
    }
    if ($book === null) {
        return function_error('Boek niet gevonden.');
    }

    if (!book_validate($conn, $data)) {
        return false;
    }

    // Een uitgeleend boek krijgt pas een andere status als het is teruggebracht
    if ($book['status'] === 'uitgeleend' && $data['status'] !== 'uitgeleend') {
        return function_error('Dit boek is uitgeleend. Registreer eerst de terugname bij Leningen.');
    }
    if ($book['status'] !== 'uitgeleend' && $data['status'] === 'uitgeleend') {
        return function_error('Status "uitgeleend" wordt automatisch gezet bij het lenen.');
    }

    $result = db_change(
        $conn,
        'UPDATE books SET category_id = ?, title = ?, author = ?, publish_year = ?, status = ? WHERE id = ?',
        'issisi',
        array((int) $data['category_id'], $data['title'], $data['author'], book_year_or_null($data['publish_year']), $data['status'], $id)
    );

    if ($result === false) {
        return false;
    }
    return true; // ook true als er niets veranderd is (affected_rows = 0)
}

// ---------------------------------------------------------------------
// DELETE
// ---------------------------------------------------------------------

// SOFT delete: zet deleted_at. Het boek blijft in de database (geschiedenis blijft kloppen).
function book_soft_delete($conn, $id)
{
    $book = book_find($conn, $id, false);
    if ($book === false) {
        return false;
    }
    if ($book === null) {
        return function_error('Boek niet gevonden of al verwijderd.');
    }
    if ($book['status'] === 'uitgeleend') {
        return function_error('Dit boek is nog uitgeleend en kan niet verwijderd worden.');
    }

    $result = db_change($conn, 'UPDATE books SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    return true;
}

// Terugzetten uit de prullenbak
function book_restore($conn, $id)
{
    $result = db_change($conn, 'UPDATE books SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL', 'i', array($id));
    if ($result === false) {
        return false;
    }
    if ($result === 0) {
        return function_error('Boek staat niet in de prullenbak.');
    }
    return true;
}

// HARD delete: rij echt weg. Kan alleen als er GEEN leningen aan het boek hangen (FK RESTRICT).
// Hier uitgeschreven ZONDER helper, zodat je ziet hoe je de foreign key fout (1451) afvangt.
function book_hard_delete($conn, $id)
{
    // Check 1 (420): staat het boek in de prullenbak? (eerst soft delete, dan pas hard)
    $book = book_find($conn, $id, true);
    if ($book === false) {
        return false;
    }
    if ($book === null) {
        return function_error('Boek niet gevonden.');
    }
    if ($book['deleted_at'] === null) {
        return function_error('Zet het boek eerst in de prullenbak.');
    }

    // Check 2 (420): zijn er leningen? Dan mag het niet (geschiedenis).
    $row = db_select_one($conn, 'SELECT COUNT(*) AS total FROM loans WHERE book_id = ?', 'i', array($id));
    if ($row === false) {
        return false;
    }
    if ((int) $row['total'] > 0) {
        return function_error('Dit boek heeft ' . $row['total'] . ' lening(en) in de geschiedenis en kan niet definitief verwijderd worden.');
    }

    // De DELETE zelf, lange versie
    $stmt = $conn->prepare('DELETE FROM books WHERE id = ?');
    if (!$stmt) {
        return database_error('Verwijderen voorbereiden mislukt', $conn->error);
    }
    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        $errno = $stmt->errno;
        $error = $stmt->error;
        $stmt->close();

        // 1451 = "Cannot delete or update a parent row: a foreign key constraint fails"
        if ($errno === 1451) {
            return database_error('Kan niet verwijderen: er hangen nog gegevens aan dit boek', $error);
        }
        return database_error('Verwijderen mislukt', $error);
    }

    $stmt->close();
    return true;
}
