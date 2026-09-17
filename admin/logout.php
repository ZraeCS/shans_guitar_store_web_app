<?php
/* Admin logout — a COMPLETE logout (customer + admin session data and the
   session cookie are all wiped), then back to the shared login page.
   The sidebar button in admin.php calls the same logout_everything(). */
require_once __DIR__ . '/../database/config.php';

logout_everything();
redirect('auth/login.php?tab=admin');
