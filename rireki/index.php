<?php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書フォーマットを選択</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <style>
    :root{
      --sky:#39a7ff; --sky-2:#1e90ff; --sky2:#e9f5ff;
      --ink:#0b0f19; --muted:#667085; --ring:#bfe2ff;
      --card:#ffffff; --border:#e6edf6;
    }
    *{ box-sizing:border-box; }
    html,body{ height:100% }
    body{
      margin:0;
      font-family: ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;
      color:var(--ink);
      background:linear-gradient(180deg,#f8fbff,#eef6ff);
    }
    header{
      padding:28px 20px;
      border-bottom:1px solid #eef2f7;
      backdrop-filter:saturate(180%) blur(6px);
      background:rgba(255,255,255,.7);
      position:sticky; top:0; z-index:10;
    }
    .wrap{ max-width:1100px; margin:0 auto; }
    h1{ margin:0; font-size:24px; letter-spacing:.2px; font-weight:800; }
    p.lead{ margin:8px 0 0; color:var(--muted); }
    main.wrap{ padding-bottom:44px; }
    .grid{
      display:grid; grid-template-columns: repeat(3,minmax(0,1fr));
      gap:18px; padding:24px 20px 44px;
    }
    @media (max-width: 980px){ .grid{ grid-template-columns:1fr 1fr } }
    @media (max-width: 700px){ .grid{ grid-template-columns:1fr } }

    .card{
      position:relative; border:1px solid var(--border); border-radius:16px;
      overflow:hidden; background:var(--card);
      transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
      box-shadow: 0 10px 24px rgba(0,0,0,.04);
    }
    .card:focus-within,.card:hover{
      transform: translateY(-2px);
      border-color: var(--ring);
      box-shadow: 0 10px 28px rgba(57,167,255,.18);
    }
    .thumb{
      aspect-ratio: 16/10;
      display:flex; align-items:center; justify-content:center;
      font-weight:800; font-size:20px; color:#1b66a7;
      background:linear-gradient(135deg, var(--sky2), #fff);
    }
    .card.basic .thumb{
      background:linear-gradient(135deg, #e9f5ff, #fff);
    }
    .card.kaigo .thumb{
      background:linear-gradient(135deg, #e6fff7, #fff);
      color:#0b6b4a;
    }
    .card.shinsotsu .thumb{
      background:linear-gradient(135deg, #f7ecff, #fff);
      color:#5e2ca5;
    }

    .body{ padding:16px; }
    .title{
      font-size:18px; margin:0 0 6px; display:flex; align-items:center; gap:8px; font-weight:800;
    }
    .title .tag{
      font-size:12px; padding:2px 8px; border-radius:999px;
      background:#eef7ff; color:#1969b5; border:1px solid #d7ebff;
    }
    .desc{ margin:0; color:#475467; line-height:1.5; }

    .cta{ display:flex; gap:10px; padding:14px 16px 18px; flex-wrap:wrap; }
    .btn{
      appearance:none; cursor:pointer; border-radius:10px; padding:12px 14px;
      border:1px solid #dbe7f5; background:#f3f9ff; color:#0c4a7a; text-decoration:none; font-weight:700;
      transition: background .2s, transform .2s, box-shadow .2s, border-color .2s;
    }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); box-shadow:0 6px 20px rgba(57,167,255,.18) }
    .btn.primary{ background:linear-gradient(180deg,var(--sky),var(--sky-2)); border-color:var(--sky); color:#fff; }
    .btn.primary:hover{ filter:brightness(0.96); }

    .soon{
      position:absolute; top:10px; right:10px; background:#111827; color:#fff;
      font-size:11px; padding:4px 8px; border-radius:999px; opacity:.9;
    }
    .btn[aria-disabled="true"]{ opacity:.6; cursor:not-allowed; }

    footer{ padding:20px; border-top:1px solid #eef2f7; color:#667085; font-size:12px; background:#fff; }
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
      <article class="card basic">
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

      <!-- KAIGO (now live) -->
      <article class="card kaigo">
        <div class="thumb">Kaigo</div>
        <div class="body">
          <h2 class="title">介護向け 履歴書 <span class="tag">業界特化</span></h2>
          <p class="desc">介護資格・夜勤可否・経験年数・シフト希望など、介護業界に特化した入力ステップ。</p>
        </div>
        <div class="cta">
          <a class="btn primary" href="/rireki/kaigo/rireki.php">このフォーマットで作成</a>
          <a class="btn" href="/rireki/kaigo/php/submit_rireki.php?demo=1">サンプルを見る</a>
        </div>
      </article>

      <!-- SHINSOTSU (coming soon) -->
      <article class="card shinsotsu">
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

  <footer class="footer">
            <div class="footer-container">
                <div class="footer-row">
                    <div class="footer-col">
                        <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
                        <div class="footer-link">
                            <a href="index.html" style="color: white;" data-i18n="footer.company_name">株式会社アイティーエフ</a>
                        </div>
                        <p class="footer-text" data-i18n="footer.location_details">
                            〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
                            06-6644-1800<br>
                            〒144-0052 東京都大田区蒲田5丁目21-13<br>
                            03-6424-7747<br>
                            info@it-future.jp
                        </p>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-heading" data-i18n="footer.services_title">サービス案内</h3>
                        <a href="index.html#solution_03" class="footer-link"
                            data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
                        
                        <a href="index.html#service-naiyo" class="footer-link"
                            data-i18n="footer.service_introduction">サービス紹介</a>
                        <a href="index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
                        <a href="index.html#work-step" class="footer-link"
                            data-i18n="footer.introduction_flow">紹介の流れ</a>
                            <a href="about.html#support-naiyou" class="footer-link"
                            data-i18n="footer.support_content">サポート内容</a>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
                        <a href="greeting.html" class="footer-link"
                            data-i18n="footer.president_greeting">代表者挨拶</a>
                        <a href="company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
                    </div>
                    <div class="footer-col">
                        <a href="privacy.html" class="footer-btn" data-i18n="footer.privacy_policy">プライバシーポリシー</a>
                    </div>
                </div>
                <div class="footer-copyright">
                    © ITF co. Ltd. ALL Rights Reserved
                </div>
            </div>
        </footer>
    </div>

    <a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </a>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="https://it-future.jp/js/main.min.js"></script> -->
    <!-- <script src="https://it-future.jp/js/scripts.js"></script> -->
    <script type="text/javascript" src="https://it-future.jp/js/wp-embed.min.js"></script>
</body>
</html>
