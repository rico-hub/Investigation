<?php
// =====================================================================
// config/site.php - ALLES wat per app anders is in de header en footer.
// header.php en footer.php zelf zijn universeel (nooit aanpassen).
// Bricks heeft geen login en geen winkelmand: die stukken staan dus "uit".
// =====================================================================

$site_tagline = 'De bouwstenen van Notion pagina 7: uitleg, het echte element en de code eronder.';

$site_logo = '<rect width="7" height="7" x="14" y="3" rx="1"/><path d="M10 21V8a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H3"/>';

$menu = array(
    'index.php' => 'Overzicht',
    'dropdowns.php' => 'Dropdowns',
    'velden.php' => 'Velden',
    'knoppen.php' => 'Knoppen',
    'tonen.php' => 'Netjes tonen',
    'tabel.php' => 'Tabel + filter',
    'opslaan.php' => 'Alles samen: opslaan',
);

// Geen login: leeg account-menu = poppetje wordt niet getoond
$account_menu = array();
$site_user_name = '';
$site_role_label = 'Notion pagina 7';

// Geen winkelmand: $cart_file leeg = knop wordt niet getoond
$cart_file = '';
$cart_title = '';
$cart_icon = '';
$cart_number = 0;
