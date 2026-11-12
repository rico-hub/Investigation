<?php
function klanten_get_all($conn, $search)
{
    $sql = 'SELECT k.id, k.naam, k.telefoon, k.email, COUNT(a.id) AS aantal_afspraken
            FROM klanten k
            LEFT JOIN afspraken a ON a.klant_id = k.id
            WHERE 1 = 1';
    $types = '';
    $params = array();

    if ($search !== '') {
        $sql .= ' AND (k.naam LIKE ? OR k.telefoon LIKE ? OR k.email LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' GROUP BY k.id ORDER BY k.naam';

    return db_select_all($conn, $sql, $types, $params);
}

// 1 klant. Geeft array, null (niet gevonden) of false (db fout).
function klant_find($conn, $id)
{
    return db_select_one($conn, 'SELECT id, naam, telefoon, email FROM klanten WHERE id = ?', 'i', array($id));
}

function klant_empty_data()
{
    return array('naam' => '', 'telefoon' => '', 'email' => '');
}

function klant_form_data()
{
    return array(
        'naam'     => post_param('naam'),
        'telefoon' => post_param('telefoon'),
        'email'    => post_param('email'),
    );
}

function klant_create($conn, $data)
{
    if ($data['naam'] === '' || $data['telefoon'] === '') {
        return function_error('Naam en telefoon zijn verplicht.');
    }
    if (strlen($data['naam']) > 100) {
        return function_error('Naam mag maximaal 100 tekens zijn.');
    }
    if (!preg_match('/^[0-9 +-]{6,20}$/', $data['telefoon'])) {
        return function_error('Telefoon mag alleen cijfers, spaties, + en - bevatten (6 tot 20 tekens).');
    }

    // E-mail mag leeg blijven -> dan NULL in de database
    $email = null;
    if ($data['email'] !== '') {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return function_error('Dit is geen geldig e-mailadres.');
        }
        $email = $data['email'];
    }

    return db_insert(
        $conn,
        'INSERT INTO klanten (naam, telefoon, email) VALUES (?, ?, ?)',
        'sss',
        array($data['naam'], $data['telefoon'], $email)
    );
}
