<?php
/* Moved during the 2026-09-16 reorganization - permanent redirect for old bookmarks. */
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
header('Location: ' . $base . '/customer/account.php', true, 301);
exit;