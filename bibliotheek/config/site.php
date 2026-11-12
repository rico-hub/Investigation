<?php
// =====================================================================
// config/site.php - ALLES wat per app anders is in de header en footer.
// header.php en footer.php zelf zijn universeel (nooit aanpassen).
// Wordt geladen door includes/header.php.
// =====================================================================

// AANPASSEN 1: zin onderaan in de footer
$site_tagline = 'Boeken zoeken, lenen en terugbrengen op één plek.';

// AANPASSEN 2: het icoontje naast de naam (binnenkant van een <svg>, van lucide.dev)
$site_logo = '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>';

// AANPASSEN 3: het menu. 'bestand.php' => 'Tekst in het menu'
// Verschilt per rol, daarom simpele if / else.
$menu = array('index.php' => 'Boeken', 'info.php' => 'Info');
if (is_logged_in()) {
    if (is_admin()) {
        $menu['loans.php'] = 'Alle leningen';
        $menu['book_create.php'] = '+ Boek';
        $menu['book_trash.php'] = 'Prullenbak';
        $menu['logs.php'] = 'Logboek';
    } else {
        $menu['loans.php'] = 'Mijn leningen';
    }
}

// AANPASSEN 4: het account-menu (poppetje rechtsboven + kolom in de footer)
$account_menu = array();
if (is_logged_in()) {
    $account_menu['loans.php'] = 'Mijn leningen';
    $account_menu['logout.php'] = 'Uitloggen';
} else {
    $account_menu['login.php'] = 'Inloggen';
    $account_menu['register.php'] = 'Registreren';
}

// Naam en rol van de ingelogde gebruiker (leeg = niet tonen)
$site_user_name = '';
$site_role_label = '';
if (is_logged_in()) {
    $site_user_name = $_SESSION['name'];
    $site_role_label = $_SESSION['role'];
}

// AANPASSEN 5: winkelmand-knop rechtsboven. $cart_file = '' betekent: geen knop.
// Geen winkelmand in je app? Haal dan het hele if-blok hieronder weg.
$cart_file = '';
$cart_title = 'Winkelmand';
$cart_icon = '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>';
$cart_number = 0;
if (is_logged_in()) {
    $cart_file = 'cart.php';
    $cart_number = cart_count();
}
