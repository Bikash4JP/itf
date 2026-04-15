<?php
// /home/it-future/www/itf/rireki/php/api_user_status.php
declare(strict_types=1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (app_is_logged_in()) {
    try {
        $pdo = app_pdo();
        $user = app_current_user($pdo);
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'username' => mb_strtoupper(mb_substr($user['username'], 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($user['username'], 1, null, 'UTF-8')
            ]);
            exit;
        }
    } catch (Throwable $e) {}
}

echo json_encode(['logged_in' => false]);
