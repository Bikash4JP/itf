<?php
// /home/it-future/www/itf/php/user_logout.php
require_once __DIR__ . '/user_auth.php';
app_logout();
header('Location: /php/user_login.php', true, 302);
exit;
