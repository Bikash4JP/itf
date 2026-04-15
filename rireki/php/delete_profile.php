<?php
// /home/it-future/www/itf/rireki/php/delete_profile.php

declare(strict_types=1);

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

if (!app_is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'delete_all') {
    header('Location: /rireki/user_data.php', true, 302);
    exit;
}

try {
    $pdo = app_pdo();
    $uid = (int)app_user_id();

    // Delete records from both resume tables
    $st1 = $pdo->prepare("DELETE FROM app_resume_basic WHERE user_id = ?");
    $st1->execute([$uid]);

    $st2 = $pdo->prepare("DELETE FROM app_resume_kaigo WHERE user_id = ?");
    $st2->execute([$uid]);

    // Optional: we don't delete from APP_TBL_USERS, only resume data.
} catch (Throwable $e) {
    // ignore
}

// Redirect back to user_data (will show empty state now)
header('Location: /rireki/user_data.php', true, 302);
exit;
