<?php
// =====================================================================
// config/site.php - ALLES wat per app anders is in de header en footer.
// header.php en footer.php zelf zijn universeel (nooit aanpassen).
// Wordt geladen door includes/header.php.
// =====================================================================

// AANPASSEN 1: zin onderaan in de footer
$site_tagline = 'Geef een dier een nieuw thuis.';

// AANPASSEN 2: het icoontje naast de naam (binnenkant van een <svg>, van lucide.dev)
$site_logo = '<rect width="7" height="7" x="14" y="3" rx="1"/><path d="M10 21V8a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H3"/>';

// AANPASSEN 3: het menu. 'bestand.php' => 'Tekst in het menu'
// Verschilt per rol, daarom simpele if / else.
$menu = array('index.php' => 'Dieren');
if (is_logged_in()) {
    $menu['appointments.php'] = 'Mijn afspraken';
    if (is_admin()) {
        $menu['animal_create.php'] = '+ Dier';
        $menu['animal_trash.php'] = 'Prullenbak';
        $menu['logs.php'] = 'Logboek';
    }
}

// AANPASSEN 4: het account-menu (poppetje rechtsboven + kolom in de footer)
$account_menu = array();
if (is_logged_in()) {
    $account_menu['appointments.php'] = 'Mijn afspraken';
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
$cart_title = 'Bezoeklijst';
$cart_icon = '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>';
$cart_number = 0;
if (is_logged_in()) {
    $cart_file = 'cart.php';
    $cart_number = cart_count();
}
