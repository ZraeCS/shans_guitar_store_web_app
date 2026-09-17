<?php
/* Admin logout — reachable by URL (the sidebar uses a POST form in admin.php).
   Clears only the admin_* session keys so a customer login survives, exactly
   like the POST logout inside admin.php. */
require_once __DIR__ . '/../database/config.php';

unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
flash('info', "You've been logged out.");
redirect('admin/admin.php');
