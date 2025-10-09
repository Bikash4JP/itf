<?php
// /home/it-future/www/itf/php/upload_success.php
// Simple "thanks" page shown after a successful upload from resume.php

// Get optional context for nicer messaging
$job_id   = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$name     = trim($_GET['name'] ?? '');
$jobTitle = trim($_GET['job_title'] ?? '');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>応募ありがとうございました - 株式会社アイティーエフ</title>
  <link rel="stylesheet" href="../css/common.css">
  <link rel="stylesheet" href="../css/style.min.css">
  <link rel="stylesheet" href="../css/screen.min.css">
  <link rel="stylesheet" href="../css/pagenavi-css.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/main_intro.css">
  <link rel="stylesheet" href="../css/saiyou.css">
  <link rel="stylesheet" href="../css/login.css">
  <style>
    .thanks-section{max-width:800px;margin:40px auto;padding:24px;background:#f0fcfd;border-radius:20px;box-shadow:0 2px 4px rgba(0,0,0,.1)}
    .thanks-section h2{color:#0577c5;font-size:2rem;font-weight: bold; margin-bottom:12px}
    .thanks-section p{margin:8px 0;font-size:1.5rem}
    .thanks-actions{margin-top:18px;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
    .btn{display:inline-block;padding:10px 16px;border-radius:6px;color:#fff;text-decoration:none}
    .btn.primary{background:#2a7de1}
    .btn.gray{background:#555}
    .btn.primary:hover{background:#1a5cb8}
    .btn.gray:hover{background:#333}
  </style>
</head>
<body>
  <header id="header" class="l-header header" itemscope="" itemtype="https://schema.org/WPHeader">
        <div class="header-frame">
            <div class="header-top">
                <div class="wrap pc-flex bet">
                    <div class="header-top-in flex bet vcenter">
                        <h1 class="sp-2 logo"><a href="https://it-future.jp/" class="logo-link flex vcenter"><img
                                    src="https://it-future.jp/images/logo.png" alt="株式会社アイティーエフ - 外国人財紹介サービス"></a></h1>
                        <div id="sp-menu-open" class="sp l-animebtn sp-3">
                            <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')">
                                <div class="bar"><span></span><span></span><span></span></div>
                            </a>
                        </div>
                    </div>
                    <div class="header-menu sp-md-acc">
                        <div id="sp-menu-acc" class="pc-flex hend acc-body">
                            <ul class="contents pc-flex str hend max">
                                <li class="contents-item"><a href="https://it-future.jp/about.html" data-i18n="nav.about">事業紹介</a></li>
                                <li class="contents-item"><a href="https://it-future.jp/company_info.html" data-i18n="nav.company_info">企業情報</a></li>
                                <li class="contents-item"><a href="https://it-future.jp/news.html" data-i18n="nav.news">新着情報</a></li>
                            </ul>
                            <ul class="cta pc-flex max str">
                                <li class="cta-item tel sp">
                                    <a href="tel:06-6644-1800" class="sp-flex hcenter vcenter">
                                        <i class="icon icon-phone"></i>
                                        <span class="text" data-i18n="nav.phone_inquiry">電話でのお問い合わせ<br><span
                                                class="note" data-i18n="nav.phone_hours">09:00～19:00(土日祝除く)</span></span>
                                    </a>
                                </li>
                                <li class="cta-item inquiry flex vcenter">
                                    <a href="inquiry.html" class="cta-item-link flex hcenter vcenter" data-i18n="nav.inquiry">お問い合わせ</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header><br>


  <section class="thanks-section">
    <h2>ご応募ありがとうございました。</h2>
    <?php if ($name !== ''): ?>
      <p><strong><?=htmlspecialchars($name)?></strong> 様</p>
    <?php endif; ?>
    <?php if ($job_id > 0 && $jobTitle !== ''): ?>
      <p>求人「<strong><?=htmlspecialchars($jobTitle)?></strong>」にご応募いただき、誠にありがとうございます。</p>
    <?php elseif ($job_id > 0): ?>
      <p>該当求人にご応募いただき、誠にありがとうございます。（求人ID：<?= (int)$job_id ?>）</p>
    <?php else: ?>
      <p>履歴書のアップロードが完了しました。ご応募ありがとうございます。</p>
    <?php endif; ?>
    <p>担当チームにて内容を確認し、<strong>2営業日以内</strong>にご連絡いたします。</p>

    <div class="thanks-actions">
      <?php if ($job_id > 0): ?>
        <a class="btn primary" href="/php/job_details.php?job_id=<?= (int)$job_id ?>">求人詳細に戻る</a>
      <?php endif; ?>
      <a class="btn gray" href="/saiyou.php">求人一覧へ</a>
    </div>
  </section>

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
          <a href="index.html#solution_03" class="footer-link" data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
          <a href="index.html#service-naiyo" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
          <a href="index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
          <a href="index.html#work-step" class="footer-link" data-i18n="footer.introduction_flow">紹介の流れ</a>
          <a href="about.html#support-naiyou" class="footer-link" data-i18n="footer.support_content">サポート内容</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
          <a href="greeting.html" class="footer-link" data-i18n="footer.president_greeting">代表者挨拶</a>
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
</body>
</html>
