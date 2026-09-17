<?php
/* Folder entry point — so /admin/ (no file name) opens the admin auth gate
   instead of Apache's bare directory listing. admin.php stays the real page. */
require_once __DIR__ . '/../database/config.php';

$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . BASE_URL . '/admin/admin.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;
