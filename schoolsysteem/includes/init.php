<?php
// includes/init.php - bovenaan ELKE pagina
session_start();

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/log.php';
require_once __DIR__ . '/../functions/auth.php';
require_once __DIR__ . '/../functions/school.php';
require_once __DIR__ . '/../functions/grades.php';
require_once __DIR__ . '/../functions/cart.php';

check_session_timeout();
