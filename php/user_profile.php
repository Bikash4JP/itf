<?php
// /home/it-future/www/itf/php/user_profile.php
require_once __DIR__ . '/user_auth.php';

app_require_login('/php/user_profile.php');

// For now: redirect to kaigo resume form (we will later add auto-fill)
header('Location: /rireki/kaigo/rireki.php?from=mypage', true, 302);
exit;
