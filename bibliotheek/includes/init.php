<?php
// =====================================================================
// includes/init.php - bovenaan ELKE pagina: require_once 'includes/init.php';
// Start de sessie, maakt de database verbinding en laadt alle functies.
// =====================================================================

session_start();

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/log.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/categories.php';
require_once __DIR__ . '/../functions/books.php';
require_once __DIR__ . '/../functions/loans.php';
require_once __DIR__ . '/../functions/cart.php';

check_session_timeout();
