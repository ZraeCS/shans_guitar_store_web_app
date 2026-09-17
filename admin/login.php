<?php
/* Not a real page — the login form is now SHARED with customers on
   auth/login.php (the "Admin / Staff" tab opens first). 301 so
   /admin/login.php never 404s. */
require_once __DIR__ . '/../database/config.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . BASE_URL . '/auth/login.php?tab=admin' . ($qs !== '' ? '&' . $qs : ''), true, 301);
exit;
