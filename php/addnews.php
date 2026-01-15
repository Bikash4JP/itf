<?php
// /home/it-future/www/itf/php/addnews.php
// Add "News/Announcement" (お知らせ) — separate page version

// ---- session & auth ----
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: /php/login.php");
  exit;
}

// ---- DB ----
require_once __DIR__ . '/db_connect.php';

// ---- CSRF helpers ----
if (empty($_SESSION['csrf_addnews'])) {
  $_SESSION['csrf_addnews'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_addnews'];

// ---- POST handler ----
$err = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (!isset($_POST['csrf']) || !hash_equals($csrf, $_POST['csrf'])) {
    $err[] = '不正なリクエストです（CSRF）。もう一度お試しください。';
  }

  // Collect/validate inputs
  $title    = trim($_POST['title'] ?? '');
  $summary  = trim($_POST['summary'] ?? '');
  $category = trim($_POST['category'] ?? 'その他');
  $content  = trim($_POST['content'] ?? '');
  $date     = trim($_POST['date'] ?? date('Y-m-d'));
  $posted_by= trim($_POST['posted_by'] ?? $_SESSION['username']);
  $staff_id = (int)($_POST['staff_id'] ?? $_SESSION['id']);

  if ($title === '')   $err[] = 'タイトルは必須です。';
  if ($summary === '') $err[] = '概要は必須です。';
  if ($content === '') $err[] = '内容は必須です。';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err[] = '日付の形式が正しくありません。';

  // Optional image upload
  $imageRel = null; // e.g., "uploads/news/xxx.jpg"
  if (!empty($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $up = $_FILES['image'];
    if ($up['error'] !== UPLOAD_ERR_OK) {
      $err[] = '画像のアップロードに失敗しました。';
    } else {
      // Validate size <= 2MB
      if ($up['size'] > 2 * 1024 * 1024) {
        $err[] = '画像は 2MB 以下にしてください。';
      } else {
        // Validate type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($up['tmp_name']);
        $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($ok[$mime])) {
          $err[] = '画像形式は JPEG/PNG のみ対応しています。';
        } else {
          // Ensure uploads/news exists
          $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
          $absDir  = $docroot . '/uploads/news';
          if (!is_dir($absDir)) @mkdir($absDir, 0775, true);

          // Safe filename
          $ext = $ok[$mime];
          $base = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          $absPath = $absDir . '/' . $base;

          if (!move_uploaded_file($up['tmp_name'], $absPath)) {
            $err[] = '画像の保存に失敗しました。';
          } else {
            // Relative path saved to DB (fetch_news expects paths starting with "uploads/")
            $imageRel = 'uploads/news/' . $base;
          }
        }
      }
    }
  }

  // Insert if valid
  if (!$err) {
    try {
      $sql = "INSERT INTO posts (post_type, title, summary, category, content, image, date, posted_by, staff_id)
              VALUES ('news', :title, :summary, :category, :content, :image, :date, :posted_by, :staff_id)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':title'     => $title,
        ':summary'   => $summary,
        ':category'  => $category,
        ':content'   => $content,
        ':image'     => $imageRel,     // can be null
        ':date'      => $date,
        ':posted_by' => $posted_by,
        ':staff_id'  => $staff_id,
      ]);
      // success → go back to staff home
      $_SESSION['flash_ok'] = 'お知らせを投稿しました。';
      header('Location: /staffdb.php');
      exit;
    } catch (PDOException $e) {
      $err[] = 'データベース保存時にエラーが発生しました。';
      // Log quietly
      @file_put_contents("/home/it-future/www/itf/logs/db_error.log",
        "AddNews Error: ".$e->getMessage()." | Time: ".date('Y-m-d H:i:s')."\n", FILE_APPEND);
    }
  }
}

// ---- View (form) ----
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>✙ お知らせを追加</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/staffdb.css?v=1.4">
  <style>
    body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;margin:20px}
    .wrap{max-width:900px;margin:0 auto}
    header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
    header h1{margin:0;font-size:20px}
    a.btn{display:inline-block;padding:8px 12px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;text-decoration:none}
    a.btn:hover{background:#e9f5ff}
    form{background:#fff;border:1px solid #e6edf6;border-radius:12px;padding:16px}
    label{display:block;margin:10px 0 6px;font-weight:600}
    input[type="text"],input[type="date"],textarea,select{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box}
    textarea{min-height:120px}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
    button{padding:10px 14px;border:1px solid #dbe7f5;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer}
    button.secondary{background:#f3f9ff;color:#0c4a7a}
    .errors{background:#fff7f7;border:1px solid #fecaca;color:#7f1d1d;padding:12px;border-radius:10px;margin-bottom:12px}
    .note{color:#667085;font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>✙ お知らせを追加</h1>
      <a class="btn" href="/staffdb.php" style="margin-left:auto">← ダッシュボードへ戻る</a>
    </header>

    <?php if ($err): ?>
      <div class="errors">
        <ul style="margin:0;padding-left:18px">
          <?php foreach ($err as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
      <input type="hidden" name="posted_by" value="<?=htmlspecialchars($_SESSION['username'])?>">
      <input type="hidden" name="staff_id" value="<?=htmlspecialchars((string)$_SESSION['id'])?>">

      <div class="grid-2">
        <div>
          <label>掲載日 *</label>
          <input type="date" name="date" value="<?=htmlspecialchars(date('Y-m-d'))?>" required>
        </div>
        <div>
          <label>カテゴリ *</label>
          <select name="category" required>
            <option value="入社情報">入社情報</option>
            <option value="連携">連携</option>
            <option value="募集">募集</option>
            <option value="イベント">イベント</option>
            <option value="セミナー">セミナー</option>
            <option value="その他" selected>その他</option>
          </select>
        </div>
      </div>

      <label>タイトル *</label>
      <input type="text" name="title" maxlength="200" required>

      <label>概要（70〜100語目安） *</label>
      <textarea name="summary" required></textarea>

      <label>内容 *</label>
      <textarea name="content" rows="10" required></textarea>

      <label>画像（JPEG/PNG、最大2MB）</label>
      <input type="file" name="image" accept="image/jpeg,image/png">
      <p class="note">※ 画像は任意です。アップロードされた場合は <code>uploads/news/</code> に保存され、API（fetch_news.php）から参照されます。</p>

      <div class="actions">
        <a class="btn secondary" href="/staffdb.php">キャンセル</a>
        <button type="submit">投稿する</button>
      </div>
    </form>
  </div>
</body>
</html>
