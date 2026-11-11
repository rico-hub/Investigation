<?php
// includes/init.php - bovenaan ELKE pagina
session_start();

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/log.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/categories.php';
require_once __DIR__ . '/../functions/complaints.php';
require_once __DIR__ . '/../functions/cart.php';

check_session_timeout();
