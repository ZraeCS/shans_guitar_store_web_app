<?php
/* Moved during the 2026-09-16 reorganization - permanent redirect for old bookmarks. */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$qs = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ' . $base . '/auth/login.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
exit;