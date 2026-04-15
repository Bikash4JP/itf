<?php
// /home/it-future/www/itf/rireki/user_data.php

declare(strict_types=1);

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

// Cache BUSTING - Extremely important so back button/nav doesn't show stale state
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

// Auth Guard
if (!app_is_logged_in()) {
    header('Location: /php/user_login.php?next=/rireki/user_data.php', true, 302);
    exit;
}

$pdo = app_pdo();
$uid = (int)app_user_id();

// Load profile data
$profKaigo = app_load_profile($pdo, $uid, 'kaigo');
$profBasic = app_load_profile($pdo, $uid, 'basic');
$hasKaigo = $profKaigo !== null;
$hasBasic = $profBasic !== null;

// Determine primary format to show
if ($hasKaigo) {
    header("Location: /rireki/kaigo/php/rireki_preview.php?flow=profile_only", true, 302);
    exit;
}

$editUrl = '';
if ($hasBasic) {
    $editUrl = '/rireki/basic/rireki.php';
    $st = $pdo->prepare("SELECT token FROM app_resumes WHERE user_id = ? AND fmt = 'basic' ORDER BY id DESC LIMIT 1");
    $st->execute([$uid]);
    $t = $st->fetchColumn();
    if ($t) $editUrl .= '?token=' . urlencode((string)$t);
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>マイページ｜ITF 履歴書メーカー</title>
  <meta name="robots" content="noindex,follow">
  <link rel="icon" href="https://it-future.jp/images/favicon-32x32.png" sizes="32x32">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    /* ===== Reset & Base ===== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sky: #3b9eff; --sky2: #1e78e8; --violet: #7c3aed;
      --ink: #e6edf3; --muted: #8b949e; --border: rgba(255,255,255,.1);
      --card: rgba(255,255,255,.06); --danger: #f85149;
    }
    html, body { height: 100%; font-family: 'Inter','Noto Sans JP',sans-serif; }
    body {
      background:
        radial-gradient(ellipse 80% 55% at 50% -5%, rgba(59,158,255,.2) 0%, transparent 65%),
        linear-gradient(155deg, #060d1a 0%, #0d1f3c 50%, #080f20 100%);
      color: var(--ink);
      display: flex; flex-direction: column;
    }

    /* ===== Topnav ===== */
    .topnav {
      position: relative; z-index: 50;
      background: rgba(6,13,26,.82); backdrop-filter: blur(18px) saturate(200%);
      border-bottom: 1px solid var(--border);
      padding: 0 24px; min-height: 64px;
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .nav-left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .nav-logo-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--sky), var(--violet));
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 4px 14px rgba(59,158,255,.4);
    }
    .nav-logo-mark img { width: 100%; height: 100%; object-fit: cover; }
    .nav-title { font-size: 15px; font-weight: 800; }
    .nav-sub { font-size: 12px; color: var(--muted); }

    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
      border: 1px solid var(--border); background: var(--card);
      color: var(--ink); cursor: pointer; text-decoration: none; transition: .2s;
    }
    .btn:hover { background: rgba(255,255,255,.12); }
    .btn.primary { background: linear-gradient(135deg, var(--sky), var(--sky2)); border: none; color: #fff; box-shadow: 0 4px 14px rgba(59,158,255,.3); }
    .btn.primary:hover { filter: brightness(1.1); }
    .btn.ghost { border: none; background: transparent; }
    .btn.ghost:hover { background: rgba(255,255,255,.05); }

    .empty-state {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-align: center; padding: 40px; margin: auto;
    }
    .empty-state .icon { font-size: 64px; margin-bottom: 20px; opacity: .8; }
    .empty-state h2 { font-size: 24px; font-weight: 900; margin-bottom: 12px; }
    .empty-state p { color: var(--muted); font-size: 15px; margin-bottom: 30px; line-height: 1.6; }
  </style>
</head>
<body>

<nav class="topnav">
  <div class="nav-left">
    <a href="/rireki/" class="nav-logo-mark"><img src="https://it-future.jp/images/android-chrome-192x192.png" alt="ITF"></a>
    <div>
      <div class="nav-title">マイページ（履歴書ダッシュボード）</div>
      <div class="nav-sub">プロフィールデータの管理</div>
    </div>
  </div>
  <a class="btn ghost" href="/php/user_logout.php" style="margin-left:auto;color:var(--muted)">ログアウト</a>
</nav>

<div class="empty-state">
  <?php if ($hasBasic): ?>
    <div class="icon">📝</div>
    <h2>標準（Basic）履歴書のプレビュー機能は<br>現在準備中です。</h2>
    <p>上の「標準フォームを編集する」ボタンをクリックして、<br>入力フォームへ進んでください。</p>
    <a class="btn primary" href="<?= h($editUrl) ?>" style="padding:14px 28px;font-size:16px">標準フォームを編集する</a>
  <?php else: ?>
    <div class="icon">📄</div>
    <h2>まだ履歴書がありません</h2>
    <p>履歴書を作成して保存すると、このダッシュボードから<br>いつでも確認・編集・ダウンロード・直接応募が可能になります。</p>
    <a class="btn primary" href="/rireki/" style="padding:14px 28px;font-size:16px">フォーマットを選んで作成開始</a>
  <?php endif; ?>
</div>

</body>
</html>
