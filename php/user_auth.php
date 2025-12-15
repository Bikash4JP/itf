<?php
// /home/it-future/www/itf/php/user_auth.php
// Applicant auth helpers (NOT admin)

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function app_logged_in(): bool {
  return !empty($_SESSION['app_user_id']);
}

function app_user_id(): ?int {
  return app_logged_in() ? (int)$_SESSION['app_user_id'] : null;
}

function app_login(int $userId): void {
  $_SESSION['app_user_id'] = (int)$userId;
}

function app_logout(): void {
  unset($_SESSION['app_user_id']);
}

function app_redirect(string $url): void {
  header('Location: ' . $url, true, 302);
  exit;
}

/** Require login; redirect to user_login.php with next= */
function app_require_login(?string $next = null): void {
  if (app_logged_in()) return;
  $next = $next ?? ($_SERVER['REQUEST_URI'] ?? '/');
  $url  = '/php/user_login.php?next=' . urlencode($next);
  app_redirect($url);
}
// Backward compatible alias (if old files call app_redirect_login)
if (!function_exists('app_redirect_login')) {
  function app_redirect_login(string $next = '/'): void {
    if (function_exists('app_require_login')) {
      app_require_login($next);
      return;
    }
    header('Location: /php/user_login.php?next=' . urlencode($next), true, 302);
    exit;
  }
}
