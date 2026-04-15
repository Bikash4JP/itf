<?php
// /home/it-future/www/itf/rireki/rireki_login.php
// Replaces rireki_terms.php — requires login/register before accessing resume forms.

declare(strict_types=1);

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

$pdo = app_pdo();
app_ensure_tables($pdo);

// --- Sanitize ?next= (must be internal path only) ---
function sanitize_next(string $n): string {
  $n = trim($n);
  // If next is empty, defaults to the new dashboard
  if ($n === '' || strpos($n, '/') !== 0 || stripos($n, '://') !== false || $n === '/rireki/') {
    return '/rireki/user_data.php';
  }
  return $n;
}

$next = sanitize_next((string)($_GET['next'] ?? ''));
$fmt  = isset($_GET['fmt']) ? preg_replace('/[^a-z]/i', '', $_GET['fmt']) : 'basic';

// --- If already logged in, go straight to destination ---
if (app_is_logged_in()) {
  $uid = (int)($_SESSION['app_user_id'] ?? 0);
  // Default override: if they hit login page while logged in, take them to dashboard 
  // (unless they specifically requested an editor form like rireki.php?edit=1)
  if ($next === '/rireki/' || strpos($next, '/php/rireki_preview.php') !== false) {
    header('Location: /rireki/user_data.php', true, 302);
    exit;
  }
  header('Location: ' . $next, true, 302);
  exit;
}


function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$err  = '';
$ok   = '';
$tab  = 'login'; // default tab

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $mode      = (string)($_POST['mode'] ?? '');
  $postNext  = sanitize_next((string)($_POST['next'] ?? ''));
  $next      = $postNext;

  if ($mode === 'register') {
    $tab      = 'register';
    $username = trim((string)($_POST['username'] ?? ''));
    $email    = trim((string)($_POST['email'] ?? ''));
    $pass     = (string)($_POST['password'] ?? '');
    $agreed   = isset($_POST['agree']) && $_POST['agree'] === '1';

    if ($username === '' || $email === '' || $pass === '') {
      $err = '全て入力してください。';
    } elseif (!$agreed) {
      $err = '個人情報の取扱いに同意してください。';
    } elseif (strlen($pass) < 8) {
      $err = 'パスワードは8文字以上で設定してください。';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      try {
        $stmt = $pdo->prepare("INSERT INTO " . APP_TBL_USERS . " (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $uid = (int)$pdo->lastInsertId();
        app_login_user_id($uid);
        header('Location: ' . $next, true, 302);
        exit;
      } catch (Throwable $e) {
        $err = '登録に失敗しました（同じユーザー名/メールが既に存在する可能性があります）。';
      }
    }

  } elseif ($mode === 'login') {
    $tab   = 'login';
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash FROM " . APP_TBL_USERS . " WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($pass, (string)$row['password_hash'])) {
      $err = 'メールアドレスまたはパスワードが違います。';
    } else {
      $uid = (int)$row['id'];
      app_login_user_id($uid);

      // Force to user_data.php if they just logged in from general buttons
      if ($next === '/rireki/' || strpos($next, '/php/rireki_preview.php') !== false) {
          header('Location: /rireki/user_data.php', true, 302);
      } else {
          header('Location: ' . $next, true, 302);
      }
      exit;
    }
  }
}
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ログイン・新規登録｜オンライン履歴書メーカー</title>
  <meta name="robots" content="noindex,follow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
  <style>
    /* ===== Reset & Base ===== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sky:  #3b9eff;
      --sky2: #1e78e8;
      --ink:  #0d1117;
      --muted:#8b949e;
      --card: rgba(255,255,255,0.07);
      --card-solid: rgba(255,255,255,0.95);
      --border: rgba(255,255,255,0.15);
      --danger:#f85149;
      --ok:   #3fb950;
    }

    html, body {
      min-height: 100%;
      font-family: 'Inter', 'Noto Sans JP', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
    }

    body {
      background:
        radial-gradient(ellipse 80% 60% at 50% -10%, rgba(59,158,255,.28) 0%, transparent 70%),
        radial-gradient(ellipse 60% 40% at 80% 90%,  rgba(100,60,220,.22) 0%, transparent 60%),
        linear-gradient(155deg, #060d1a 0%, #0d1f3c 45%, #091428 100%);
      background-attachment: fixed;
      color: #e6edf3;
      display: flex;
      flex-direction: column;
      min-height: 100dvh;
    }

    /* ===== Animated stars (decorative) ===== */
    .stars {
      position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden;
    }
    .star {
      position: absolute; border-radius: 50%;
      background: rgba(255,255,255,0.75);
      animation: twinkle var(--d, 4s) ease-in-out infinite alternate;
    }
    @keyframes twinkle { from { opacity:.1; transform:scale(1) } to { opacity:.8; transform:scale(1.4) } }

    /* ===== Page header ===== */
    .page-header {
      position: relative; z-index: 2;
      padding: 22px 24px 0;
      display: flex; align-items: center; gap: 12px;
    }
    .page-header a {
      color: rgba(255,255,255,.6); text-decoration: none; font-size: 13px;
      display: flex; align-items: center; gap: 6px;
      transition: color .2s;
    }
    .page-header a:hover { color: #fff; }
    .logo-mark {
      width: 34px; height: 34px; border-radius: 10px;
      background: linear-gradient(135deg, var(--sky), #7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 16px; color: #fff; letter-spacing: -1px;
    }

    /* ===== Main layout ===== */
    main {
      position: relative; z-index: 2;
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 32px 16px 48px;
    }

    /* ===== The glass card ===== */
    .auth-card {
      width: 100%; max-width: 460px;
      background: rgba(13,17,27,0.72);
      backdrop-filter: blur(24px) saturate(180%);
      -webkit-backdrop-filter: blur(24px) saturate(180%);
      border: 1px solid rgba(255,255,255,.11);
      border-radius: 24px;
      box-shadow:
        0 0 0 1px rgba(59,158,255,.08),
        0 32px 80px rgba(0,0,0,.55),
        inset 0 1px 0 rgba(255,255,255,.08);
      overflow: hidden;
      animation: slideUp .45s cubic-bezier(.22,.8,.4,1) both;
    }
    @keyframes slideUp { from { opacity:0; transform:translateY(28px) } to { opacity:1; transform:none } }

    /* ===== Tab header ===== */
    .tab-header {
      display: grid; grid-template-columns: 1fr 1fr;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .tab-btn {
      padding: 18px 0; text-align: center;
      cursor: pointer; border: none; background: transparent;
      color: var(--muted); font-size: 15px; font-weight: 700;
      font-family: inherit;
      position: relative; transition: color .2s;
    }
    .tab-btn::after {
      content: '';
      position: absolute; bottom: 0; left: 20%; right: 20%; height: 2px;
      border-radius: 2px;
      background: var(--sky);
      transform: scaleX(0); transition: transform .25s cubic-bezier(.4,0,.2,1);
    }
    .tab-btn.active { color: #e6edf3; }
    .tab-btn.active::after { transform: scaleX(1); }

    /* ===== Card body ===== */
    .card-body { padding: 28px 28px 22px; }

    .pane { display: none; }
    .pane.active { display: block; animation: fadeIn .25s ease both; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(6px) } to { opacity:1; transform:none } }

    /* ===== Brand area inside card ===== */
    .brand {
      text-align: center; margin-bottom: 22px;
    }
    .brand-icon {
      width: 54px; height: 54px; border-radius: 16px; margin: 0 auto 12px;
      background: linear-gradient(135deg, var(--sky), #7c3aed);
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; color: #fff;
      box-shadow: 0 8px 24px rgba(59,158,255,.35);
    }
    .brand h1 { font-size: 20px; font-weight: 900; color: #e6edf3; line-height: 1.3; }
    .brand p  { font-size: 13px; color: var(--muted); margin-top: 4px; line-height: 1.5; }

    /* ===== Form fields ===== */
    .field { margin-bottom: 14px; }
    .field label {
      display: block; font-size: 12px; font-weight: 700;
      color: rgba(230,237,243,.7); margin-bottom: 6px; letter-spacing: .3px;
      text-transform: uppercase;
    }
    .field input[type="text"],
    .field input[type="email"],
    .field input[type="password"] {
      width: 100%; padding: 11px 14px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 10px;
      color: #e6edf3; font-size: 15px; font-family: inherit;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .field input:focus {
      border-color: var(--sky);
      background: rgba(59,158,255,.08);
      box-shadow: 0 0 0 3px rgba(59,158,255,.2);
    }
    .field input::placeholder { color: rgba(139,148,158,.6); }

    /* Password toggle wrapper */
    .pw-wrap { position: relative; }
    .pw-wrap input { padding-right: 44px; }
    .pw-toggle {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; padding: 4px;
      color: var(--muted); transition: color .2s;
      line-height: 0;
    }
    .pw-toggle:hover { color: #e6edf3; }

    /* ===== Consent checkbox ===== */
    .consent {
      display: flex; align-items: flex-start; gap: 10px;
      margin: 14px 0;
      padding: 12px;
      background: rgba(59,158,255,.05);
      border: 1px solid rgba(59,158,255,.15);
      border-radius: 10px;
    }
    .consent input[type="checkbox"] {
      width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px;
      accent-color: var(--sky); cursor: pointer;
    }
    .consent span { font-size: 12px; color: rgba(230,237,243,.75); line-height: 1.5; }
    .consent a { color: var(--sky); text-decoration: none; }
    .consent a:hover { text-decoration: underline; }

    /* ===== Buttons ===== */
    .btn-primary {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, var(--sky), var(--sky2));
      border: none; border-radius: 12px;
      color: #fff; font-size: 15px; font-weight: 800; font-family: inherit;
      cursor: pointer; text-decoration: none;
      box-shadow: 0 4px 18px rgba(59,158,255,.4);
      transition: filter .2s, transform .15s, box-shadow .2s;
    }
    .btn-primary:hover  { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 8px 28px rgba(59,158,255,.5); }
    .btn-primary:active { transform: translateY(0); filter: brightness(.96); }
    .btn-primary:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    /* ===== Error / ok messages ===== */
    .msg {
      margin: 0 0 16px; padding: 10px 14px;
      border-radius: 10px; font-size: 13px; font-weight: 600;
    }
    .msg.err { background: rgba(248,81,73,.12); border: 1px solid rgba(248,81,73,.3); color: #ff7b6b; }
    .msg.ok  { background: rgba(63,185,80,.12); border: 1px solid rgba(63,185,80,.3); color: #56d364; }

    /* ===== Divider ===== */
    .divider {
      margin: 18px 0 14px; position: relative;
      text-align: center; color: var(--muted); font-size: 12px;
    }
    .divider::before, .divider::after {
      content: ''; position: absolute; top: 50%;
      width: calc(50% - 28px); height: 1px;
      background: rgba(255,255,255,.08);
    }
    .divider::before { left: 0; }
    .divider::after  { right: 0; }

    /* ===== Step badge ===== */
    .step-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(59,158,255,.12); border: 1px solid rgba(59,158,255,.25);
      color: #7dc8ff; border-radius: 999px; padding: 4px 12px;
      font-size: 12px; font-weight: 700; margin-bottom: 16px;
    }
    .step-badge svg { flex-shrink: 0; }

    /* ===== Feature chips below card ===== */
    .chips {
      display: flex; flex-wrap: wrap; gap: 8px; justify-content: center;
      margin-top: 20px; z-index: 2; position: relative;
    }
    .chip {
      font-size: 12px; padding: 5px 12px; border-radius: 999px;
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
      color: rgba(230,237,243,.65);
    }

    /* ===== Footer ===== */
    .page-footer {
      position: relative; z-index: 2;
      text-align: center; padding: 16px; font-size: 11px; color: var(--muted);
    }
    .page-footer a { color: rgba(139,148,158,.8); text-decoration: none; }
    .page-footer a:hover { color: #e6edf3; }

    /* ===== Responsive ===== */
    @media (max-width: 500px) {
      .card-body { padding: 22px 18px 18px; }
      .brand h1 { font-size: 17px; }
    }
  </style>
</head>
<body>

  <!-- Decorative star field -->
  <div class="stars" aria-hidden="true" id="stars"></div>

  <!-- Page header -->
  <header class="page-header">
    <div class="logo-mark">ITF</div>
    <a href="/rireki/">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      フォーマット選択へ戻る
    </a>
  </header>

  <main>
    <div style="width:100%;max-width:460px">

      <!-- Auth card -->
      <div class="auth-card">

        <!-- Tabs -->
        <div class="tab-header" role="tablist">
          <button class="tab-btn <?php echo ($tab==='login'?'active':''); ?>" id="tabLogin" role="tab"
            aria-selected="<?php echo ($tab==='login'?'true':'false'); ?>" aria-controls="paneLogin"
            onclick="switchTab('login')">
            ログイン
          </button>
          <button class="tab-btn <?php echo ($tab==='register'?'active':''); ?>" id="tabRegister" role="tab"
            aria-selected="<?php echo ($tab==='register'?'true':'false'); ?>" aria-controls="paneRegister"
            onclick="switchTab('register')">
            新規登録（無料）
          </button>
        </div>

        <div class="card-body">

          <!-- Error message -->
          <?php if ($err): ?>
            <div class="msg err" role="alert"><?= h($err) ?></div>
          <?php endif; ?>
          <?php if ($ok):  ?>
            <div class="msg ok"><?= h($ok) ?></div>
          <?php endif; ?>

          <!-- Brand -->
          <div class="brand">
            <div class="brand-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
              </svg>
            </div>
            <h1>オンライン履歴書メーカー</h1>
            <p>履歴書フォームを利用するには、アカウントが必要です</p>
          </div>

          <!-- Step badge -->
          <div style="text-align:center">
            <span class="step-badge">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              STEP 1 of 3 — ログイン / 登録
            </span>
          </div>

          <!-- ===================== LOGIN PANE ===================== -->
          <div class="pane <?php echo ($tab==='login'?'active':''); ?>" id="paneLogin" role="tabpanel" aria-labelledby="tabLogin">
            <form method="post" action="/php/user_login.php?next=<?= h($next) ?>">
              <input type="hidden" name="mode" value="login">
              <input type="hidden" name="next" value="<?= h($next) ?>">

              <div class="field">
                <label for="login_email">メールアドレス</label>
                <input type="email" id="login_email" name="email" required
                  placeholder="taro@example.com" autocomplete="email">
              </div>

              <div class="field">
                <label for="login_pass">パスワード</label>
                <div class="pw-wrap">
                  <input type="password" id="login_pass" name="password" required
                    placeholder="••••••••" autocomplete="current-password" minlength="8">
                  <button type="button" class="pw-toggle" onclick="togglePw('login_pass',this)" aria-label="パスワードを表示">
                    <svg id="eyeLogin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>

              <div style="margin-top:20px">
                <button class="btn-primary" type="submit" id="loginBtn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                  ログインして続ける
                </button>
              </div>

              <div class="divider">または</div>

              <p style="text-align:center;font-size:13px;color:var(--muted)">
                まだアカウントをお持ちでない方は
                <button type="button" onclick="switchTab('register')"
                  style="background:none;border:none;color:#7dc8ff;cursor:pointer;font-size:13px;font-weight:700;padding:0">
                  新規登録（無料）
                </button>
              </p>
            </form>
          </div>

          <!-- ===================== REGISTER PANE ===================== -->
          <div class="pane <?php echo ($tab==='register'?'active':''); ?>" id="paneRegister" role="tabpanel" aria-labelledby="tabRegister">
            <form method="post" action="/php/user_login.php?next=<?= h($next) ?>" id="regForm">
              <input type="hidden" name="mode" value="register">
              <input type="hidden" name="next" value="<?= h($next) ?>">
              <!-- Honeypot -->
              <input type="text" name="company" value="" style="position:absolute;left:-9999px;top:-9999px" tabindex="-1" aria-hidden="true" autocomplete="off">

              <div class="field">
                <label for="reg_username">ユーザー名</label>
                <input type="text" id="reg_username" name="username" required
                  placeholder="yamada_taro" autocomplete="username" maxlength="60">
              </div>

              <div class="field">
                <label for="reg_email">メールアドレス</label>
                <input type="email" id="reg_email" name="email" required
                  placeholder="taro@example.com" autocomplete="email">
              </div>

              <div class="field">
                <label for="reg_pass">パスワード（8文字以上）</label>
                <div class="pw-wrap">
                  <input type="password" id="reg_pass" name="password" required
                    placeholder="•••••••• （8文字以上）" autocomplete="new-password" minlength="8">
                  <button type="button" class="pw-toggle" onclick="togglePw('reg_pass',this)" aria-label="パスワードを表示">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>

              <!-- Consent (replaces terms page) -->
              <label class="consent">
                <input type="checkbox" name="agree" value="1" id="agreeChk">
                <span>
                  <a href="https://it-future.jp/privacy.html" target="_blank" rel="noopener">プライバシーポリシー</a>・
                  <strong>個人情報の取扱い</strong>（入力情報は履歴書作成のみに使用）に同意します。
                </span>
              </label>

              <div style="margin-top:16px">
                <button class="btn-primary" type="submit" id="regBtn" disabled>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                  登録して履歴書フォームへ
                </button>
              </div>

              <p style="margin-top:12px;text-align:center;font-size:12px;color:var(--muted)">
                既にアカウントをお持ちの方は
                <button type="button" onclick="switchTab('login')"
                  style="background:none;border:none;color:#7dc8ff;cursor:pointer;font-size:12px;font-weight:700;padding:0">
                  ログイン
                </button>
              </p>
            </form>
          </div>

        </div><!-- /.card-body -->
      </div><!-- /.auth-card -->

      <!-- Feature chips -->
      <div class="chips" aria-hidden="true">
        <span class="chip">🔒 安全な暗号化</span>
        <span class="chip">📄 Excel出力対応</span>
        <span class="chip">📱 スマホOK</span>
        <span class="chip">⚡ 最短5分</span>
      </div>

    </div>
  </main>

  <footer class="page-footer">
    <a href="https://it-future.jp/">株式会社アイティーエフ</a> &nbsp;|&nbsp;
    <a href="https://it-future.jp/privacy.html">プライバシーポリシー</a>
  </footer>

  <script>
  // ===== Star field =====
  (function () {
    const wrap = document.getElementById('stars');
    if (!wrap) return;
    for (let i = 0; i < 60; i++) {
      const s = document.createElement('div');
      s.className = 'star';
      const size = Math.random() * 2 + 1;
      s.style.cssText = `
        width:${size}px;height:${size}px;
        top:${Math.random()*100}%;left:${Math.random()*100}%;
        --d:${(Math.random()*4+2).toFixed(1)}s;
        animation-delay:-${(Math.random()*6).toFixed(1)}s;
      `;
      wrap.appendChild(s);
    }
  })();

  // ===== Tab switch =====
  function switchTab(which) {
    ['login','register'].forEach(t => {
      document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1))
        ?.classList.toggle('active', t === which);
      document.getElementById('pane' + t.charAt(0).toUpperCase() + t.slice(1))
        ?.classList.toggle('active', t === which);
    });
    // update aria
    document.getElementById('tabLogin').setAttribute('aria-selected', which==='login');
    document.getElementById('tabRegister').setAttribute('aria-selected', which==='register');
  }

  // ===== Password toggle =====
  function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const isText = inp.type === 'text';
    inp.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
  }

  // ===== Register button enable/disable (consent) =====
  (function () {
    const chk = document.getElementById('agreeChk');
    const btn = document.getElementById('regBtn');
    if (!chk || !btn) return;
    function sync() { btn.disabled = !chk.checked; }
    chk.addEventListener('change', sync);
    sync();
  })();
  </script>
</body>
</html>
