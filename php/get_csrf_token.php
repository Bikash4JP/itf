<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// フォーム別に別セッションキーを使用（管理者CSRFと混在しない）
$form = preg_replace('/[^a-z_]/', '', strtolower($_GET['form'] ?? 'default'));
$key  = 'csrf_token_' . $form;

if (empty($_SESSION[$key])) {
    $_SESSION[$key] = bin2hex(random_bytes(32));
}

// 後方互換: 既存コードが csrf_token を参照している場合も対応
if ($form === 'default') {
    $_SESSION['csrf_token'] = $_SESSION[$key];
}

echo json_encode([
    'token'      => $_SESSION[$key],
    'csrf_token' => $_SESSION[$key],  // 後方互換
]);