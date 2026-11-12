<?php
// =====================================================================
// config/site.php - ALLES wat per app anders is in de header en footer.
// header.php en footer.php zelf zijn universeel (nooit aanpassen).
// Wordt geladen door includes/header.php.
// =====================================================================

// AANPASSEN 1: zin onderaan in de footer
$site_tagline = 'Cijfers invoeren, bekijken en rapporten maken op één plek.';

// AANPASSEN 2: het icoontje naast de naam (binnenkant van een <svg>, van lucide.dev)
$site_logo = '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>';

// AANPASSEN 3: het menu. 'bestand.php' => 'Tekst in het menu'
// Verschilt per rol, daarom simpele if / else.
$menu = array();
if (is_logged_in()) {
    if (is_admin()) {
        $menu['index.php'] = 'Cijfers';
        $menu['students.php'] = 'Leerlingen';
        $menu['grade_create.php'] = '+ Cijfer';
        $menu['report.php'] = 'Rapporten';
        $menu['logs.php'] = 'Logboek';
    } else {
        $menu['index.php'] = 'Mijn cijfers';
        $menu['report.php'] = 'Mijn rapport';
    }
} else {
    $menu['login.php'] = 'Inloggen';
    $menu['register.php'] = 'Registreren';
}

// AANPASSEN 4: het account-menu (poppetje rechtsboven + kolom in de footer)
$account_menu = array();
if (is_logged_in()) {
    $account_menu['report.php'] = 'Rapport';
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
$cart_title = 'Selectie';
$cart_icon = '<rect width="8" height="4" x="8" y="2" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>';
$cart_number = 0;
if (is_admin()) {
    $cart_file = 'cart.php';
    $cart_number = cart_count();
}
