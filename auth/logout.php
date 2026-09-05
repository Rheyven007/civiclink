<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) {
    log_audit($conn, current_user_id(), 'Logout', 'User logged out');
}
session_destroy();
header('Location: /auth/login.php');
exit;
