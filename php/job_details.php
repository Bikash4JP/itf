<?php
// /home/it-future/www/itf/php/job_details.php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>求人詳細 - 株式会社アイティーエフ</title>
  <link rel="stylesheet" href="../css/common.css">
  <link rel="stylesheet" href="../css/style.min.css">
  <link rel="stylesheet" href="../css/screen.min.css">
  <link rel="stylesheet" href="../css/pagenavi-css.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/main_intro.css">
  <link rel="stylesheet" href="../css/saiyou.css">
  <link rel="stylesheet" href="../css/login.css">
  <style>
    .job-details-section{max-width:800px;margin:40px auto;padding:20px;background:#f0fcfd;border-radius:20px;box-shadow:0 2px 4px rgba(0,0,0,.1);}
    .job-details-section h2{color:#0577c5;font-size: x-large; font-weight: bolder;margin-bottom:20px}
    .job-details-section p,.job-details-section ul{margin:10px 0;font-size:2rem}
    .job-details-section ul{list-style:disc;padding-left:20px}
    .button-container{text-align:right;margin-top:20px}
    .apply-button,.back-button{display:inline-block;padding:10px 20px;color:#fff;text-decoration:none;border-radius:4px;font-size:.95rem;margin-left:10px}
    .apply-button{background:#2a7de1}
    .apply-button:hover{background:#1a5cb8}
    .back-button{background:#555}
    .back-button:hover{background:#333}
    .kv{margin:8px 0 0;color:#475569}
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

<section class="job-details-section">
<?php
require_once 'db_connect.php';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($job_id <= 0) { echo '<p>無効な求人IDです。</p>'; exit; }

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job'");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) { echo '<p>求人が見つかりませんでした。</p>'; }
else {
  // derive booleans safely (DB may store '0'/'1' or NULL)
  $hasBonus   = !empty($job['bonuses']) && $job['bonuses'] != '0';
  $hasLiving  = !empty($job['living_support']) && $job['living_support'] != '0';
  $hasIns     = !empty($job['insurance']) && $job['insurance'] != '0';
  $hasTrans   = !empty($job['transportation_charges']) && $job['transportation_charges'] != '0';
  $hasInc     = !empty($job['salary_increment']) && $job['salary_increment'] != '0';

  // build apply URL by category (介護 → resume flow)
  $isKaigo = (isset($job['job_category']) && $job['job_category'] === '介護');
  $applyHref = $isKaigo
    ? '../php/resume.php?job_id=' . rawurlencode((string)$job['id']) . '&flow=kaigo'
    : '../recruitment.php?job_id=' . rawurlencode((string)$job['id']); // ← 他職種の導線（必要に応じて変更）

  echo '<h2>' . htmlspecialchars($job['title']) . '</h2>';
  echo '<p class="kv"><strong>投稿日:</strong> ' . htmlspecialchars($job['date'] ?? '') . '</p>';
  echo '<p class="kv"><strong>会社名:</strong> ' . htmlspecialchars($job['company_name'] ?? '') . '</p>';
  echo '<p class="kv"><strong>概要:</strong> ' . nl2br(htmlspecialchars($job['summary'] ?? '')) . '</p>';
  echo '<p><strong>詳細内容:</strong></p>';
  echo '<div>' . nl2br(htmlspecialchars($job['content'] ?? '')) . '</div>';

  echo '<p class="kv"><strong>勤務地:</strong> ' . htmlspecialchars($job['job_location'] ?? '') . '</p>';
  echo '<p class="kv"><strong>職種カテゴリ:</strong> ' . htmlspecialchars($job['job_category'] ?? '') . '</p>';
  echo '<p class="kv"><strong>雇用形態:</strong> ' . htmlspecialchars($job['job_type'] ?? '') . '</p>';
  echo '<p class="kv"><strong>給与:</strong> ' . htmlspecialchars($job['salary'] ?? '') . '</p>';

  echo '<p class="kv"><strong>賞与:</strong> ' . ($hasBonus ? 'あり（額：' . htmlspecialchars($job['bonus_amount'] ?? '') . '）' : 'なし') . '</p>';
  echo '<p class="kv"><strong>住宅手当:</strong> ' . ($hasLiving ? 'あり（額：' . htmlspecialchars($job['rent_support_amount'] ?? '') . '）' : 'なし') . '</p>';
  echo '<p class="kv"><strong>保険:</strong> ' . ($hasIns ? 'あり' : 'なし') . '</p>';
  echo '<p class="kv"><strong>交通費:</strong> ' . ($hasTrans ? 'あり（上限：' . htmlspecialchars($job['transport_amount'] ?? '') . '／月）' : 'なし') . '</p>';
  echo '<p class="kv"><strong>昇給:</strong> ' . ($hasInc ? 'あり（条件：' . htmlspecialchars($job['increment_condition'] ?? '') . '）' : 'なし') . '</p>';

  echo '<p class="kv"><strong>必要日本語レベル:</strong> ' . htmlspecialchars($job['japanese_level'] ?? '') . '</p>';
  echo '<p class="kv"><strong>経験:</strong> ' . htmlspecialchars($job['experience'] ?? '') . '</p>';
  echo '<p class="kv"><strong>年間最低休暇日数:</strong> ' . htmlspecialchars($job['minimum_leave_per_year'] ?? '') . ' 日</p>';
  echo '<p class="kv"><strong>現在の従業員数:</strong> ' . htmlspecialchars($job['employee_size'] ?? '') . '</p>';
  echo '<p class="kv"><strong>募集人数:</strong> ' . htmlspecialchars($job['required_vacancy'] ?? '') . '</p>';
  echo '<p class="kv"><strong>投稿者:</strong> ' . htmlspecialchars($job['posted_by'] ?? '') . '</p>';

  echo '<div class="button-container">';
  echo '<a href="'. $applyHref .'" class="apply-button">この求人に応募する</a>';
  echo '<a href="../saiyou.php" class="back-button">求人一覧に戻る</a>';
  echo '</div>';
}
?>
</section>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-row">
      <div class="footer-col">
        <h3 class="footer-heading">所在地</h3>
        <div class="footer-link"><a href="index.html" style="color:#fff;">株式会社アイティーエフ</a></div>
        <p class="footer-text">
          〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
          06-6644-1800<br>
          〒144-0052 東京都大田区蒲田5丁目21-13<br>
          03-6424-7747<br>
          info@it-future.jp
        </p>
      </div>
      <div class="footer-col">
        <h3 class="footer-heading">サービス案内</h3>
        <a href="index.html#solution_03" class="footer-link">人財をお探しの企業様</a>
        <a href="index.html#service-naiyo" class="footer-link">サービス紹介</a>
        <a href="index.html#merit" class="footer-link">メリット</a>
        <a href="index.html#work-step" class="footer-link">紹介の流れ</a>
        <a href="about.html#support-naiyou" class="footer-link">サポート内容</a>
      </div>
      <div class="footer-col">
        <h3 class="footer-heading">会社案内</h3>
        <a href="greeting.html" class="footer-link">代表者挨拶</a>
        <a href="company_info.html" class="footer-link">会社概要</a>
      </div>
      <div class="footer-col">
        <a href="privacy.html" class="footer-btn">プライバシーポリシー</a>
      </div>
    </div>
    <div class="footer-copyright">
      © ITF co. Ltd. ALL Rights Reserved
    </div>
  </div>
</footer>

<a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
       stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="18 15 12 9 6 15"></polyline>
  </svg>
</a>

<!-- i18next & scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/news.js"></script>
<script src="/js/wp-embed.min.js"></script>
</body>
</html>
