<?php
// /home/it-future/www/itf/rireki/index.php
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="format-detection" content="telephone=no" />
  <meta name="theme-color" content="#1e90ff" />

  <!-- Primary SEO (UPDATED) -->
  <title>【無料】履歴書作成ツール（スマホ対応）｜オンラインで5分・Excelダウンロード</title>
  <meta name="description" content="【無料】履歴書をオンラインで作成。スマホ/PC対応で最短5分、Excel（.xls）でダウンロード可能。標準/介護テンプレ対応、インストール不要でかんたん作成。">

  <!-- Canonical / robots -->
  <link rel="canonical" href="https://it-future.jp/rireki/" />
  <meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">

  <!-- Hreflang -->
  <link rel="alternate" hreflang="ja-JP" href="https://it-future.jp/rireki/" />
  <link rel="alternate" hreflang="x-default" href="https://it-future.jp/rireki/" />

  <!-- Open Graph (UPDATED) -->
  <meta property="og:type" content="website">
  <meta property="og:locale" content="ja_JP">
  <meta property="og:site_name" content="ITF オンライン履歴書メーカー">
  <meta property="og:title" content="【無料】履歴書作成ツール（スマホ対応）｜オンラインで5分・Excelダウンロード">
  <meta property="og:description" content="最短5分で履歴書をオンライン作成。無料・Excel出力・テンプレ対応・スマホOK。介護向けフォーマットも。">
  <meta property="og:url" content="https://it-future.jp/rireki/">
  <meta property="og:image" content="https://it-future.jp/images/og/rireki_og.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">

  <!-- Twitter (UPDATED) -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="【無料】履歴書作成ツール（スマホ対応）｜オンラインで5分・Excelダウンロード">
  <meta name="twitter:description" content="最短5分で履歴書をオンライン作成。無料・Excel出力・テンプレ対応・スマホOK。介護向けフォーマットも。">
  <meta name="twitter:image" content="https://it-future.jp/images/og/rireki_og.png">

  <!-- Performance (UPDATED) -->
  <link rel="preconnect" href="https://it-future.jp" crossorigin>
  <link rel="dns-prefetch" href="https://it-future.jp">
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

  <!-- Preload LCP image (UPDATED) -->
  <link rel="preload" as="image" href="https://it-future.jp/rireki/images/basicRireki_sample.png" fetchpriority="high" />

  <!-- Styles -->
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <style>
    :root{
      --sky:#39a7ff; --sky-2:#1e90ff; --sky2:#e9f5ff;
      --ink:#0b0f19; --muted:#667085; --ring:#bfe2ff;
      --card:#ffffff; --border:#e6edf6; --ok:#0b6b4a; --warn:#b45309; --warn-bg:#fff7ed; --warn-bd:#fed7aa;
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
      padding:24px 20px;
      border-bottom:1px solid #eef2f7;
      background:rgba(255,255,255,.86);
      position:sticky; top:0; z-index:10;
      backdrop-filter:saturate(180%) blur(6px);
    }
    .wrap{ max-width:1100px; margin:0 auto; }
    .hero{ display:grid; grid-template-columns: 1.2fr .8fr; gap:18px; align-items:center; }
    @media (max-width: 900px){ .hero{ grid-template-columns:1fr } }
    .hero h1{ margin:0; font-size:28px; letter-spacing:.2px; font-weight:900; }
    .lead{ margin:8px 0 0; color:var(--muted); line-height:1.7 }
    .benefits{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px }
    .chip{ font-size:12px; padding:6px 10px; border-radius:999px; background:#eef7ff; color:#1969b5; border:1px solid #d7ebff }
    .cta-row{ margin-top:14px; display:flex; gap:10px; flex-wrap:wrap }
    .btn{
      appearance:none; cursor:pointer; border-radius:10px; padding:12px 14px;
      border:1px solid #dbe7f5; background:#f3f9ff; color:#0c4a7a; text-decoration:none; font-weight:800;
      transition:background .2s,transform .2s,box-shadow .2s,border-color .2s;
    }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); box-shadow:0 6px 18px rgba(57,167,255,.18) }
    .btn.primary{ background:linear-gradient(180deg,var(--sky),var(--sky-2)); border-color:var(--sky); color:#fff; }
    .btn.primary:hover{ filter:brightness(.96); }
    .thumb-hero{ border:1px solid var(--border); border-radius:14px; overflow:hidden; background:#fff; box-shadow:0 10px 24px rgba(0,0,0,.06); }
    .thumb-hero img{ display:block; width:100%; height:auto }

    main.wrap{ padding:28px 20px 40px }
    .notice{
      display:flex; gap:10px; align-items:flex-start;
      background:var(--warn-bg); border:1px solid var(--warn-bd); color:var(--warn);
      border-radius:12px; padding:12px 14px; margin:16px 0 8px;
      font-size:14px; line-height:1.6;
    }
    .notice svg{ flex:0 0 auto; margin-top:2px }
    .h2{ font-size:22px; margin:18px 0 10px; font-weight:800 }
    .grid{
      display:grid; grid-template-columns: repeat(3,minmax(0,1fr));
      gap:18px; padding:12px 0 24px;
    }
    @media (max-width: 980px){ .grid{ grid-template-columns:1fr 1fr } }
    @media (max-width: 700px){ .grid{ grid-template-columns:1fr } }
    .card{
      position:relative; border:1px solid var(--border); border-radius:16px;
      overflow:hidden; background:var(--card);
      transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
      box-shadow: 0 10px 24px rgba(0,0,0,.04);
    }
    .card:hover{ transform: translateY(-2px); border-color: var(--ring); box-shadow: 0 10px 28px rgba(57,167,255,.18); }
    .thumb{ aspect-ratio: 16/10; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:20px; color:#1b66a7; background:linear-gradient(135deg, var(--sky2), #fff); }
    .card.kaigo .thumb{ background:linear-gradient(135deg, #e6fff7, #fff); color:var(--ok); }
    .card.shinsotsu .thumb{ background:linear-gradient(135deg, #f7ecff, #fff); color:#5e2ca5; }
    .body{ padding:16px; }
    .title{ font-size:18px; margin:0 0 6px; display:flex; align-items:center; gap:8px; font-weight:800; }
    .tag{ font-size:12px; padding:2px 8px; border-radius:999px; background:#eef7ff; color:#1969b5; border:1px solid #d7ebff; }
    .desc{ margin:0; color:#475467; line-height:1.6; }
    .cta{ display:flex; gap:10px; padding:14px 16px 18px; flex-wrap:wrap; }
    .soon{ position:absolute; top:10px; right:10px; background:#111827; color:#fff; font-size:11px; padding:4px 8px; border-radius:999px; opacity:.9; }
    .faq, .howto{ background:#fff; border:1px solid var(--border); border-radius:14px; padding:16px; margin:18px 0; box-shadow:0 10px 24px rgba(0,0,0,.04) }
    .faq h3, .howto h3{ margin:0 0 10px; font-size:18px }
    details{ border-top:1px dashed #e5e7eb; padding:10px 0 }
    details:first-of-type{ border-top:none }
    summary{ cursor:pointer; font-weight:700 }
    .crumb{ font-size:12px; color:#6b7280; margin:6px 0 0 }
    footer{ padding:20px; border-top:1px solid #eef2f7; color:#667085; font-size:12px; background:#fff; }

    /* Back to top */
    #back-to-top{ text-decoration:none; color:#0c4a7a; box-shadow:0 2px 6px rgba(0,0,0,.12); transition:background .2s,transform .2s,box-shadow .2s }
    #back-to-top:hover{ background:#f3f9ff; transform: translateY(-1px); box-shadow:0 6px 18px rgba(30,144,255,.16) }
    #back-to-top:focus{ outline:none; box-shadow:0 0 0 4px rgba(59,130,246,.5),0 6px 18px rgba(30,144,255,.16) }
  </style>

  <!-- JSON-LD: WebPage (UPDATED) -->
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebPage",
    "name":"オンライン履歴書メーカー（無料）",
    "url":"https://it-future.jp/rireki/",
    "description":"履歴書をオンラインで作成できる無料ツール。スマホ/PC対応、最短5分、Excel（.xls）ダウンロード、標準/介護テンプレ対応。",
    "inLanguage":"ja-JP",
    "isPartOf":{
      "@type":"WebSite",
      "name":"株式会社アイティーエフ",
      "url":"https://it-future.jp/"
    },
    "primaryImageOfPage":{
      "@type":"ImageObject",
      "url":"https://it-future.jp/rireki/images/basicRireki_sample.png",
      "width":720,
      "height":480
    }
  }
  </script>

  <!-- JSON-LD: WebApplication (UPDATED) -->
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebApplication",
    "name":"ITF オンライン履歴書メーカー",
    "url":"https://it-future.jp/rireki/",
    "applicationCategory":"BusinessApplication",
    "operatingSystem":"Web",
    "inLanguage":"ja-JP",
    "offers":{"@type":"Offer","price":"0","priceCurrency":"JPY"},
    "featureList":[
      "スマホで履歴書作成",
      "オンラインで入力して作成",
      "Excel（.xls）ダウンロード",
      "標準/介護テンプレ対応",
      "インストール不要"
    ],
    "publisher":{
      "@type":"Organization",
      "name":"株式会社アイティーエフ",
      "url":"https://it-future.jp/"
    }
  }
  </script>

  <!-- JSON-LD: Breadcrumb -->
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"BreadcrumbList",
    "itemListElement":[
      {"@type":"ListItem","position":1,"name":"ホーム","item":"https://it-future.jp/"},
      {"@type":"ListItem","position":2,"name":"オンライン履歴書メーカー","item":"https://it-future.jp/rireki/"}
    ]
  }
  </script>

  <!-- JSON-LD: FAQ -->
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"FAQPage",
    "mainEntity":[
      {"@type":"Question","name":"本サービスは無料ですか？","acceptedAnswer":{"@type":"Answer","text":"はい、無料でご利用いただけます。作成した履歴書はExcel（.xls）でダウンロードできます。"}},
      {"@type":"Question","name":"履歴書の作成にどれくらい時間がかかりますか？","acceptedAnswer":{"@type":"Answer","text":"基本フォームなら最短5分程度で作成可能です。入力を保存し、後から編集もできます。"}},
      {"@type":"Question","name":"介護業界向けの項目はありますか？","acceptedAnswer":{"@type":"Answer","text":"はい。介護資格・夜勤可否・経験年数など、介護向けフォーマットをご用意しています。"}},
      {"@type":"Question","name":"スマホでも使えますか？","acceptedAnswer":{"@type":"Answer","text":"はい。スマホ・タブレット・PCに対応しています。インストールは不要です。"}}
    ]
  }
  </script>
</head>
<body>
  <header>
    <div class="wrap hero">
      <div>
        <p class="crumb">ホーム ＞ オンライン履歴書メーカー</p>
        <h1>オンライン履歴書メーカー（無料）— 5分で作成、Excel出力にも対応</h1>
        <p class="lead">
          ダウンロード不要・インストール不要。<br>
          入力するだけで、<strong>最短5分</strong>で履歴書が完成します。<br>
          <strong>Excel（.xls）でダウンロード</strong>でき、介護向けの特化フォーマットもご用意。
        </p>
        <div class="benefits">
          <span class="chip">無料</span>
          <span class="chip">Excel出力</span>
          <span class="chip">スマホOK</span>
          <span class="chip">介護向けテンプレ</span>
          <span class="chip">日本語＆外国籍対応</span>
        </div>
        <div class="cta-row">
          <!-- Quick start -> Terms -> Basic form -->
          <a class="btn primary" href="/rireki/rireki_terms.php?next=/rireki/basic/rireki.php&fmt=basic">5分で無料作成をはじめる</a>
          <a class="btn" href="/saiyou.php">求人を見ながら作成</a>
        </div>
      </div>
      <div class="thumb-hero">
        <!-- LCP improvement: eager + fetchpriority (UPDATED) -->
        <img src="https://it-future.jp/rireki/images/basicRireki_sample.png"
             alt="オンライン履歴書メーカーのスクリーンショット"
             loading="eager"
             fetchpriority="high"
             decoding="async"
             width="720"
             height="480">
      </div>
    </div>
  </header>

  <main class="wrap">
    <!-- CLEAR NOTICE about PDF vs Excel -->
    <div class="notice" role="note" aria-live="polite">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
      </svg>
      <div>
        <strong>ご注意：</strong>PDF 生成は環境差により<strong>レイアウトが100%一致しない</strong>場合があります。<br>
        <strong>最も正確な出力・印刷は「Excel（.xls）のダウンロード」</strong>をご利用ください。
      </div>
    </div>

    <h2 class="h2">フォーマットを選択</h2>
    <div class="grid" id="formats">
      <!-- BASIC -->
      <article class="card basic">
        <div class="thumb">Basic</div>
        <div class="body">
          <h2 class="title">標準 履歴書 <span class="tag">おすすめ</span></h2>
          <p class="desc">一般応募向けの標準フォーマット。氏名・住所・学歴・職歴・資格・自己PRなど。最短5分で完了。</p>
        </div>
        <div class="cta">
          <!-- Basic -> Terms -> Basic form -->
          <a class="btn primary" href="/rireki/rireki_terms.php?next=/rireki/basic/rireki.php&fmt=basic">このフォーマットで作成</a>
        </div>
      </article>

      <!-- KAIGO -->
      <article class="card kaigo">
        <div class="thumb">Kaigo</div>
        <div class="body">
          <h2 class="title">介護向け 履歴書 <span class="tag">業界特化</span></h2>
          <p class="desc">介護資格・夜勤可否・経験年数・シフト希望など、介護業界に特化した入力ステップ。現場で求められる情報を漏れなく整理。</p>
        </div>
        <div class="cta">
          <!-- Kaigo -> Terms -> Kaigo form -->
          <a class="btn primary" href="/rireki/rireki_terms.php?next=/rireki/kaigo/rireki.php&fmt=kaigo">このフォーマットで作成</a>
        </div>
      </article>

      <!-- SHINSOTSU -->
      <article class="card shinsotsu">
        <div class="soon">近日公開</div>
        <div class="thumb">Shinsotsu</div>
        <div class="body">
          <h2 class="title">新卒向け 履歴書</h2>
          <p class="desc">卒業見込・ゼミ/卒研・インターン・志望業界など新卒特化の項目。公開までもう少しお待ちください。</p>
        </div>
        <div class="cta">
          <a class="btn" href="javascript:void(0)" aria-disabled="true">準備中</a>
        </div>
      </article>
    </div>

    <!-- How-to -->
    <section class="howto" id="howto">
      <h3>使い方（5分で完了）</h3>
      <ol>
        <li><strong>フォーマットを選択：</strong>「標準」または「介護向け」を選びます。</li>
        <li><strong>必要項目を入力：</strong>氏名・連絡先・学歴・職歴・資格等を入力。</li>
        <li><strong>プレビュー：</strong>入力ミスがないか確認します。</li>
        <li><strong>Excel（.xls）で保存：</strong>そのままダウンロードできます（推奨）。<br>
            <small style="color:#6b7280">※ PDF は環境により体裁が崩れる場合があります。Excel からの印刷が最も綺麗です。</small>
        </li>
        <li><strong>求人へ応募：</strong><a href="/saiyou.php">求人情報</a>から応募、または直接提出。</li>
      </ol>
      <p class="lead">求人詳細ページからの応募でも、履歴書の自動作成に対応しています。</p>
    </section>

    <!-- FAQ -->
    <section class="faq" id="faq">
      <h3>よくある質問</h3>
      <details>
        <summary>本サービスは無料ですか？</summary>
        <div>はい、無料でご利用いただけます。作成した履歴書はExcel（.xls）でダウンロード可能です。</div>
      </details>
      <details>
        <summary>介護業界向けの項目はありますか？</summary>
        <div>はい。介護資格・夜勤可否・経験年数など、介護向けフォーマットをご用意しています。</div>
      </details>
      <details>
        <summary>スマホでも使えますか？</summary>
        <div>はい。スマホ・タブレット・PCに対応しています。インストールは不要です。</div>
      </details>
      <details>
        <summary>Excel以外の形式は対応していますか？</summary>
        <div>現在はExcel（.xls）に最適化しています。PDFはレイアウトが完全一致しない場合があるため、Excelでの保存・印刷を推奨します。</div>
      </details>
    </section>

    <p class="lead" style="margin-top:20px">
      キーワード例：<em>オンライン 履歴書 作成 無料 / 履歴書 Excel 5分 / 履歴書 テンプレ 介護</em>
    </p>
  </main>

  <!-- Footer (site shared) -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-row">
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
          <div class="footer-link">
            <a href="https://it-future.jp/" style="color: white;" data-i18n="footer.company_name">株式会社アイティーエフ</a>
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
          <a href="https://it-future.jp/index.html#solution_03" class="footer-link" data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
          <a href="https://it-future.jp/index.html#service-naiyo" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
          <a href="https://it-future.jp/index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
          <a href="https://it-future.jp/index.html#work-step" class="footer-link" data-i18n="footer.introduction_flow">紹介の流れ</a>
          <a href="https://it-future.jp/about.html#support-naiyou" class="footer-link" data-i18n="footer.support_content">サポート内容</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
          <a href="https://it-future.jp/greeting.html" class="footer-link" data-i18n="footer.president_greeting">代表者挨拶</a>
          <a href="https://it-future.jp/company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
        </div>
        <div class="footer-col">
          <a href="https://it-future.jp/privacy.html" class="footer-btn" data-i18n="footer.privacy_policy">プライバシーポリシー</a>
        </div>
      </div>
      <div class="footer-copyright">
        © ITF co. Ltd. ALL Rights Reserved
      </div>
    </div>
  </footer>

  <a href="#" id="back-to-top" class="back-to-top" title="Back to Top" aria-label="Back to Top" style="position:fixed;right:18px;bottom:18px;display:inline-flex;border:1px solid #e5e7eb;border-radius:999px;background:#fff;padding:8px">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </a>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
  <script type="text/javascript" src="https://it-future.jp/js/wp-embed.min.js" defer></script>
</body>
</html>
