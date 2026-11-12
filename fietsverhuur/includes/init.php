<?php
// =====================================================================
// includes/init.php - bovenaan ELKE pagina: require_once 'includes/init.php';
// Start de sessie, maakt de database verbinding en laadt alle functies.
// =====================================================================

session_start();

require_once __DIR__ . '/../config/config.php';

// UNIVERSEEL (nooit aanpassen)
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/log.php';
require_once __DIR__ . '/../functions/auth.php';

// AANPASSEN: jouw eigen functie-bestanden. Nieuw bestand in functions/ = hier 1 regel erbij.
require_once __DIR__ . '/../functions/bike_types.php';
require_once __DIR__ . '/../functions/bikes.php';
require_once __DIR__ . '/../functions/cart.php';
require_once __DIR__ . '/../functions/rentals.php';

check_session_timeout();
