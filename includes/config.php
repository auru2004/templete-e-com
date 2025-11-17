<?php
// KONFIGURASI APLIKASI

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'lumbungdigital_db');

// Site Configuration
define('SITE_NAME', 'Lumbung Digital');
define('SITE_URL', 'http://localhost/lumbungdigital/');
define('ADMIN_EMAIL', 'admin@lumbungdigital.com');

// Currency
define('CURRENCY', 'Rp');
define('CURRENCY_SYMBOL', 'Rp');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
