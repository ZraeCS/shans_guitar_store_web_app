<?php
/* Not a real page — the admin auth gate (login form + panel) lives in
   admin/admin.php. Kept as a permanent redirect so /auth/admin.php
   (typed URLs, old bookmarks, external links) lands on the admin login
   instead of Apache's 404. Same convention as the root redirect stubs. */
require_once __DIR__ . '/../database/config.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . BASE_URL . '/admin/admin.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
