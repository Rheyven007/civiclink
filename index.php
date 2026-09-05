<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    redirect_by_role();
} else {
    header('Location: /auth/login.php');
    exit;
}
