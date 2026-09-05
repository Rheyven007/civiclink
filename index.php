<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    redirect_by_role();
} else {
    require_once __DIR__ . '/landing.php';
}
