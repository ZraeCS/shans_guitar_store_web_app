<?php
/* Not a real page — admin.php IS the auth gate (it renders the login form
   when no admin session exists). 301 so /admin/login.php never 404s. */
require_once __DIR__ . '/../database/config.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . BASE_URL . '/admin/admin.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
