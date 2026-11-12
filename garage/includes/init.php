<?php
session_start();

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../functions/database.php';
require_once __DIR__ . '/../functions/log.php';
require_once __DIR__ . '/../functions/auth.php';

require_once __DIR__ . '/../functions/klanten.php';
require_once __DIR__ . '/../functions/beurten.php';
require_once __DIR__ . '/../functions/afspraken.php';

check_session_timeout();
