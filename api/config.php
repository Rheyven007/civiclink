<?php
// CivicLink — database connection settings (edit for your server / XAMPP)
define('DB_HOST', getenv('CIVIC_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('CIVIC_DB_PORT') ?: '3306');
define('DB_USER', getenv('CIVIC_DB_USER') ?: 'root');
define('DB_PASS', getenv('CIVIC_DB_PASS') ?: '');
define('DB_NAME', getenv('CIVIC_DB_NAME') ?: 'civiclink');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');   // must be writable
define('UPLOAD_URL', 'uploads/');                 // relative to index.html
define('MAX_UPLOAD_MB', 5);

// Quick sign-in buttons on the login page (one active account per role, read from the users table).
// Useful for testing and project defense. Set to false before going live.
define('QUICK_LOGIN', getenv('CIVIC_QUICK_LOGIN') !== '0');
define('QUICK_LOGIN_PASSWORD', 'password');   // the shared password of the sample accounts in 
