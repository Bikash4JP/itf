<?php
// /home/it-future/www/itf/php/user_auth.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db_connect.php';

// ---------- config ----------
const APP_TBL_USERS        = 'app_users';
const APP_TBL_PROFILES     = 'app_profiles';
const APP_TBL_RESUMES      = 'app_resumes';
const APP_TBL_APPLICATIONS = 'app_applications';

function app_pdo(): PDO {
  global $pdo;
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('PDO not available. Check db_connect.php');
  }
  return $pdo;
}

function app_ensure_tables(PDO $pdo): void {
  // users
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ".APP_TBL_USERS." (
      id INT AUTO_INCREMENT PRIMARY KEY,
      username VARCHAR(60) NOT NULL UNIQUE,
      email VARCHAR(190) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  // profiles (per format)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ".APP_TBL_PROFILES." (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      fmt VARCHAR(30) NOT NULL DEFAULT 'kaigo',
      data_json LONGTEXT NOT NULL,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_user_fmt (user_id, fmt),
      CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES ".APP_TBL_USERS."(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  // resumes (token based)
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ".APP_TBL_RESUMES." (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      fmt VARCHAR(30) NOT NULL DEFAULT 'kaigo',
      token CHAR(32) NOT NULL,
      job_id INT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_user_token (user_id, token),
      KEY idx_job (job_id),
      CONSTRAINT fk_resume_user FOREIGN KEY (user_id) REFERENCES ".APP_TBL_USERS."(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  // applications
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ".APP_TBL_APPLICATIONS." (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      job_id INT NOT NULL,
      resume_token CHAR(32) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_apply (user_id, job_id, resume_token),
      KEY idx_user_job (user_id, job_id),
      CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES ".APP_TBL_USERS."(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");
}

function app_is_logged_in(): bool {
  return !empty($_SESSION['app_user_id']);
}

function app_user_id(): int {
  return (int)($_SESSION['app_user_id'] ?? 0);
}

function app_current_user(PDO $pdo): ?array {
  if (!app_is_logged_in()) return null;
  $id = app_user_id();
  if ($id <= 0) return null;

  $stmt = $pdo->prepare("SELECT id, username, email FROM ".APP_TBL_USERS." WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
  $u = $stmt->fetch(PDO::FETCH_ASSOC);
  return $u ?: null;
}

function app_login_user_id(int $id): void {
  $_SESSION['app_user_id'] = $id;
}

function app_logout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"],
      $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}

function app_login_url(string $next = ''): string {
  $base = '/php/user_login.php';
  if ($next !== '') return $base . '?next=' . urlencode($next);
  return $base;
}

// ✅ this fixes your “Call to unknown function app_redirect_login”
function app_redirect_login(string $next = ''): void {
  $dest = app_login_url($next !== '' ? $next : ($_SERVER['REQUEST_URI'] ?? '/saiyou.php'));
  header('Location: ' . $dest, true, 302);
  exit;
}

function app_require_login(string $next = ''): void {
  if (!app_is_logged_in()) app_redirect_login($next);
}

// profile helpers
function app_load_profile(PDO $pdo, int $userId, string $fmt = 'kaigo'): ?array {
  $stmt = $pdo->prepare("SELECT data_json FROM ".APP_TBL_PROFILES." WHERE user_id = ? AND fmt = ? LIMIT 1");
  $stmt->execute([$userId, $fmt]);
  $json = $stmt->fetchColumn();
  if (!is_string($json) || $json === '') return null;
  $arr = json_decode($json, true);
  return is_array($arr) ? $arr : null;
}

function app_save_profile(PDO $pdo, int $userId, array $data, string $fmt = 'kaigo'): void {
  $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $stmt = $pdo->prepare("
    INSERT INTO ".APP_TBL_PROFILES." (user_id, fmt, data_json)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE data_json = VALUES(data_json)
  ");
  $stmt->execute([$userId, $fmt, $json]);
}

function app_save_resume(PDO $pdo, int $userId, string $token, ?int $jobId, string $fmt = 'kaigo'): void {
  if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    throw new InvalidArgumentException('Invalid token');
  }
  $stmt = $pdo->prepare("
    INSERT INTO ".APP_TBL_RESUMES." (user_id, fmt, token, job_id)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE job_id = VALUES(job_id)
  ");
  $stmt->execute([$userId, $fmt, $token, $jobId]);
}

function app_save_application(PDO $pdo, int $userId, int $jobId, string $token): void {
  if ($jobId <= 0) return;
  if (!preg_match('/^[a-f0-9]{32}$/', $token)) return;

  $stmt = $pdo->prepare("
    INSERT IGNORE INTO ".APP_TBL_APPLICATIONS." (user_id, job_id, resume_token)
    VALUES (?, ?, ?)
  ");
  $stmt->execute([$userId, $jobId, $token]);
}
