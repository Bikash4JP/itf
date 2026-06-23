<?php
// php/manage_staff.php — Admin-only staff account management
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();
date_default_timezone_set('Asia/Tokyo');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/db_connect.php';

// ─── Schema migration: add missing columns silently ───────────────────────────
foreach ([
  "ALTER TABLE staff ADD COLUMN full_name  VARCHAR(100) NOT NULL DEFAULT ''",
  "ALTER TABLE staff ADD COLUMN email      VARCHAR(150) NOT NULL DEFAULT ''",
  "ALTER TABLE staff ADD COLUMN is_admin   TINYINT(1)  NOT NULL DEFAULT 0",
  "ALTER TABLE staff ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
  "ALTER TABLE staff ADD COLUMN created_by VARCHAR(50)  DEFAULT ''",
] as $ddl) {
  try {
    $pdo->exec($ddl);
  } catch (PDOException $e) { /* column already exists */
  }
}

// ─── Access control ───────────────────────────────────────────────────────────
$HARDCODED_ADMINS = ['osaka_ueda', 'bikash', 'kimura'];
$isPageAdmin = in_array((string) $_SESSION['username'], $HARDCODED_ADMINS, true);
if (!$isPageAdmin) {
  try {
    $q = $pdo->prepare("SELECT is_admin FROM staff WHERE id = ? LIMIT 1");
    $q->execute([$_SESSION['id']]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    $isPageAdmin = ($r && (int) ($r['is_admin'] ?? 0) === 1);
  } catch (PDOException $e) {
  }
}
if (!$isPageAdmin) {
  header('Location: staffdb.php');
  exit;
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ─── Action handler ───────────────────────────────────────────────────────────
$flash = ['msg' => '', 'type' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['csrf_token'] ?? '') !== $csrf) {
    $flash = ['msg' => '不正なリクエストです。ページを再読み込みしてください。', 'type' => 'error'];
  } else {
    $act = $_POST['action'] ?? '';

    if ($act === 'add_staff') {
      $fn = trim($_POST['full_name'] ?? '');
      $un = trim($_POST['username'] ?? '');
      $em = trim($_POST['email'] ?? '');
      $pw = $_POST['password'] ?? '';
      $ia = (int) ($_POST['is_admin'] ?? 0);

      if (!$fn || !$un || !$pw) {
        $flash = ['msg' => '名前・ユーザー名・パスワードは必須項目です。', 'type' => 'error'];
      } elseif (strlen($pw) < 8) {
        $flash = ['msg' => 'パスワードは8文字以上で入力してください。', 'type' => 'error'];
      } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $un)) {
        $flash = ['msg' => 'ユーザー名は英数字・アンダースコアのみ使用できます。', 'type' => 'error'];
      } else {
        $dup = $pdo->prepare("SELECT id FROM staff WHERE username = ? LIMIT 1");
        $dup->execute([$un]);
        if ($dup->fetch()) {
          $flash = ['msg' => '「' . htmlspecialchars($un, ENT_QUOTES) . '」は既に使用されているユーザー名です。', 'type' => 'error'];
        } else {
          // Discover every NOT NULL / no-default column so we never hit
          // "Field X doesn't have a default value" regardless of schema changes.
          $schemaStmt = $pdo->query(
            "SHOW COLUMNS FROM staff
             WHERE `Null` = 'NO'
               AND `Default` IS NULL
               AND `Extra` NOT LIKE '%auto_increment%'"
          );
          $requiredCols = $schemaStmt->fetchAll(PDO::FETCH_COLUMN, 0);

          // Values we explicitly know about
          $fields = [
            'name' => $fn,
            'full_name' => $fn,
            'username' => $un,
            'email' => $em,
            'password' => password_hash($pw, PASSWORD_BCRYPT),
            'is_admin' => $ia,
            'created_by' => (string) $_SESSION['username'],
            'failed_attempts' => 0,
            'is_blocked' => 0,
          ];

          // Any remaining required column the table has gets an empty string
          // so the INSERT never fails due to a missing NOT NULL field.
          foreach ($requiredCols as $col) {
            if (!array_key_exists($col, $fields)) {
              $fields[$col] = '';
            }
          }

          $colList = implode(',', array_keys($fields));
          $placeholders = implode(',', array_fill(0, count($fields), '?'));
          $pdo->prepare("INSERT INTO staff ($colList) VALUES ($placeholders)")
            ->execute(array_values($fields));

          $flash = ['msg' => '「' . htmlspecialchars($fn, ENT_QUOTES) . '」のアカウントを作成しました。', 'type' => 'success'];
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          $csrf = $_SESSION['csrf_token'];
        }
      }

    } elseif ($act === 'edit_staff') {
      $eid = (int) ($_POST['edit_id'] ?? 0);
      $fn = trim($_POST['full_name'] ?? '');
      $em = trim($_POST['email'] ?? '');
      $ia = (int) ($_POST['is_admin'] ?? 0);

      if (!$eid || !$fn) {
        $flash = ['msg' => '名前は必須項目です。', 'type' => 'error'];
      } else {
        $pdo->prepare("UPDATE staff SET full_name=?,email=?,is_admin=? WHERE id=?")
          ->execute([$fn, $em, $ia, $eid]);
        $flash = ['msg' => 'スタッフ情報を更新しました。', 'type' => 'success'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['csrf_token'];
      }

    } elseif ($act === 'reset_password') {
      $rid = (int) ($_POST['reset_id'] ?? 0);
      $npw = $_POST['new_password'] ?? '';
      $cpw = $_POST['confirm_password'] ?? '';

      if (!$rid) {
        $flash = ['msg' => '対象スタッフが指定されていません。', 'type' => 'error'];
      } elseif (strlen($npw) < 8) {
        $flash = ['msg' => 'パスワードは8文字以上で入力してください。', 'type' => 'error'];
      } elseif ($npw !== $cpw) {
        $flash = ['msg' => 'パスワードが一致しません。確認欄を確認してください。', 'type' => 'error'];
      } else {
        $pdo->prepare("UPDATE staff SET password=?,failed_attempts=0,is_blocked=0 WHERE id=?")
          ->execute([password_hash($npw, PASSWORD_BCRYPT), $rid]);
        $flash = ['msg' => 'パスワードをリセットし、ログイン制限を解除しました。', 'type' => 'success'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['csrf_token'];
      }

    } elseif ($act === 'toggle_block') {
      $bid = (int) ($_POST['block_id'] ?? 0);
      if ($bid === (int) $_SESSION['id']) {
        $flash = ['msg' => '自分自身をブロックすることはできません。', 'type' => 'error'];
      } elseif ($bid > 0) {
        $row = $pdo->prepare("SELECT is_blocked FROM staff WHERE id=? LIMIT 1");
        $row->execute([$bid]);
        $r = $row->fetch(PDO::FETCH_ASSOC);
        if ($r !== false) {
          if ((int) $r['is_blocked']) {
            $pdo->prepare("UPDATE staff SET is_blocked=0,failed_attempts=0 WHERE id=?")->execute([$bid]);
            $flash = ['msg' => 'アカウントのブロックを解除しました。', 'type' => 'success'];
          } else {
            $pdo->prepare("UPDATE staff SET is_blocked=1 WHERE id=?")->execute([$bid]);
            $flash = ['msg' => 'アカウントをブロックしました。', 'type' => 'success'];
          }
          $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
          $csrf = $_SESSION['csrf_token'];
        }
      }

    } elseif ($act === 'delete_staff') {
      $did = (int) ($_POST['delete_id'] ?? 0);
      if ($did === (int) $_SESSION['id']) {
        $flash = ['msg' => '自分自身のアカウントは削除できません。', 'type' => 'error'];
      } elseif ($did > 0) {
        $nr = $pdo->prepare("SELECT COALESCE(NULLIF(full_name,''),NULLIF(name,''),username) AS dn FROM staff WHERE id=? LIMIT 1");
        $nr->execute([$did]);
        $nrow = $nr->fetch(PDO::FETCH_ASSOC);
        $dn = $nrow ? htmlspecialchars($nrow['dn'], ENT_QUOTES) : 'スタッフ';
        $pdo->prepare("DELETE FROM staff WHERE id=?")->execute([$did]);
        $flash = ['msg' => "「{$dn}」のアカウントを削除しました。", 'type' => 'success'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf = $_SESSION['csrf_token'];
      }
    }
  }
}

// ─── Data ─────────────────────────────────────────────────────────────────────
$staffList = [];
try {
  $st = $pdo->query(
    "SELECT id,name,full_name,username,email,is_admin,is_blocked,failed_attempts,created_at,created_by
         FROM staff
         ORDER BY is_admin DESC, COALESCE(NULLIF(full_name,''),NULLIF(name,''),username) ASC"
  );
  $staffList = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  try {
    $st = $pdo->query("SELECT id,username,is_blocked,failed_attempts FROM staff ORDER BY username ASC");
    $staffList = $st->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e2) {
  }
}

$total = count($staffList);
$admins = count(array_filter($staffList, fn($s) => (int) ($s['is_admin'] ?? 0)));
$blocked = count(array_filter($staffList, fn($s) => (int) ($s['is_blocked'] ?? 0)));
$active = $total - $blocked;

// Sidebar check — same list as staffdb.php
$canSidebarAdmin = in_array((string) $_SESSION['username'], $HARDCODED_ADMINS, true);

// ─── Helpers ──────────────────────────────────────────────────────────────────
function avatarBg(string $s): string
{
  $p = ['#1e90ff', '#7c3aed', '#db2777', '#16a34a', '#ea580c', '#0891b2', '#9333ea', '#64748b'];
  $h = 0;
  for ($i = 0; $i < strlen($s); $i++)
    $h = ord($s[$i]) + (($h << 5) - $h);
  return $p[abs($h) % count($p)];
}
function initials(string $name, string $un): string
{
  $src = $name ?: $un;
  $pts = preg_split('/[\s_\-]+/', trim($src), -1, PREG_SPLIT_NO_EMPTY);
  if (count($pts) >= 2)
    return strtoupper(mb_substr($pts[0], 0, 1, 'UTF-8') . mb_substr($pts[1], 0, 1, 'UTF-8'));
  return strtoupper(mb_substr($src, 0, 2, 'UTF-8'));
}
function e(string $s): string
{
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>スタッフ管理 | ITF ダッシュボード</title>
  <link rel="stylesheet" href="../css/staffdb.css">
  <link rel="stylesheet" href="../css/search.css">
  <style>
    /* ─── Design tokens ─── */
    :root {
      --pr: #1e90ff;
      --pr-d: #1677d3;
      --ink: #0b2243;
      --muted: #667085;
      --border: #e6edf6;
      --bg: #f5f7fa;
      --card: #ffffff;
      --ok: #16a34a;
      --bad: #dc2626;
      --warn: #f59e0b;
      --purple: #7c3aed;
      --r: 12px;
      --sh: 0 4px 20px rgba(10, 40, 120, .07);
    }

    /* ─── Page layout override ─── */
    .content-area {
      padding: 0 28px 48px;
    }

    /* ─── Page header ─── */
    .sm-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      padding: 26px 0 8px;
      flex-wrap: wrap;
      gap: 14px;
    }

    .sm-head h1 {
      margin: 0 0 4px;
      font-size: 22px;
      color: var(--ink);
      font-weight: 800;
      letter-spacing: -.3px;
    }

    .sm-head p {
      margin: 0;
      color: var(--muted);
      font-size: 13px;
    }

    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--pr);
      color: #fff;
      border: none;
      padding: 11px 20px;
      border-radius: 999px;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      white-space: nowrap;
      transition: background .15s, transform .15s;
    }

    .btn-add:hover {
      background: var(--pr-d);
      transform: translateY(-1px);
    }

    .btn-add:active {
      transform: none;
    }

    /* ─── Stats grid ─── */
    .sm-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin: 20px 0;
    }

    @media(max-width:860px) {
      .sm-stats {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media(max-width:480px) {
      .sm-stats {
        grid-template-columns: 1fr 1fr;
      }
    }

    .stat {
      background: var(--card);
      border-radius: var(--r);
      padding: 18px 20px;
      border-left: 4px solid;
      box-shadow: var(--sh);
      user-select: none;
    }

    .stat-total {
      border-color: var(--pr);
    }

    .stat-admin {
      border-color: var(--purple);
    }

    .stat-active {
      border-color: var(--ok);
    }

    .stat-block {
      border-color: var(--bad);
    }

    .stat-num {
      font-size: 34px;
      font-weight: 900;
      line-height: 1;
      color: var(--ink);
    }

    .stat-lbl {
      font-size: 12px;
      color: var(--muted);
      margin-top: 5px;
      font-weight: 500;
    }

    /* ─── Toolbar ─── */
    .sm-toolbar {
      display: flex;
      gap: 12px;
      align-items: center;
      margin: 18px 0 14px;
    }

    .sm-searchbox {
      flex: 1;
      display: flex;
      align-items: center;
      border: 2px solid #1d96db;
      border-radius: 999px;
      height: 46px;
      background: #fff;
      overflow: hidden;
    }

    .sm-searchbox svg {
      margin: 0 12px 0 14px;
      flex-shrink: 0;
      color: #9ca3af;
    }

    .sm-searchbox input {
      border: none;
      outline: none;
      width: 100%;
      padding: 0 14px 0 0;
      font-size: 14.5px;
      font-family: inherit;
      color: var(--ink);
      background: transparent;
    }

    .sm-count-badge {
      background: #f1f5f9;
      color: var(--muted);
      border: 1px solid var(--border);
      border-radius: 999px;
      padding: 5px 13px;
      font-size: 13px;
      font-weight: 600;
      white-space: nowrap;
    }

    /* ─── Table card ─── */
    .sm-table-card {
      background: var(--card);
      border-radius: var(--r);
      box-shadow: var(--sh);
      overflow: hidden;
    }

    .sm-table {
      width: 100%;
      border-collapse: collapse;
    }

    .sm-table thead th {
      background: #f8fafc;
      color: var(--muted);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .7px;
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }

    .sm-table tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .12s;
    }

    .sm-table tbody tr:last-child {
      border-bottom: none;
    }

    .sm-table tbody tr:hover {
      background: #f0f7ff;
    }

    .sm-table tbody tr.is-self {
      background: #fffbeb;
    }

    .sm-table tbody tr.is-self:hover {
      background: #fef9c3;
    }

    .sm-table tbody tr.row-hidden {
      display: none;
    }

    .sm-table td {
      padding: 13px 16px;
      vertical-align: middle;
    }

    /* avatar */
    .sm-avt {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 800;
      color: #fff;
      letter-spacing: .3px;
    }

    .sm-staff-cell {
      display: flex;
      align-items: center;
      gap: 11px;
    }

    .sm-fullname {
      font-weight: 700;
      color: var(--ink);
      font-size: 14px;
      line-height: 1.2;
    }

    .sm-uname-sub {
      font-size: 11.5px;
      color: var(--muted);
      margin-top: 2px;
    }

    .sm-warn-sub {
      font-size: 11px;
      color: var(--warn);
      font-weight: 600;
      margin-top: 2px;
    }

    /* badges */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 9px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      white-space: nowrap;
    }

    .b-admin {
      background: #ede9fe;
      color: #5b21b6;
    }

    .b-staff {
      background: #eff6ff;
      color: #1e40af;
    }

    .b-active {
      background: #dcfce7;
      color: #166534;
    }

    .b-blocked {
      background: #fee2e2;
      color: #991b1b;
    }

    .b-you {
      background: #fef9c3;
      color: #713f12;
      font-size: 10px;
      margin-left: 5px;
      vertical-align: middle;
    }

    /* action buttons */
    .sm-acts {
      display: flex;
      gap: 5px;
      align-items: center;
      flex-wrap: wrap;
    }

    .sm-btn {
      padding: 5px 10px;
      border-radius: 7px;
      border: 1px solid var(--border);
      background: #f8fafc;
      color: var(--ink);
      font-size: 12px;
      cursor: pointer;
      font-family: inherit;
      transition: all .12s;
      white-space: nowrap;
      line-height: 1.4;
    }

    .sm-btn:hover {
      background: #e8f0fe;
      border-color: #9db8f8;
      color: var(--pr);
    }

    .sm-btn-edit {
      color: var(--pr);
      border-color: #bfdbfe;
      background: #eff6ff;
    }

    .sm-btn-edit:hover {
      background: #dbeafe;
    }

    .sm-btn-pw {
      padding: 5px 8px;
      font-size: 14px;
    }

    .sm-btn-pw:hover {
      background: #fef9c3;
      border-color: #fde68a;
      color: #92400e;
    }

    .sm-btn-block {
      color: var(--warn);
      border-color: #fde68a;
      background: #fffbeb;
    }

    .sm-btn-block:hover {
      background: #fef3c7;
    }

    .sm-btn-unblock {
      color: var(--ok);
      border-color: #bbf7d0;
      background: #f0fdf4;
    }

    .sm-btn-unblock:hover {
      background: #dcfce7;
    }

    .sm-btn-del {
      color: var(--bad);
      border-color: #fecaca;
      background: #fef2f2;
    }

    .sm-btn-del:hover {
      background: #fee2e2;
    }

    .sm-self-note {
      color: var(--muted);
      font-size: 12px;
    }

    /* empty state */
    .sm-empty {
      text-align: center;
      padding: 72px 20px;
      color: var(--muted);
    }

    .sm-empty-ico {
      font-size: 52px;
      opacity: .35;
      margin-bottom: 14px;
    }

    .sm-empty p {
      font-size: 15px;
      margin: 0;
    }

    /* no-result row */
    #no-result td {
      text-align: center;
      padding: 40px;
      color: var(--muted);
      font-size: 14px;
    }

    /* ─── Modals ─── */
    .modal-ov {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(11, 34, 67, .52);
      backdrop-filter: blur(4px);
      z-index: 9000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal-ov.open {
      display: flex;
    }

    .modal {
      background: var(--card);
      border-radius: 18px;
      padding: 32px 30px;
      width: 100%;
      max-width: 480px;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 28px 72px rgba(11, 34, 67, .18);
      position: relative;
      animation: mIn .22s cubic-bezier(.34, 1.4, .64, 1);
    }

    @keyframes mIn {
      from {
        opacity: 0;
        transform: translateY(20px) scale(.97);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .modal-x {
      position: absolute;
      top: 18px;
      right: 18px;
      background: none;
      border: none;
      font-size: 22px;
      color: var(--muted);
      cursor: pointer;
      padding: 4px 7px;
      border-radius: 6px;
      line-height: 1;
      font-family: inherit;
    }

    .modal-x:hover {
      background: #f1f5f9;
      color: var(--ink);
    }

    .modal h2 {
      margin: 0 0 4px;
      font-size: 19px;
      color: var(--ink);
      font-weight: 800;
      padding-right: 30px;
      letter-spacing: -.2px;
    }

    .modal-sub {
      color: var(--muted);
      font-size: 13px;
      margin: 0 0 22px;
    }

    .modal-divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 0 0 22px;
    }

    .field {
      margin-bottom: 15px;
    }

    .field label {
      display: block;
      font-size: 12.5px;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 5px;
    }

    .field label .req {
      color: var(--bad);
      margin-left: 2px;
    }

    .field input[type=text],
    .field input[type=email],
    .field input[type=password],
    .field select {
      width: 100%;
      padding: 10px 13px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-size: 14.5px;
      color: var(--ink);
      background: #fff;
      font-family: inherit;
      transition: border-color .15s, box-shadow .15s;
      box-sizing: border-box;
    }

    .field input:focus,
    .field select:focus {
      outline: none;
      border-color: var(--pr);
      box-shadow: 0 0 0 3px rgba(30, 144, 255, .12);
    }

    .field input:disabled {
      background: #f8fafc;
      color: var(--muted);
      cursor: not-allowed;
    }

    .field-hint {
      font-size: 11.5px;
      color: var(--muted);
      margin-top: 4px;
    }

    .field-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    @media(max-width:480px) {
      .field-row {
        grid-template-columns: 1fr;
      }
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      margin-top: 24px;
    }

    .mbtn {
      padding: 10px 20px;
      border-radius: 9px;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      transition: all .15s;
      border: none;
    }

    .mbtn-primary {
      background: var(--pr);
      color: #fff;
    }

    .mbtn-primary:hover {
      background: var(--pr-d);
    }

    .mbtn-ghost {
      background: #f1f5f9;
      color: var(--ink);
      border: 1px solid var(--border);
    }

    .mbtn-ghost:hover {
      background: #e2e8f0;
    }

    .mbtn-danger {
      background: var(--bad);
      color: #fff;
    }

    .mbtn-danger:hover {
      background: #b91c1c;
    }

    /* ─── Toast notification ─── */
    .toast {
      position: fixed;
      bottom: 26px;
      right: 26px;
      z-index: 99999;
      padding: 13px 18px;
      border-radius: 12px;
      font-size: 13.5px;
      font-weight: 600;
      max-width: 380px;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .14);
      opacity: 0;
      transform: translateY(16px);
      transition: all .32s cubic-bezier(.34, 1.5, .64, 1);
      pointer-events: none;
      line-height: 1.4;
    }

    .toast.show {
      opacity: 1;
      transform: none;
      pointer-events: auto;
    }

    .toast-s {
      background: #22c55e;
      color: #fff;
    }

    .toast-e {
      background: #ef4444;
      color: #fff;
    }
  </style>
</head>

<body>

  <!-- ══ Header ═════════════════════════════════════════════════════════════════ -->
  <header>
    <div class="logo">
      <a href="https://it-future.jp/"><img src="../images/logo.png" alt="ITF"></a>
    </div>
    <nav>
      <ul>
        <li><a href="staffdb.php">ホーム</a></li>
        <li><a href="profile.php">プロフィール</a></li>
        <li><a href="logout.php">ログアウト</a></li>
      </ul>
    </nav>
  </header>

  <!-- ══ Main layout ════════════════════════════════════════════════════════════ -->
  <div class="main-container">

    <!-- ─ Sidebar ─────────────────────────────────────────────────────────────── -->
    <div class="menu-bar">
      <div class="menu-icon"><img src="../images/searcch.png" alt=""></div>
      <div class="menu-title">Menus</div>
      <ul>
        <li><a href="addstaff.php" class="menu-btn">✙雇用者情報</a></li>
        <?php if ($canSidebarAdmin): ?>
          <li><a href="manage_jobs.php" class="menu-btn">求人管理</a></li>
          <li><a href="manage_staff.php" class="menu-btn"
              style="background:#1e90ff;color:#fff;border-color:#1e90ff;">スタッフ管理</a></li>
        <?php endif; ?>
        <li><a href="addnews.php" class="menu-btn">✙お知らせ</a></li>
        <li><a href="manage_posts.php" class="menu-btn">お知らせ管理</a></li>
        <li><a href="rireki_list.php" class="menu-btn">履歴書一覧</a></li>
      </ul>
    </div>

    <!-- ─ Content ─────────────────────────────────────────────────────────────── -->
    <div class="content-area">

      <!-- Page header -->
      <div class="sm-head">
        <div>
          <h1>スタッフ管理</h1>
          <p>アカウントの登録・権限設定・パスワードリセット・アクセス制限</p>
        </div>
        <button class="btn-add" onclick="openModal('addModal')">
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24"
            aria-hidden="true">
            <path d="M12 5v14M5 12h14" />
          </svg>
          スタッフを追加
        </button>
      </div>

      <!-- Stats -->
      <div class="sm-stats">
        <div class="stat stat-total">
          <div class="stat-num"><?= $total ?></div>
          <div class="stat-lbl">総スタッフ数</div>
        </div>
        <div class="stat stat-admin">
          <div class="stat-num"><?= $admins ?></div>
          <div class="stat-lbl">管理者</div>
        </div>
        <div class="stat stat-active">
          <div class="stat-num"><?= $active ?></div>
          <div class="stat-lbl">アクティブ</div>
        </div>
        <div class="stat stat-block">
          <div class="stat-num"><?= $blocked ?></div>
          <div class="stat-lbl">ブロック中</div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="sm-toolbar">
        <div class="sm-searchbox">
          <svg width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"
            aria-hidden="true">
            <circle cx="11" cy="11" r="8" />
            <path d="M21 21l-4.35-4.35" />
          </svg>
          <input type="text" id="staffSearch" placeholder="名前・ユーザー名・メールで絞り込み..." oninput="filterStaff(this.value)"
            autocomplete="off">
        </div>
        <span class="sm-count-badge" id="staffCount"><?= $total ?>名</span>
      </div>

      <!-- Staff table -->
      <div class="sm-table-card">
        <?php if (empty($staffList)): ?>
          <div class="sm-empty">
            <div class="sm-empty-ico">👥</div>
            <p>登録されているスタッフがいません</p>
          </div>
        <?php else: ?>
          <table class="sm-table">
            <thead>
              <tr>
                <th>スタッフ</th>
                <th>メールアドレス</th>
                <th>権限</th>
                <th>ステータス</th>
                <th>登録日</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($staffList as $s):
                $sid = (int) $s['id'];
                $isSelf = ($sid === (int) $_SESSION['id']);
                $fn = (string) ($s['full_name'] ?? '') ?: (string) ($s['name'] ?? '');
                $un = (string) ($s['username'] ?? '');
                $em = (string) ($s['email'] ?? '');
                $isBlk = (int) ($s['is_blocked'] ?? 0);
                $isAdm = (int) ($s['is_admin'] ?? 0);
                $fails = (int) ($s['failed_attempts'] ?? 0);
                $creAt = (string) ($s['created_at'] ?? '');
                $creBy = (string) ($s['created_by'] ?? '');
                $display = $fn ?: $un;
                $ini = initials($fn, $un);
                $bg = avatarBg($un);
                $search = strtolower($fn . ' ' . $un . ' ' . $em);
                $dateFmt = $creAt ? date('Y/m/d', strtotime($creAt)) : '—';
                $jdata = json_encode([
                  'id' => $sid,
                  'full_name' => $fn,
                  'email' => $em,
                  'is_admin' => $isAdm,
                  'username' => $un,
                ], JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
                ?>
                <tr class="<?= $isSelf ? 'is-self' : '' ?>" data-search="<?= e($search) ?>">

                  <!-- Staff name + avatar -->
                  <td>
                    <div class="sm-staff-cell">
                      <div class="sm-avt" style="background:<?= $bg ?>">
                        <?= e($ini) ?>
                      </div>
                      <div>
                        <div class="sm-fullname">
                          <?= e($display) ?>
                          <?php if ($isSelf): ?>
                            <span class="badge b-you">あなた</span>
                          <?php endif; ?>
                        </div>
                        <div class="sm-uname-sub">@<?= e($un) ?></div>
                        <?php if ($fails > 0 && !$isBlk): ?>
                          <div class="sm-warn-sub">⚠ ログイン失敗 <?= $fails ?>回</div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>

                  <!-- Email -->
                  <td style="font-size:13.5px;color:var(--muted);">
                    <?= $em ? e($em) : '<span style="color:#d1d5db">—</span>' ?>
                  </td>

                  <!-- Role -->
                  <td>
                    <?php if ($isAdm): ?>
                      <span class="badge b-admin">管理者</span>
                    <?php else: ?>
                      <span class="badge b-staff">スタッフ</span>
                    <?php endif; ?>
                  </td>

                  <!-- Status -->
                  <td>
                    <?php if ($isBlk): ?>
                      <span class="badge b-blocked">ブロック中</span>
                    <?php else: ?>
                      <span class="badge b-active">アクティブ</span>
                    <?php endif; ?>
                  </td>

                  <!-- Registered date -->
                  <td style="font-size:12.5px;color:var(--muted);white-space:nowrap;">
                    <?= e($dateFmt) ?>
                    <?php if ($creBy): ?>
                      <div style="font-size:11px;color:#cbd5e1;margin-top:1px;">by <?= e($creBy) ?></div>
                    <?php endif; ?>
                  </td>

                  <!-- Actions -->
                  <td>
                    <div class="sm-acts">
                      <!-- Edit -->
                      <button class="sm-btn sm-btn-edit" onclick='openEditModal(<?= $jdata ?>)'>編集</button>

                      <!-- Password reset -->
                      <button class="sm-btn sm-btn-pw"
                        onclick="openResetModal(<?= $sid ?>, '<?= e(addslashes($display)) ?>')"
                        title="パスワードリセット">🔑</button>

                      <?php if (!$isSelf): ?>
                        <!-- Block / Unblock -->
                        <form method="post" style="display:inline;">
                          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                          <input type="hidden" name="action" value="toggle_block">
                          <input type="hidden" name="block_id" value="<?= $sid ?>">
                          <?php if ($isBlk): ?>
                            <button type="submit" class="sm-btn sm-btn-unblock" title="ブロックを解除">🔓 解除</button>
                          <?php else: ?>
                            <button type="submit" class="sm-btn sm-btn-block" title="アカウントを停止">🔒 停止</button>
                          <?php endif; ?>
                        </form>

                        <!-- Delete -->
                        <form method="post" style="display:inline;"
                          onsubmit="return confirm('「<?= e(addslashes($display)) ?>」を削除しますか？\nこの操作は取り消せません。')">
                          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                          <input type="hidden" name="action" value="delete_staff">
                          <input type="hidden" name="delete_id" value="<?= $sid ?>">
                          <button type="submit" class="sm-btn sm-btn-del">削除</button>
                        </form>
                      <?php else: ?>
                        <span class="sm-self-note">（自分）</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <!-- No-result row (shown by JS search) -->
              <tr id="no-result" style="display:none;">
                <td colspan="6">
                  「<span id="no-result-term"></span>」に一致するスタッフは見つかりませんでした
                </td>
              </tr>
            </tbody>
          </table>
        <?php endif; ?>
      </div><!-- /.sm-table-card -->

    </div><!-- /.content-area -->
  </div><!-- /.main-container -->


  <!-- ══ Modal: Add Staff ════════════════════════════════════════════════════════ -->
  <div class="modal-ov" id="addModal" onclick="closeOnOverlay(event,'addModal')">
    <div class="modal">
      <button class="modal-x" onclick="closeModal('addModal')" title="閉じる">×</button>
      <h2>スタッフを追加</h2>
      <p class="modal-sub">新しいアカウントを登録します。初期パスワードを設定し、本人にお知らせください。</p>
      <hr class="modal-divider">
      <form method="post" onsubmit="return validateAddForm(event)">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add_staff">
        <div class="field-row">
          <div class="field">
            <label>氏名 <span class="req">*</span></label>
            <input type="text" name="full_name" id="add_fn" placeholder="例：山田 太郎" autocomplete="off" required>
          </div>
          <div class="field">
            <label>ユーザー名 <span class="req">*</span></label>
            <input type="text" name="username" id="add_un" placeholder="例：yamada_t" pattern="[a-zA-Z0-9_]+"
              title="英数字・アンダースコアのみ" autocomplete="off" required>
            <div class="field-hint">英数字・_(アンダースコア)のみ</div>
          </div>
        </div>
        <div class="field">
          <label>メールアドレス</label>
          <input type="email" name="email" placeholder="例：yamada@example.com" autocomplete="off">
        </div>
        <div class="field-row">
          <div class="field">
            <label>初期パスワード <span class="req">*</span></label>
            <input type="password" name="password" id="add_pw" placeholder="8文字以上" minlength="8"
              autocomplete="new-password" required>
          </div>
          <div class="field">
            <label>パスワード確認 <span class="req">*</span></label>
            <input type="password" id="add_pw2" placeholder="もう一度入力" autocomplete="new-password" required>
          </div>
        </div>
        <div class="field">
          <label>権限レベル</label>
          <select name="is_admin">
            <option value="0">スタッフ（一般）</option>
            <option value="1">管理者</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="mbtn mbtn-ghost" onclick="closeModal('addModal')">
            キャンセル
          </button>
          <button type="submit" class="mbtn mbtn-primary">アカウントを作成</button>
        </div>
      </form>
    </div>
  </div>


  <!-- ══ Modal: Edit Staff ═══════════════════════════════════════════════════════ -->
  <div class="modal-ov" id="editModal" onclick="closeOnOverlay(event,'editModal')">
    <div class="modal">
      <button class="modal-x" onclick="closeModal('editModal')" title="閉じる">×</button>
      <h2>スタッフ情報を編集</h2>
      <p class="modal-sub" id="edit-sub">情報と権限を変更します。</p>
      <hr class="modal-divider">
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="edit_staff">
        <input type="hidden" name="edit_id" id="edit-id">
        <div class="field">
          <label>ユーザー名</label>
          <input type="text" id="edit-un-disp" disabled>
        </div>
        <div class="field">
          <label>氏名 <span class="req">*</span></label>
          <input type="text" name="full_name" id="edit-fn" placeholder="例：山田 太郎" required>
        </div>
        <div class="field">
          <label>メールアドレス</label>
          <input type="email" name="email" id="edit-em" placeholder="例：yamada@example.com">
        </div>
        <div class="field">
          <label>権限レベル</label>
          <select name="is_admin" id="edit-ia">
            <option value="0">スタッフ（一般）</option>
            <option value="1">管理者</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="mbtn mbtn-ghost" onclick="closeModal('editModal')">
            キャンセル
          </button>
          <button type="submit" class="mbtn mbtn-primary">変更を保存</button>
        </div>
      </form>
    </div>
  </div>


  <!-- ══ Modal: Reset Password ══════════════════════════════════════════════════ -->
  <div class="modal-ov" id="resetModal" onclick="closeOnOverlay(event,'resetModal')">
    <div class="modal">
      <button class="modal-x" onclick="closeModal('resetModal')" title="閉じる">×</button>
      <h2>パスワードをリセット</h2>
      <p class="modal-sub" id="reset-sub">新しいパスワードを設定します。</p>
      <hr class="modal-divider">
      <form method="post" onsubmit="return validateResetForm(event)">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="reset_id" id="reset-id">
        <div class="field">
          <label>新しいパスワード <span class="req">*</span></label>
          <input type="password" name="new_password" id="reset-pw" placeholder="8文字以上" minlength="8"
            autocomplete="new-password" required>
          <div class="field-hint">リセット後、ログイン失敗回数とブロック状態も自動解除されます。</div>
        </div>
        <div class="field">
          <label>パスワード確認 <span class="req">*</span></label>
          <input type="password" name="confirm_password" id="reset-pw2" placeholder="もう一度入力" autocomplete="new-password"
            required>
        </div>
        <div class="modal-actions">
          <button type="button" class="mbtn mbtn-ghost" onclick="closeModal('resetModal')">
            キャンセル
          </button>
          <button type="submit" class="mbtn mbtn-danger">パスワードをリセット</button>
        </div>
      </form>
    </div>
  </div>


  <!-- ══ Toast ══════════════════════════════════════════════════════════════════ -->
  <div class="toast" id="toast" role="status" aria-live="polite"></div>


  <!-- ══ Scripts ════════════════════════════════════════════════════════════════ -->
  <script>
    // ── Modal core ──────────────────────────────────────────────────────────────
    function openModal(id) {
      document.getElementById(id).classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
      document.getElementById(id).classList.remove('open');
      document.body.style.overflow = '';
    }
    function closeOnOverlay(e, id) {
      if (e.target === document.getElementById(id)) closeModal(id);
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        ['addModal', 'editModal', 'resetModal'].forEach(closeModal);
        document.body.style.overflow = '';
      }
    });

    // ── Edit modal ───────────────────────────────────────────────────────────────
    function openEditModal(data) {
      document.getElementById('edit-id').value = data.id;
      document.getElementById('edit-un-disp').value = '@' + (data.username || '');
      document.getElementById('edit-fn').value = data.full_name || '';
      document.getElementById('edit-em').value = data.email || '';
      document.getElementById('edit-ia').value = data.is_admin ? '1' : '0';
      document.getElementById('edit-sub').textContent =
        '「' + (data.full_name || data.username) + '」の情報を編集';
      openModal('editModal');
    }

    // ── Reset modal ──────────────────────────────────────────────────────────────
    function openResetModal(id, name) {
      document.getElementById('reset-id').value = id;
      document.getElementById('reset-sub').textContent = '「' + name + '」のパスワードを新しく設定します。';
      document.getElementById('reset-pw').value = '';
      document.getElementById('reset-pw2').value = '';
      openModal('resetModal');
    }

    // ── Validation ───────────────────────────────────────────────────────────────
    function validateAddForm(e) {
      var pw = document.getElementById('add_pw').value;
      var pw2 = document.getElementById('add_pw2').value;
      if (pw.length < 8) { alert('パスワードは8文字以上で入力してください。'); return false; }
      if (pw !== pw2) { alert('パスワードが一致しません。'); return false; }
      return true;
    }
    function validateResetForm(e) {
      var pw = document.getElementById('reset-pw').value;
      var pw2 = document.getElementById('reset-pw2').value;
      if (pw.length < 8) { alert('パスワードは8文字以上で入力してください。'); return false; }
      if (pw !== pw2) { alert('パスワードが一致しません。'); return false; }
      return true;
    }

    // ── Search / filter ──────────────────────────────────────────────────────────
    var allRows = null;
    var noResult = null;
    var countBadge = null;

    function filterStaff(q) {
      if (!allRows) allRows = document.querySelectorAll('.sm-table tbody tr[data-search]');
      if (!noResult) noResult = document.getElementById('no-result');
      if (!countBadge) countBadge = document.getElementById('staffCount');

      var term = q.trim().toLowerCase();
      var visible = 0;

      allRows.forEach(function (row) {
        var match = !term || row.dataset.search.includes(term);
        row.classList.toggle('row-hidden', !match);
        if (match) visible++;
      });

      if (noResult) {
        noResult.style.display = (term && visible === 0) ? '' : 'none';
        var termEl = document.getElementById('no-result-term');
        if (termEl) termEl.textContent = q.trim();
      }
      if (countBadge) {
        countBadge.textContent = (term ? visible : <?= $total ?>) + '名';
      }
    }

    // ── Toast ────────────────────────────────────────────────────────────────────
    var toastTimer = null;
    function showToast(msg, type) {
      var t = document.getElementById('toast');
      t.textContent = msg;
      t.className = 'toast ' + (type === 'success' ? 'toast-s' : 'toast-e');
      t.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () { t.classList.remove('show'); }, 4500);
    }

    // ── Auto-fire flash on load ──────────────────────────────────────────────────
    <?php if ($flash['msg']): ?>
      document.addEventListener('DOMContentLoaded', function () {
        showToast(<?= json_encode($flash['msg'], JSON_UNESCAPED_UNICODE) ?>,
          <?= json_encode($flash['type']) ?>);
      });
    <?php endif; ?>
  </script>

</body>

</html>