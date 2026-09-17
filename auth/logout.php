<?php
require_once __DIR__ . '/../database/config.php';

/* COMPLETE logout: wipes customer AND admin session data plus the session
   cookie in one go (see logout_everything() in database/config.php). */
logout_everything();
redirect('index.php');

