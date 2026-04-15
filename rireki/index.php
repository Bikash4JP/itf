<?php
// /home/it-future/www/itf/rireki/index.php

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

$loggedIn = app_is_logged_in();
$pdo      = null;
$user     = null;
$profiles = [];

if ($loggedIn) {
    try {
        $pdo  = app_pdo();
        app_ensure_tables($pdo);
        $user = app_current_user($pdo);

        // Load saved profiles (basic + kaigo)
        foreach (['basic', 'kaigo'] as $fmt) {
            $p = app_load_profile($pdo, app_user_id(), $fmt);
            if ($p) $profiles[$fmt] = $p;
        }
    } catch (Throwable $e) { $loggedIn = false; }
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function username(): string {
    global $user;
    return $user ? h($user['username']) : '';
}
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="format-detection" content="telephone=no">
  <title>【無料】履歴書作成ツール（スマホ対応）｜オンラインで5分・Excelダウンロード</title>
  <meta name="description" content="【無料】履歴書をオンラインで作成。スマホ/PC対応で最短5分、Excel（.xls）でダウンロード可能。標準/介護テンプレ対応。">
  <link rel="canonical" href="https://it-future.jp/rireki/">
  <meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large">
  <link rel="alternate" hreflang="ja-JP" href="https://it-future.jp/rireki/">
  <link rel="icon" href="https://it-future.jp/images/favicon-32x32.png" sizes="32x32">
  <link rel="apple-touch-icon" href="https://it-future.jp/images/apple-touch-icon.png">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ja_JP">
  <meta property="og:title" content="【無料】履歴書作成ツール（スマホ対応）">
  <meta property="og:description" content="最短5分で履歴書をオンライン作成。無料・Excel出力・テンプレ対応・スマホOK。">
  <meta property="og:url" content="https://it-future.jp/rireki/">
  <meta property="og:image" content="https://it-future.jp/images/og/rireki_og.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">

  <style>
    /* ===== Reset ===== */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sky: #3b9eff; --sky2: #1e78e8; --violet: #7c3aed;
      --ink: #e6edf3; --muted: #8b949e; --border: rgba(255,255,255,.1);
      --card: rgba(255,255,255,.06); --card-hover: rgba(255,255,255,.1);
      --ok: #3fb950; --warn: #e3b341; --danger: #f85149;
    }
    html, body { min-height: 100%; font-family: 'Inter','Noto Sans JP',system-ui,sans-serif; }
    body {
      background:
        radial-gradient(ellipse 80% 55% at 50% -5%, rgba(59,158,255,.22) 0%, transparent 65%),
        radial-gradient(ellipse 55% 40% at 85% 95%, rgba(124,58,237,.18) 0%, transparent 60%),
        linear-gradient(155deg, #060d1a 0%, #0d1f3c 50%, #080f20 100%);
      background-attachment: fixed;
      color: var(--ink);
    }
    a { color: inherit; text-decoration: none; }

    /* ===== Stars ===== */
    #stars { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
    .star { position: absolute; border-radius: 50%; background: rgba(255,255,255,.8);
      animation: twinkle var(--d,4s) ease-in-out infinite alternate; }
    @keyframes twinkle { from{opacity:.08;transform:scale(1)} to{opacity:.7;transform:scale(1.5)} }

    /* ===== Nav ===== */
    .topnav {
      position: sticky; top: 0; z-index: 50;
      background: rgba(6,13,26,.82); backdrop-filter: blur(18px) saturate(200%);
      border-bottom: 1px solid var(--border);
      padding: 0 24px; height: 64px;
      display: flex; align-items: center; gap: 16px;
    }
    .nav-logo { display: flex; align-items: center; gap: 10px; }
    .nav-logo-mark {
      width: 36px; height: 36px; border-radius: 10px;
      background: linear-gradient(135deg, var(--sky), var(--violet));
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 14px; color: #fff; letter-spacing: -1px;
      box-shadow: 0 4px 14px rgba(59,158,255,.4);
      overflow: hidden;
    }
    .nav-logo-mark img { width: 100%; height: 100%; object-fit: cover; }
    .nav-logo-text { font-weight: 800; font-size: 15px; color: var(--ink); }
    .nav-logo-sub  { font-size: 11px; color: var(--muted); font-weight: 400; }
    .nav-spacer { flex: 1; }
    .nav-links { display: flex; align-items: center; gap: 12px; }
    .nav-link {
      font-size: 13px; font-weight: 600; color: var(--muted); padding: 6px 12px;
      border-radius: 8px; transition: color .2s, background .2s;
    }
    .nav-link:hover { color: var(--ink); background: rgba(255,255,255,.07); }
    .nav-btn {
      font-size: 13px; font-weight: 700; padding: 8px 18px; border-radius: 10px;
      border: 1px solid var(--border); background: rgba(255,255,255,.08);
      color: var(--ink); cursor: pointer; transition: background .2s, border-color .2s;
      font-family: inherit;
    }
    .nav-btn:hover { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.2); }
    .nav-btn.primary {
      background: linear-gradient(135deg, var(--sky), var(--sky2));
      border-color: var(--sky); color: #fff;
      box-shadow: 0 4px 14px rgba(59,158,255,.35);
    }
    .nav-btn.primary:hover { filter: brightness(1.1); }
    .nav-user {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: var(--muted);
    }
    .nav-user-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: linear-gradient(135deg, var(--sky), var(--violet));
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 12px; color: #fff;
    }
    @media(max-width:600px){ .nav-link{ display:none } }

    /* ===== Page wrap ===== */
    .wrap { max-width: 1100px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 2; }

    /* ===== Hero ===== */
    .hero {
      padding: 72px 0 52px;
      display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 40px; align-items: center;
    }
    @media(max-width:900px){ .hero{ grid-template-columns:1fr; padding:48px 0 36px; } }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(59,158,255,.12); border: 1px solid rgba(59,158,255,.25);
      color: #7dc8ff; border-radius: 999px; padding: 4px 14px; font-size: 12px; font-weight: 700;
      margin-bottom: 18px; letter-spacing: .3px;
    }
    .hero h1 {
      font-size: clamp(26px, 4vw, 42px); font-weight: 900; line-height: 1.22;
      letter-spacing: -.5px;
      background: linear-gradient(135deg, #e6edf3 30%, #7dc8ff 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .hero-lead { margin: 14px 0 24px; color: var(--muted); line-height: 1.75; font-size: 15px; }
    .hero-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 28px; }
    .chip {
      font-size: 12px; padding: 5px 12px; border-radius: 999px;
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
      color: rgba(230,237,243,.7);
    }
    .hero-cta { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-primary {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 14px 26px; border-radius: 12px;
      background: linear-gradient(135deg, var(--sky), var(--sky2));
      border: none; color: #fff; font-weight: 800; font-size: 15px; font-family: inherit;
      cursor: pointer; text-decoration: none;
      box-shadow: 0 6px 24px rgba(59,158,255,.45);
      transition: filter .2s, transform .15s, box-shadow .2s;
    }
    .btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 10px 30px rgba(59,158,255,.55); }
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 13px 22px; border-radius: 12px;
      background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.14);
      color: var(--ink); font-weight: 700; font-size: 15px; font-family: inherit;
      cursor: pointer; text-decoration: none;
      transition: background .2s, border-color .2s;
    }
    .btn-ghost:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.22); }

    /* Hero image mock */
    .hero-visual {
      border-radius: 20px; overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: 0 20px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(59,158,255,.1);
      background: rgba(255,255,255,.04);
      position: relative;
    }
    .hero-visual img { display: block; width: 100%; height: auto; }
    .hero-visual-glow {
      position: absolute; inset: 0; pointer-events: none;
      background: linear-gradient(135deg, rgba(59,158,255,.08) 0%, transparent 60%);
    }
    @media(max-width:900px){ .hero-visual{ display:none } }

    /* ===== Returning user section ===== */
    .section-label {
      font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--sky); margin-bottom: 10px;
    }
    .section-title { font-size: 22px; font-weight: 900; margin-bottom: 6px; }
    .section-sub { font-size: 14px; color: var(--muted); margin-bottom: 28px; }

    .user-section { padding: 40px 0 20px; }

    .resume-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }

    .resume-card {
      background: rgba(255,255,255,.05);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 18px; padding: 22px;
      transition: border-color .25s, background .25s, transform .2s, box-shadow .2s;
      position: relative; overflow: hidden;
    }
    .resume-card::before {
      content: ''; position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(59,158,255,.06) 0%, transparent 70%);
      pointer-events: none;
    }
    .resume-card:hover {
      border-color: rgba(59,158,255,.35); background: rgba(59,158,255,.07);
      transform: translateY(-3px); box-shadow: 0 12px 40px rgba(0,0,0,.3);
    }
    .resume-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .resume-icon {
      width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .resume-icon.basic   { background: rgba(59,158,255,.15); }
    .resume-icon.kaigo   { background: rgba(63,185,80,.15); }
    .resume-card-meta h3 { font-size: 16px; font-weight: 800; }
    .resume-card-meta p  { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .resume-card-actions { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
    .action-btn {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 7px 14px; border-radius: 9px; font-size: 13px; font-weight: 700;
      border: 1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.07);
      color: var(--ink); cursor: pointer; text-decoration: none;
      transition: background .2s, border-color .2s;
    }
    .action-btn:hover { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.22); }
    .action-btn.sky   { background: rgba(59,158,255,.15); border-color: rgba(59,158,255,.3); color: #7dc8ff; }
    .action-btn.sky:hover { background: rgba(59,158,255,.25); }
    .action-btn.green { background: rgba(63,185,80,.15); border-color: rgba(63,185,80,.3); color: #56d364; }
    .action-btn.green:hover { background: rgba(63,185,80,.25); }

    .empty-card {
      border: 2px dashed rgba(255,255,255,.1); border-radius: 18px; padding: 36px 22px;
      text-align: center; color: var(--muted);
    }
    .empty-card .icon { font-size: 36px; margin-bottom: 12px; }
    .empty-card p { font-size: 14px; line-height: 1.6; margin-bottom: 18px; }

    /* ===== Notice ===== */
    .notice {
      display: flex; gap: 10px; align-items: flex-start;
      background: rgba(227,179,65,.08); border: 1px solid rgba(227,179,65,.2);
      border-radius: 12px; padding: 14px 16px; margin: 24px 0;
      font-size: 14px; line-height: 1.6; color: #e3b341;
    }
    .notice svg { flex-shrink: 0; margin-top: 2px; }

    /* ===== Format grid ===== */
    .formats-section { padding: 20px 0 48px; }
    .format-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
    }
    @media(max-width:980px){ .format-grid{ grid-template-columns:1fr 1fr } }
    @media(max-width:660px){ .format-grid{ grid-template-columns:1fr } }

    .format-card {
      background: rgba(255,255,255,.05); border: 1px solid var(--border);
      border-radius: 20px; overflow: hidden;
      transition: border-color .25s, transform .2s, box-shadow .2s;
      position: relative;
    }
    .format-card:hover {
      border-color: rgba(59,158,255,.35); transform: translateY(-4px);
      box-shadow: 0 16px 48px rgba(0,0,0,.35);
    }
    .format-thumb {
      aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 22px; letter-spacing: 1px;
    }
    .format-thumb.basic  { background: linear-gradient(135deg, rgba(59,158,255,.2), rgba(59,158,255,.05)); color: #7dc8ff; }
    .format-thumb.kaigo  { background: linear-gradient(135deg, rgba(63,185,80,.2), rgba(63,185,80,.05)); color: #56d364; }
    .format-thumb.soon   { background: linear-gradient(135deg, rgba(124,58,237,.2), rgba(124,58,237,.05)); color: #a78bfa; opacity: .7; }
    .format-body { padding: 18px; }
    .format-body h2 { font-size: 17px; font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .format-tag {
      font-size: 11px; padding: 2px 8px; border-radius: 999px;
      background: rgba(59,158,255,.15); border: 1px solid rgba(59,158,255,.25); color: #7dc8ff;
    }
    .format-tag.green { background: rgba(63,185,80,.15); border-color: rgba(63,185,80,.25); color: #56d364; }
    .format-body p { font-size: 13px; color: var(--muted); line-height: 1.6; }
    .format-cta { padding: 0 18px 20px; }
    .format-soon-badge {
      position: absolute; top: 10px; right: 10px;
      background: rgba(124,58,237,.3); border: 1px solid rgba(124,58,237,.4);
      color: #c4b5fd; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    }

    /* ===== Howto ===== */
    .howto-section { padding: 20px 0 48px; }
    .steps-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    @media(max-width:860px){ .steps-row{ grid-template-columns:1fr 1fr } }
    @media(max-width:560px){ .steps-row{ grid-template-columns:1fr } }
    .step-item {
      background: rgba(255,255,255,.04); border: 1px solid var(--border);
      border-radius: 16px; padding: 20px 16px; text-align: center;
    }
    .step-num {
      width: 36px; height: 36px; border-radius: 50%; margin: 0 auto 12px;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(135deg, var(--sky), var(--violet));
      font-weight: 900; font-size: 15px; color: #fff;
    }
    .step-item h3 { font-size: 14px; font-weight: 800; margin-bottom: 6px; }
    .step-item p  { font-size: 12px; color: var(--muted); line-height: 1.55; }

    /* ===== FAQ ===== */
    .faq-section { padding: 0 0 56px; }
    .faq-list { display: flex; flex-direction: column; gap: 8px; }
    details.faq-item {
      background: rgba(255,255,255,.04); border: 1px solid var(--border);
      border-radius: 14px; overflow: hidden;
    }
    details.faq-item[open] { border-color: rgba(59,158,255,.25); background: rgba(59,158,255,.05); }
    summary.faq-q {
      cursor: pointer; padding: 16px 20px; font-weight: 700; font-size: 14px;
      list-style: none; display: flex; justify-content: space-between; align-items: center;
    }
    summary.faq-q::after { content: '+'; font-size: 20px; font-weight: 400; color: var(--sky); flex-shrink: 0; }
    details[open] summary.faq-q::after { content: '−'; }
    .faq-a { padding: 0 20px 16px; font-size: 13px; color: var(--muted); line-height: 1.65; }

    /* ===== Divider ===== */
    .section-divider {
      height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent);
      margin: 8px 0;
    }

    /* ===== Footer ===== */
    footer.footer { background: rgba(0,0,0,.4); border-top: 1px solid var(--border); }

    /* ===== Returning user hero bar ===== */
    .hero-return-bar {
      margin-top: 22px;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .return-pill {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 18px; border-radius: 12px;
      background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
      font-size: 13px; color: var(--muted);
    }
    .return-pill a {
      color: #7dc8ff; font-weight: 700; text-decoration: none;
      transition: color .2s;
    }
    .return-pill a:hover { color: #aad8ff; text-decoration: underline; }
    .btn-preview {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 11px 22px; border-radius: 12px;
      background: linear-gradient(135deg, rgba(124,58,237,.35), rgba(59,158,255,.35));
      border: 1px solid rgba(124,58,237,.45);
      color: #c4b5fd; font-weight: 800; font-size: 14px;
      text-decoration: none; cursor: pointer;
      box-shadow: 0 4px 20px rgba(124,58,237,.25), inset 0 1px 0 rgba(255,255,255,.1);
      transition: filter .2s, transform .15s, box-shadow .2s;
    }
    .btn-preview:hover {
      filter: brightness(1.15); transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(124,58,237,.4);
    }
    .btn-edit-resume {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 10px 18px; border-radius: 11px;
      background: rgba(59,158,255,.12); border: 1px solid rgba(59,158,255,.25);
      color: #7dc8ff; font-weight: 700; font-size: 13px;
      text-decoration: none;
      transition: background .2s, border-color .2s;
    }
    .btn-edit-resume:hover { background: rgba(59,158,255,.22); border-color: rgba(59,158,255,.4); }
    .logged-in-hero-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  </style>

  <script type="application/ld+json">
  {"@context":"https://schema.org","@type":"WebApplication","name":"ITF オンライン履歴書メーカー","url":"https://it-future.jp/rireki/","applicationCategory":"BusinessApplication","operatingSystem":"Web","inLanguage":"ja-JP","offers":{"@type":"Offer","price":"0","priceCurrency":"JPY"}}
  </script>
</head>
<body>
<div id="stars" aria-hidden="true"></div>

<!-- ===== NAV ===== -->
<nav class="topnav">
  <div class="nav-logo">
    <div class="nav-logo-mark">
      <img src="https://it-future.jp/images/android-chrome-192x192.png" alt="ITF" onerror="this.style.display='none';this.parentElement.textContent='ITF'">
    </div>
    <div>
      <div class="nav-logo-text">ITF 履歴書メーカー</div>
      <div class="nav-logo-sub">オンライン・無料・最短5分</div>
    </div>
  </div>

  <div class="nav-spacer"></div>
  <div class="nav-links">
    <a class="nav-link" href="https://it-future.jp/">ホーム</a>
    <a class="nav-link" href="https://it-future.jp/saiyou.php">求人を見る</a>

    <?php if ($loggedIn && $user): ?>
      <div class="nav-user">
        <div class="nav-user-avatar"><?= mb_strtoupper(mb_substr($user['username'], 0, 1, 'UTF-8'), 'UTF-8') ?></div>
        <span style="font-size:13px;font-weight:600"><?= username() ?></span>
      </div>
      <a class="nav-btn" href="/php/user_logout.php" style="font-size:12px;padding:7px 14px">ログアウト</a>
    <?php else: ?>
      <a class="nav-btn" href="/php/user_login.php?next=/rireki/">ログイン</a>
      <a class="nav-btn primary" href="/php/user_login.php?next=/rireki/">無料登録</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ===== HERO ===== -->
<section class="wrap">
  <div class="hero">
    <div>
      <div class="hero-eyebrow">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        完全無料 · Excel出力 · スマホ対応
      </div>
      <h1>オンラインで5分。<br>プロ品質の<br>履歴書を作成</h1>
      <p class="hero-lead">
        インストール不要。入力するだけで<strong style="color:#7dc8ff">Excelダウンロード</strong>まで完結。<br>
        標準・介護業界向けテンプレ対応。
      </p>
      <div class="hero-chips">
        <span class="chip">✅ 無料</span>
        <span class="chip">📄 Excel出力</span>
        <span class="chip">📱 スマホOK</span>
        <span class="chip">🌏 介護向けテンプレ</span>
        <span class="chip">⚡ 最短5分</span>
      </div>
      <div class="hero-cta">
        <a class="btn-primary" href="/php/user_login.php?next=/rireki/basic/rireki.php&fmt=basic">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          今すぐ無料作成
        </a>
        <a class="btn-ghost" href="https://it-future.jp/saiyou.php">
          求人を見ながら作成
        </a>
      </div>

      <!-- Returning user bar -->
      <?php if ($loggedIn && $user): ?>
      <div class="hero-return-bar">
        <div class="logged-in-hero-row">
          <a class="btn-preview" href="/rireki/kaigo/php/rireki_preview.php?flow=profile_only">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            履歴書をプレビュー
          </a>
          <a class="btn-edit-resume" href="/rireki/kaigo/rireki.php">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            介護向けを編集
          </a>
          <a class="btn-edit-resume" href="/rireki/basic/rireki.php" style="color:#56d364;background:rgba(63,185,80,.1);border-color:rgba(63,185,80,.25)">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            標準フォームを編集
          </a>
        </div>
      </div>
      <?php else: ?>
      <div class="hero-return-bar">
        <div class="return-pill">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          過去に作成しましたか？変更をご希望ですか？
          <a href="/php/user_login.php?next=<?= urlencode('/rireki/kaigo/php/rireki_preview.php?flow=profile_only') ?>">ログインして続ける →</a>
        </div>
      </div>
      <?php endif; ?>

    </div>
    <div class="hero-visual">
      <img src="https://it-future.jp/rireki/images/basicRireki_sample.png"
           alt="オンライン履歴書メーカーのプレビュー" loading="eager" fetchpriority="high" width="720" height="480">
      <div class="hero-visual-glow"></div>
    </div>
  </div>
</section>

<!-- ===== RETURNING USER SECTION ===== -->
<?php if ($loggedIn && $user): ?>
<section class="wrap user-section">
  <div class="section-divider"></div>
  <br>
  <div class="section-label">📋 マイ履歴書</div>
  <div class="section-title">おかえりなさい、<?= username() ?>さん</div>
  <div class="section-sub">保存された履歴書を編集・ダウンロードできます。</div>

  <?php if (empty($profiles)): ?>
    <div class="resume-grid">
      <div class="empty-card">
        <div class="icon">📄</div>
        <p>まだ履歴書が作成されていません。<br>下のフォーマットから始めてください。</p>
        <a class="btn-primary" href="/php/user_login.php?next=/rireki/basic/rireki.php&fmt=basic" style="display:inline-flex">
          今すぐ作成する
        </a>
      </div>
    </div>
  <?php else: ?>
    <div class="resume-grid">
      <?php foreach ($profiles as $fmt => $data):
        $name = $data['personal_name_kanji'] ?? $data['name_romaji'] ?? $data['name_kana'] ?? '（名称未入力）';
        $isKaigo = $fmt === 'kaigo';
        $editUrl   = $isKaigo ? '/rireki/kaigo/rireki.php' : '/rireki/basic/rireki.php';
        $fmtLabel  = $isKaigo ? '介護向け 履歴書' : '標準 履歴書';
        $icon      = $isKaigo ? '🏥' : '📋';
        $iconClass = $isKaigo ? 'kaigo' : 'basic';
      ?>
      <div class="resume-card">
        <div class="resume-card-header">
          <div class="resume-icon <?= $iconClass ?>"><?= $icon ?></div>
          <div class="resume-card-meta">
            <h3><?= $fmtLabel ?></h3>
            <p><?= h($name) ?></p>
          </div>
        </div>
        <div style="font-size:12px;color:var(--muted);line-height:1.5">
          <?php if (!empty($data['email'])): ?>
            📧 <?= h($data['email']) ?><br>
          <?php endif; ?>
          <?php if (!empty($data['contact_phone']) || !empty($data['phone'])): ?>
            📞 <?= h($data['contact_phone'] ?? $data['phone'] ?? '') ?>
          <?php endif; ?>
        </div>
        <div class="resume-card-actions">
          <a class="action-btn sky" href="<?= h($editUrl) ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            編集・更新
          </a>
          <a class="action-btn green" href="<?= h($editUrl) ?>">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            ダウンロード
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Add new format card -->
      <?php if (!isset($profiles['basic'])): ?>
      <div class="empty-card">
        <div class="icon">📋</div>
        <p>標準フォーマットがまだありません。</p>
        <a class="btn-ghost" href="/php/user_login.php?next=/rireki/basic/rireki.php&fmt=basic" style="display:inline-flex;font-size:13px">作成する</a>
      </div>
      <?php endif; ?>
      <?php if (!isset($profiles['kaigo'])): ?>
      <div class="empty-card">
        <div class="icon">🏥</div>
        <p>介護向けフォーマットがまだありません。</p>
        <a class="btn-ghost" href="/php/user_login.php?next=/rireki/kaigo/rireki.php&fmt=kaigo" style="display:inline-flex;font-size:13px">作成する</a>
      </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<div class="wrap"><div class="section-divider"></div></div>

<!-- ===== NOTICE ===== -->
<div class="wrap">
  <div class="notice">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    <div><strong>ご注意：</strong>PDF出力は環境によりレイアウトが崩れる場合があります。<strong>Excel（.xls）でのダウンロード・印刷を推奨</strong>します。</div>
  </div>
</div>

<!-- ===== FORMAT SELECTION ===== -->
<section class="wrap formats-section" id="formats">
  <div class="section-label">📁 フォーマット選択</div>
  <div class="section-title">あなたに合ったテンプレートを選択</div>
  <div class="section-sub">まず作成したいフォーマットを選んでください</div>

  <div class="format-grid">
    <!-- BASIC -->
    <article class="format-card">
      <div class="format-thumb basic">Basic</div>
      <div class="format-body">
        <h2>標準 履歴書 <span class="format-tag">おすすめ</span></h2>
        <p>一般応募向けの標準フォーマット。氏名・住所・学歴・職歴・資格・自己PRなど。最短5分で完了。</p>
      </div>
      <div class="format-cta">
        <a class="btn-primary" href="/php/user_login.php?next=/rireki/basic/rireki.php&fmt=basic" style="width:100%;justify-content:center">
          このフォーマットで作成
        </a>
      </div>
    </article>

    <!-- KAIGO -->
    <article class="format-card">
      <div class="format-thumb kaigo">Kaigo</div>
      <div class="format-body">
        <h2>介護向け 履歴書 <span class="format-tag green">業界特化</span></h2>
        <p>介護資格・夜勤可否・経験年数・シフト希望など、介護業界に特化した入力ステップ。</p>
      </div>
      <div class="format-cta">
        <a class="btn-ghost" href="/php/user_login.php?next=/rireki/kaigo/rireki.php&fmt=kaigo" style="display:inline-flex;width:100%;justify-content:center;padding:12px;background:linear-gradient(135deg,var(--sky),var(--sky2));border:none;color:#fff">
          このフォーマットで作成
        </a>
      </div>
    </article>

    <!-- SOON -->
    <article class="format-card">
      <div class="format-soon-badge">近日公開</div>
      <div class="format-thumb soon">Shinsotsu</div>
      <div class="format-body">
        <h2>新卒向け 履歴書</h2>
        <p>卒業見込・ゼミ/卒研・インターン・志望業界など新卒特化の項目。公開までもう少しお待ちください。</p>
      </div>
      <div class="format-cta">
        <button class="btn-ghost" style="width:100%;justify-content:center;opacity:.5;cursor:not-allowed" disabled>準備中</button>
      </div>
    </article>
  </div>
</section>

<div class="wrap"><div class="section-divider"></div></div>

<!-- ===== HOW TO ===== -->
<section class="wrap howto-section" id="howto">
  <div class="section-label">🚀 使い方</div>
  <div class="section-title">4ステップで完成</div>
  <div class="section-sub" style="margin-bottom:32px">アカウント登録から5分でダウンロードまで</div>
  <div class="steps-row">
    <div class="step-item">
      <div class="step-num">1</div>
      <h3>ログイン・登録</h3>
      <p>無料アカウントを作成してフォームへ。既存ユーザーはそのまま続きから編集できます。</p>
    </div>
    <div class="step-item">
      <div class="step-num">2</div>
      <h3>フォーマット選択</h3>
      <p>標準・介護用など、応募先に合ったテンプレートを選びます。</p>
    </div>
    <div class="step-item">
      <div class="step-num">3</div>
      <h3>情報を入力</h3>
      <p>氏名・学歴・職歴・資格等を入力。AIで自己PRを整えることも可能。</p>
    </div>
    <div class="step-item">
      <div class="step-num">4</div>
      <h3>Excel出力</h3>
      <p>完成したらExcel（.xls）でダウンロード。そのまま印刷または提出できます。</p>
    </div>
  </div>
</section>

<div class="wrap"><div class="section-divider"></div></div>

<!-- ===== FAQ ===== -->
<section class="wrap faq-section" id="faq">
  <div class="section-label">❓ FAQ</div>
  <div class="section-title" style="margin-bottom:24px">よくある質問</div>
  <div class="faq-list">
    <details class="faq-item">
      <summary class="faq-q">本サービスは完全無料ですか？</summary>
      <div class="faq-a">はい、完全無料です。作成した履歴書はExcel（.xls）でダウンロード可能です。</div>
    </details>
    <details class="faq-item">
      <summary class="faq-q">アカウントは必要ですか？</summary>
      <div class="faq-a">はい。フォーム入力前にメールアドレスとパスワードで無料登録が必要です。登録後は何度でも履歴書を編集・更新・再ダウンロードできます。</div>
    </details>
    <details class="faq-item">
      <summary class="faq-q">介護業界向けの項目はありますか？</summary>
      <div class="faq-a">はい。介護資格・夜勤可否・経験年数など、介護向けフォーマットをご用意しています。</div>
    </details>
    <details class="faq-item">
      <summary class="faq-q">スマホでも使えますか？</summary>
      <div class="faq-a">はい。スマホ・タブレット・PCすべてに対応しています。インストールは不要です。</div>
    </details>
    <details class="faq-item">
      <summary class="faq-q">以前の入力データはどこで確認できますか？</summary>
      <div class="faq-a">ログイン後、このページ上部に「マイ履歴書」セクションが表示されます。そこから編集またはダウンロードできます。</div>
    </details>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="footer-container">
    <div class="footer-row">
      <div class="footer-col">
        <h3 class="footer-heading">所在地</h3>
        <div class="footer-link"><a href="https://it-future.jp/" style="color:white">株式会社アイティーエフ</a></div>
        <p class="footer-text">〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>06-6644-1800<br>〒144-0052 東京都大田区蒲田5丁目21-13<br>03-6424-7747<br>info@it-future.jp</p>
      </div>
      <div class="footer-col">
        <h3 class="footer-heading">サービス案内</h3>
        <a href="https://it-future.jp/index.html#solution_03" class="footer-link">人財をお探しの企業様</a>
        <a href="https://it-future.jp/index.html#service-naiyo" class="footer-link">サービス紹介</a>
        <a href="https://it-future.jp/index.html#merit" class="footer-link">メリット</a>
        <a href="https://it-future.jp/index.html#work-step" class="footer-link">紹介の流れ</a>
        <a href="https://it-future.jp/about.html#support-naiyou" class="footer-link">サポート内容</a>
      </div>
      <div class="footer-col">
        <h3 class="footer-heading">会社案内</h3>
        <a href="https://it-future.jp/greeting.html" class="footer-link">代表者挨拶</a>
        <a href="https://it-future.jp/company_info.html" class="footer-link">会社概要</a>
      </div>
      <div class="footer-col">
        <a href="https://it-future.jp/privacy.html" class="footer-btn">プライバシーポリシー</a>
      </div>
    </div>
    <div class="footer-copyright">© ITF co. Ltd. ALL Rights Reserved</div>
  </div>
</footer>

<a href="#" id="back-to-top" title="Back to Top" aria-label="Back to Top"
   style="position:fixed;right:18px;bottom:18px;display:inline-flex;border:1px solid rgba(255,255,255,.15);border-radius:999px;background:rgba(6,13,26,.7);backdrop-filter:blur(10px);padding:9px;color:var(--ink);z-index:99">
  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</a>

<script>
// Dynamic Auth State Fetcher (Bypasses CDN Cache)
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch('/rireki/php/api_user_status.php');
    const data = await res.json();
    if (data.logged_in) {
      // 1. Update NavBar Links
      const navLinks = document.querySelector('.nav-links');
      if (navLinks) {
        navLinks.innerHTML = `
          <a class="nav-link" href="https://it-future.jp/">ホーム</a>
          <a class="nav-link" href="https://it-future.jp/saiyou.php">求人を見る</a>
          <div class="nav-user">
            <div class="nav-user-avatar">${data.username.charAt(0)}</div>
            <span style="font-size:13px;font-weight:600">${data.username}さん</span>
          </div>
          <a class="nav-btn primary" href="/rireki/user_data.php" style="font-size:12px;padding:7px 14px">マイページ</a>
          <a class="nav-btn" href="/php/user_logout.php" style="font-size:12px;padding:7px 14px;background:rgba(255,0,0,0.1);color:#ff6a6a;border-color:rgba(255,0,0,0.3)">ログアウト</a>
        `;
      }

      // 2. Update Hero Return Bar if present
      const heroReturn = document.querySelector('.hero-return-bar');
      if (heroReturn) {
        heroReturn.innerHTML = `
          <div class="logged-in-hero-row">
            <a class="btn-preview" href="/rireki/user_data.php">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              ダッシュボードを開く
            </a>
            <a class="btn-edit-resume" href="/php/user_logout.php" style="color:#ff6a6a;background:rgba(255,0,0,0.1);border-color:rgba(255,0,0,0.3)">
              ログアウト
            </a>
          </div>
        `;
      }
    }
  } catch (err) {
    console.warn('Auth fetch failed', err);
  }
});

// Stars
(function(){
  const wrap = document.getElementById('stars');
  for(let i=0;i<55;i++){
    const s = document.createElement('div');
    s.className = 'star';
    const sz = Math.random()*2+0.8;
    s.style.cssText = `width:${sz}px;height:${sz}px;top:${Math.random()*100}%;left:${Math.random()*100}%;--d:${(Math.random()*4+2).toFixed(1)}s;animation-delay:-${(Math.random()*6).toFixed(1)}s`;
    wrap.appendChild(s);
  }
})();
</script>
</body>
</html>
