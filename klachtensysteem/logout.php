<?php
// =====================================================================
// logout.php - sessie helemaal wissen (ook winkelmand)
// =====================================================================
require_once 'includes/init.php';

if (is_logged_in()) {
    log_action($conn, current_user_id(), 'logout', '');
}

logout_user();
redirect('login.php?logged_out=1');
