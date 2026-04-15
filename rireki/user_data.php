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
    header('Location: /rireki/rireki_login.php?next=/rireki/user_data.php', true, 302);
    exit;
}

$pdo = app_pdo();
$uid = (int)app_user_id();

// Load profile data
$hasKaigo = app_load_profile($pdo, $uid, 'kaigo') !== null;
$hasBasic = app_load_profile($pdo, $uid, 'basic') !== null;

// Determine primary format to show
$primaryMode = 'none';
$previewUrl = '';
if ($hasKaigo) {
    $primaryMode = 'kaigo';
    $previewUrl = '/rireki/kaigo/php/rireki_preview.php?flow=profile_only&embedded=1';
    $editUrl = '/rireki/kaigo/rireki.php?edit=1';
} elseif ($hasBasic) {
    $primaryMode = 'basic';
    $previewUrl = ''; // Basic preview doesn't exist for direct read yet
    $editUrl = '/rireki/basic/rireki.php';
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
      --card: rgba(255,255,255,.06); --danger: #f85149; --danger-hover: #ff6a6a;
    }
    html, body { height: 100%; font-family: 'Inter','Noto Sans JP',sans-serif; overflow: hidden; }
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

    /* Actions Bar */
    .dashboard-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding: 10px 0; }
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
      border: 1px solid var(--border); background: var(--card);
      color: var(--ink); cursor: pointer; text-decoration: none; transition: .2s;
    }
    .btn:hover { background: rgba(255,255,255,.12); }
    .btn.primary { background: linear-gradient(135deg, var(--sky), var(--sky2)); border: none; color: #fff; box-shadow: 0 4px 14px rgba(59,158,255,.3); }
    .btn.primary:hover { filter: brightness(1.1); }
    .btn.danger { color: var(--danger); border-color: rgba(248,81,73,.3); background: rgba(248,81,73,.1); }
    .btn.danger:hover { background: rgba(248,81,73,.2); border-color: var(--danger); }
    .btn.ghost { border: none; background: transparent; }
    .btn.ghost:hover { background: rgba(255,255,255,.05); }

    /* ===== Main Content Area ===== */
    .workspace {
      flex: 1; display: flex; flex-direction: column; position: relative;
    }
    .preview-frame {
      width: 100%; height: 100%; border: none; background: transparent;
    }
    
    .empty-state {
      flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-align: center; padding: 40px;
    }
    .empty-state .icon { font-size: 64px; margin-bottom: 20px; opacity: .8; }
    .empty-state h2 { font-size: 24px; font-weight: 900; margin-bottom: 12px; }
    .empty-state p { color: var(--muted); font-size: 15px; margin-bottom: 30px; line-height: 1.6; }

    /* Modals */
    .modal-backdrop {
      display: none; position: fixed; inset: 0; z-index: 900;
      background: rgba(0,0,0,.7); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .modal-backdrop.open { display: flex; }
    .modal {
      background: rgba(13,17,27,.95); border: 1px solid var(--border); border-radius: 18px;
      padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,.6); text-align: center;
    }
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
  
  <div class="dashboard-actions">
    <?php if ($primaryMode !== 'none'): ?>
      <a class="btn" href="https://it-future.jp/saiyou.php">💼 おすすめ求人を見る</a>
      <a class="btn primary" href="<?= h($editUrl) ?>">✏️ データを編集・更新する</a>
      <a class="btn" href="/rireki/index.php#formats">➕ 新規フォーマット切替</a>
      <button class="btn danger" onclick="showDeleteModal()">🗑 データを完全削除してやり直す</button>
    <?php else: ?>
      <a class="btn ghost" href="/php/user_logout.php">ログアウト</a>
    <?php endif; ?>
  </div>
</nav>

<div class="workspace">
  <?php if ($primaryMode === 'kaigo'): ?>
    <iframe class="preview-frame" src="<?= h($previewUrl) ?>"></iframe>
  <?php elseif ($primaryMode === 'basic'): ?>
    <div class="empty-state">
      <div class="icon">📝</div>
      <h2>標準（Basic）履歴書のプレビューは<br>編集中のみ確認できます</h2>
      <p>上の「✏️ データを編集・更新する」ボタンをクリックして、<br>入力フォームへ進んでください。<br>※介護向けテンプレートをご利用いただくと、ここに専用のダッシュボードが表示されます。</p>
      <a class="btn primary" href="<?= h($editUrl) ?>" style="padding:14px 28px;font-size:16px">標準フォームを編集する</a>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="icon">📄</div>
      <h2>まだ履歴書がありません</h2>
      <p>履歴書を作成して保存すると、このダッシュボードから<br>いつでも確認・編集・ダウンロード・直接応募が可能になります。</p>
      <a class="btn primary" href="/rireki/" style="padding:14px 28px;font-size:16px">フォーマットを選んで作成開始</a>
    </div>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-backdrop">
  <div class="modal">
    <div style="font-size:36px;margin-bottom:12px">⚠️</div>
    <h3 style="font-size:18px;margin-bottom:10px">本当に削除しますか？</h3>
    <p style="color:var(--muted);font-size:14px;margin-bottom:20px">履歴書データが完全に消去され、復元できなくなります。<br>最初から全く新しい履歴書を作り直す場合のみ実行してください。</p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button class="btn" onclick="hideDeleteModal()">キャンセル</button>
      <form action="/rireki/php/delete_profile.php" method="POST">
        <input type="hidden" name="action" value="delete_all">
        <button class="btn danger" type="submit">はい、データを削除します</button>
      </form>
    </div>
  </div>
</div>

<script>
  function showDeleteModal() { document.getElementById('deleteModal').classList.add('open'); }
  function hideDeleteModal() { document.getElementById('deleteModal').classList.remove('open'); }
</script>
</body>
</html>
