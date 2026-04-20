<?php
// /home/it-future/www/itf/rireki/php/delete_profile.php
// Deletes all data for the logged-in user and redirects to logout.
declare(strict_types=1);

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

if (!app_is_logged_in()) {
  header('Location: /php/user_login.php', true, 302);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'delete_all') {
  header('Location: /rireki/kaigo/php/rireki_preview.php', true, 302);
  exit;
}

$uid = (int) app_user_id();
app_ensure_tables($pdo);

try {
  // Delete kaigo resume drafts
  $pdo->prepare("DELETE FROM app_resume_kaigo WHERE user_id = ?")->execute([$uid]);

  // Delete saved profile snapshots
  $pdo->prepare("DELETE FROM app_profiles WHERE user_id = ?")->execute([$uid]);

  // Delete application history
  $pdo->prepare("DELETE FROM app_applications WHERE user_id = ?")->execute([$uid]);

  // Delete resume tokens
  $pdo->prepare("DELETE FROM app_resumes WHERE user_id = ?")->execute([$uid]);

  // Delete user account itself
  $pdo->prepare("DELETE FROM app_users WHERE id = ?")->execute([$uid]);

} catch (Throwable $e) {
  error_log('[delete_profile] ' . $e->getMessage());
}

// Clear session and redirect to login
session_unset();
session_destroy();
setcookie(session_name(), '', ['expires' => 1, 'path' => '/', 'domain' => '.it-future.jp', 'secure' => true, 'httponly' => true]);

header('Location: /php/user_login.php?deleted=1', true, 302);
exit;
