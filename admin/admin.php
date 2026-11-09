<?php
/* The old PHP admin panel was replaced by the Symfony admin panel (Nov 2026).
   It works on the same database and lives at ADMIN_PANEL_URL.
   This file stays as a redirect so old bookmarks keep working. */
require_once __DIR__ . '/../database/config.php';

header('Location: ' . ADMIN_PANEL_URL, true, 302);
exit;
