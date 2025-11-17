<?php
// /home/it-future/www/itf/rireki/rireki_terms.php

// --- Read & sanitize "next" (force internal path only)
$next = isset($_GET['next']) ? trim($_GET['next']) : '';
if ($next === '' || strpos($next, '/') !== 0 || stripos($next, '://') !== false) {
  $next = '/rireki/'; // safe fallback
}

// Optional: format hint (unused in logic, just for copy tone)
$fmt = isset($_GET['fmt']) ? preg_replace('/[^a-z]/i', '', $_GET['fmt']) : 'basic';

// Handle POST
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $agreed = isset($_POST['agree']) && $_POST['agree'] === '1';
  $postedNext = isset($_POST['next']) ? $_POST['next'] : '';
  // re-sanitize posted next
  if ($postedNext && strpos($postedNext, '/') === 0 && stripos($postedNext, '://') === false) {
    $next = $postedNext;
  }

  if ($agreed) {
    // No cookie, no memory — always require consent every time
    header('Location: ' . $next, true, 302);
    exit;
  } else {
    $err = '利用規約・同意事項に同意してください。';
  }
}
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ご利用規約と同意事項｜オンライン履歴書メーカー</title>
  <meta name="robots" content="noindex,follow">
  <link rel="preconnect" href="https://it-future.jp" crossorigin>
  <style>
    :root{
      --ink:#0b0f19; --muted:#64748b; --border:#e5e7eb; --ring:#1e90ff;
      --card:#ffffff; --bg:#f8fafc; --danger:#b91c1c; --ok:#166534;
    }
    *{ box-sizing:border-box }
    body{
      margin:0; font-family: ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP",Meiryo,Arial,sans-serif;
      color:var(--ink); background:linear-gradient(180deg,#f8fbff,#eef6ff);
    }
    header{ position:sticky; top:0; z-index:10; background:rgba(255,255,255,.86); border-bottom:1px solid #eef2f7; backdrop-filter:saturate(180%) blur(6px); }
    .wrap{ max-width:900px; margin:0 auto; padding:20px }
    h1{ margin:0; font-size:22px; font-weight:900 }
    main{ padding:20px }
    .card{
      background:var(--card); border:1px solid var(--border); border-radius:14px; padding:18px;
      box-shadow:0 10px 24px rgba(0,0,0,.05)
    }
    .lead{ color:var(--muted); margin:6px 0 14px }
    h2{ font-size:18px; margin:16px 0 8px }
    ol,ul{ padding-left:1.2em }
    .agree{
      display:flex; gap:10px; align-items:flex-start; margin:14px 0 0; padding-top:10px; border-top:1px dashed #e5e7eb;
    }
    .btnrow{ display:flex; gap:10px; margin-top:12px; flex-wrap:wrap }
    .btn{
      appearance:none; cursor:pointer; border-radius:10px; padding:12px 14px; font-weight:800;
      border:1px solid #dbe7f5; background:#f3f9ff; color:#0c4a7a; text-decoration:none;
      transition:background .2s,transform .2s,box-shadow .2s,border-color .2s;
    }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); box-shadow:0 6px 18px rgba(30,144,255,.16) }
    .btn.primary{ background:linear-gradient(180deg,#39a7ff,#1e90ff); border-color:#39a7ff; color:#fff; }
    .btn[disabled]{ opacity:.7; cursor:not-allowed; filter:grayscale(.15) }
    .err{ color:var(--danger); font-weight:700; margin:8px 0 0 }
    .fine{ color:#475569; font-size:12px; margin-top:6px }
    .back{ text-decoration:none; color:#0c4a7a }
    .badge{
      display:inline-block; font-size:12px; padding:3px 8px; border-radius:999px; border:1px solid #d7ebff; background:#eef7ff; color:#1969b5;
    }
  </style>
</head>
<body>
  <header>
    <div class="wrap">
      <h1>ご利用規約・同意事項の確認 <span class="badge"><?php echo ($fmt==='kaigo'?'介護向け':'標準'); ?></span></h1>
      <p class="lead">次のページに進む前に、下記の内容に同意してください。</p>
    </div>
  </header>

  <main class="wrap">
    <form class="card" method="post" action="/rireki/rireki_terms.php">
      <input type="hidden" name="next" value="<?php echo htmlspecialchars($next, ENT_QUOTES, 'UTF-8'); ?>">
      <!-- honeypot -->
      <input type="text" name="company" value="" style="position:absolute;left:-9999px;top:-9999px" tabindex="-1" aria-hidden="true" autocomplete="off">

      <h2>個人情報の取扱い</h2>
      <ol>
        <li>入力いただく内容（氏名・住所・連絡先・写真・学歴/職歴 等）は、履歴書の生成および応募管理のために利用します。</li>
        <li>当社の<a class="back" href="https://it-future.jp/privacy.html" target="_blank" rel="noopener">プライバシーポリシー</a>に基づき、適切に保管・管理します。</li>
        <li>法令に基づく場合を除き、ご本人の同意なく第三者へ提供しません。</li>
      </ol>

      <h2>利用条件</h2>
      <ol>
        <li>入力内容はご自身の情報に限ります。虚偽入力や第三者の情報を無断で登録する行為は禁止です。</li>
        <li>生成されるデータ（Excel 等）はテンプレートに依存します。PDF 出力は環境により体裁差が出る場合があります。</li>
        <li>本サービスは無償提供ですが、予告なく機能変更・停止する場合があります。</li>
      </ol>

      <h2>外部連携・応募</h2>
      <ol>
        <li>求人応募のため、生成した履歴書データを当社求人管理システムに連携する場合があります。</li>
        <li>応募先企業へ提出する際は内容をご自身で最終確認してください。</li>
      </ol>

      <label class="agree">
        <input id="agree" type="checkbox" name="agree" value="1" style="margin-top:3px">
        <span>
          上記の<strong>「個人情報の取扱い」および「利用条件」</strong>に同意します。<br>
          （毎回、同意チェックが必要です）
        </span>
      </label>

      <?php if ($err): ?>
        <div class="err" role="alert"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <div class="btnrow">
        <button id="proceedBtn" class="btn primary" type="submit" disabled>同意して進む</button>
        <a class="btn back" href="/rireki/">戻る</a>
      </div>
      <p class="fine">※ チェックを外すとボタンは押せません。スクリーンリーダー対応済み。</p>
    </form>
  </main>

  <script>
    // Enable/disable proceed button based on checkbox, no persistence
    (function(){
      const agree = document.getElementById('agree');
      const btn = document.getElementById('proceedBtn');
      if(!agree || !btn) return;
      function sync(){ btn.disabled = !agree.checked; }
      agree.addEventListener('change', sync);
      sync();
    })();
  </script>
</body>
</html>
