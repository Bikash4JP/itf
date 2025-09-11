<?php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書フォーマットを選択</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{ --sky:#39a7ff; --sky2:#e9f5ff; --ink:#0b0f19; --muted:#667085; --ring:#bfe2ff; }
    *{ box-sizing:border-box; }
    body{ margin:0; font-family: ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif; color:var(--ink); background:#fff; }
    header{ padding:28px 20px; border-bottom:1px solid #eef2f7; }
    .wrap{ max-width:1100px; margin:0 auto; }
    h1{ margin:0; font-size:24px; letter-spacing:.2px; }
    p.lead{ margin:8px 0 0; color:var(--muted); }
    .grid{ display:grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap:18px; padding:24px 20px 44px; }
    @media (max-width: 900px){ .grid{ grid-template-columns:1fr; } }
    .card{ position:relative; border:1px solid #e6edf6; border-radius:16px; overflow:hidden; background:#fff; transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .card:focus-within,.card:hover{ transform: translateY(-2px); border-color: var(--ring); box-shadow: 0 10px 24px rgba(57,167,255,.12); }
    .thumb{ aspect-ratio: 16/10; background:linear-gradient(135deg, var(--sky2), #fff); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:20px; color:#1b66a7; }
    .body{ padding:16px; }
    .title{ font-size:18px; margin:0 0 6px; display:flex; align-items:center; gap:8px; }
    .title .tag{ font-size:12px; padding:2px 8px; border-radius:999px; background:var(--sky2); color:#1969b5; border:1px solid #d7ebff; }
    .desc{ margin:0; color:#475467; line-height:1.5; }
    .cta{ display:flex; gap:10px; padding:14px 16px 18px; }
    .btn{ appearance:none; cursor:pointer; border-radius:10px; padding:12px 14px; border:1px solid #dbe7f5; background:#f3f9ff; color:#0c4a7a; text-decoration:none; font-weight:600; transition: background .2s, transform .2s; }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); }
    .btn.primary{ background:var(--sky); border-color:var(--sky); color:#fff; }
    .btn.primary:hover{ filter:brightness(0.96); }
    .soon{ position:absolute; top:10px; right:10px; background:#111827; color:#fff; font-size:11px; padding:4px 8px; border-radius:999px; opacity:.9; }
    footer{ padding:20px; border-top:1px solid #eef2f7; color:#667085; font-size:12px; }
  </style>
</head>
<body>
  <header>
    <div class="wrap">
      <h1>履歴書フォーマットを選択</h1>
      <p class="lead">まずはフォーマットを選んでください。選択した形式に必要な項目だけを入力できます。</p>
    </div>
  </header>

  <main class="wrap">
    <div class="grid">

      <!-- BASIC (existing) -->
      <article class="card">
        <div class="thumb">Basic</div>
        <div class="body">
          <h2 class="title">標準 履歴書 <span class="tag">おすすめ</span></h2>
          <p class="desc">一般応募向けの標準フォーマット。氏名・住所・学歴・職歴・資格・自己PRなど。</p>
        </div>
        <div class="cta">
          <a class="btn primary" href="/rireki/basic/rireki.php">このフォーマットで作成</a>
          <a class="btn" href="/rireki/basic/php/submit_rireki.php?demo=1">サンプルを見る</a>
        </div>
      </article>

      <!-- KAIGO -->
      <article class="card">
        <div class="soon">近日公開</div>
        <div class="thumb">Kaigo</div>
        <div class="body">
          <h2 class="title">介護向け 履歴書</h2>
          <p class="desc">介護資格・夜勤可否・経験年数など、介護業界に特化した入力ステップ。</p>
        </div>
        <div class="cta">
          <a class="btn" href="javascript:void(0)" aria-disabled="true">準備中</a>
        </div>
      </article>

      <!-- SHINSOTSU -->
      <article class="card">
        <div class="soon">近日公開</div>
        <div class="thumb">Shinsotsu</div>
        <div class="body">
          <h2 class="title">新卒向け 履歴書</h2>
          <p class="desc">卒業見込・ゼミ/卒研・インターン・志望業界など新卒特化の項目。</p>
        </div>
        <div class="cta">
          <a class="btn" href="javascript:void(0)" aria-disabled="true">準備中</a>
        </div>
      </article>

    </div>
  </main>

  <footer>
    <div class="wrap">© IT-Future — 履歴書ビルダー</div>
  </footer>
</body>
</html>
