<?php
// /home/it-future/www/itf/saiyou.php
?><!DOCTYPE html>
<html lang="ja" itemscope itemtype="https://schema.org/WebPage">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900|Noto+Sans+JP:400,500,700&subset=japanese&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css">
  <title>株式会社アイティーエフ 採用サイト</title>
  <meta name="description" content="株式会社アイティーエフの求人一覧ページ。勤務地・職種・日本語レベルなどで検索できます。">
  <meta name="robots" content="max-image-preview:large">
  <link rel="stylesheet" id="wp-block-library-css" href="css/style.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="toc-screen-css" href="css/screen.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="wp-pagenavi-css" href="css/pagenavi-css.css" type="text/css" media="all">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/main_intro.css">
  <link rel="stylesheet" href="css/saiyou.css?v=2.0"> <!-- updated -->
  <link rel="stylesheet" href="css/login.css">
  <script src="js/jquery.js"></script>
  <script src="js/jquery-migrate.min.js"></script>
  <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
  <link rel="manifest" href="images/site.webmanifest">
  <meta name="theme-color" content="#000000">
</head>

<body class="home blog">
  <div id="overlay" class="md-overlay"></div>

  <header id="header" class="l-header header" itemscope itemtype="https://schema.org/WPHeader">
    <div class="header-frame">
      <div class="header-top">
        <div class="wrap pc-flex bet">
          <div class="header-top-in flex bet vcenter">
            <h1 class="sp-2 logo"><a href="index.html" class="logo-link flex vcenter"><img src="images/logo.png" alt=""></a></h1>
            <div id="sp-menu-open" class="sp l-animebtn sp-3">
              <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')">
                <div class="bar"><span></span><span></span><span></span></div>
              </a>
            </div>
          </div>
          <div class="header-menu sp-md-acc">
            <div id="sp-menu-acc" class="pc-flex hend acc-body">
              <ul class="contents pc-flex str hend max">
                <li class="contents-item"><a href="about.html">事業紹介</a></li>
                <li class="contents-item"><a href="company_info.html">企業情報</a></li>
                <li class="contents-item"><a href="saiyou.php">新着採用</a></li>
                <li class="contents-item"><a href="news.html">新着情報</a></li>
              </ul>
              <ul class="cta pc-flex max str">
                <li class="cta-item tel sp">
                  <a href="tel:06-6644-1800" class="sp-flex hcenter vcenter">
                    <i class="icon icon-phone"></i>
                    <span class="text">電話でのお問い合わせ<br><span class="note">09:00～19:00(土日祝除く)</span></span>
                  </a>
                </li>
                <li class="cta-item document flex vcenter">
                  <select id="language-selector">
                    <option value="ja">日本語</option>
                    <option value="en">English</option>
                    <option value="id">Indonesian</option>
                    <option value="vi">Vietnamese</option>
                    <option value="zh">Chinese</option>
                    <option value="ne">Nepali</option>
                    <option value="tl">Filipino</option>
                    <option value="ko">Korean</option>
                    <option value="hi">Hindi</option>
                    <option value="bn">Bengali</option>
                  </select>
                </li>
                <li class="cta-item inquiry flex vcenter">
                  <a href="inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header><br>

  <!-- Hero -->
  <section class="bg">
    <div class="overlay">
      <form class="search-form" action="saiyou.php" method="GET">
        <input type="text" name="q" placeholder="場所、カテゴリ、キーワード（#タグ可）で検索"
          value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
        <button type="submit"><i class="icon-search"><img src="images/icons8-search-30.png" alt=""></i></button>
      </form>
    </div>
  </section>

  <!-- Jobs -->
  <section class="saiyou-section">
    <h2 class="section-title"><?php echo (isset($_GET['q']) && $_GET['q']!=='') ? 'マッチング求人' : '最近追加された求人'; ?></h2>
    <div class="content-wrapper">
      <div class="results">
        <?php
          require_once 'php/db_connect.php';

          $query = "SELECT * FROM posts WHERE post_type = 'job'";
          $params = [];

          if (!empty($_GET['q'])) {
            $search = "%" . $_GET['q'] . "%";
            // 検索対象（会社名は表示しないが検索には含めるか → ここでは含めない）
            $query .= " AND (title LIKE :search OR summary LIKE :search OR job_location LIKE :search OR job_category LIKE :search)";
            $params[':search'] = $search;
          }
          if (!empty($_GET['location'])) {
            $query .= " AND job_location = :location";
            $params[':location'] = $_GET['location'];
          }
          if (!empty($_GET['job_type'])) {
            $query .= " AND job_type = :job_type";
            $params[':job_type'] = $_GET['job_type'];
          }
          if (!empty($_GET['japanese_level'])) {
            $query .= " AND japanese_level = :japanese_level";
            $params[':japanese_level'] = $_GET['japanese_level'];
          }
          if (!empty($_GET['job_category'])) {
            $query .= " AND job_category = :job_category";
            $params[':job_category'] = $_GET['job_category'];
          }

          $query .= " ORDER BY date DESC";
          $stmt = $pdo->prepare($query);
          $stmt->execute($params);
          $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

          if (count($jobs) > 0) {
            foreach ($jobs as $i => $job) {

              // ハッシュタグ抽出（summary から #タグのみ抽出）
              $hashtags = [];
              if (!empty($job['summary'])) {
                if (preg_match_all('/#[^\s#]+/u', $job['summary'], $m)) {
                  $hashtags = $m[0];
                }
              }

              echo '<article class="job-card">';
              echo '  <div class="job-card__badge">おすすめ</div>';
              echo '  <h3 class="job-card__title"><a href="php/job_details.php?job_id=' . (int)$job['id'] . '">' . htmlspecialchars($job['title']) . '</a></h3>';

              // ハッシュタグ行（ある場合のみ）
              if (!empty($hashtags)) {
                echo '  <div class="job-card__hashtags">' . htmlspecialchars(implode(' ', $hashtags)) . '</div>';
              }

              echo '  <ul class="job-card__meta">';
              // 給与（自由入力の文字列）
              if (!empty($job['salary'])) {
                echo '    <li class="meta salary">年収' . htmlspecialchars($job['salary']) . '</li>';
              }
              if (!empty($job['job_location'])) {
                echo '    <li class="meta place">' . htmlspecialchars($job['job_location']) . '</li>';
              }
              echo '  </ul>';

              echo '</article>';
              if ($i < count($jobs) - 1) {
                echo '<hr class="job-divider">';
              }
            }
          } else {
            echo '<p>求人が見つかりませんでした。</p>';
          }
        ?>
      </div>

      <aside class="filters">
        <h2>フィルターを設定</h2>
        <form action="saiyou.php" method="GET">
          <label>
            勤務地:
            <select name="location">
              <option value="">全て</option>
              <?php
              $locations = $pdo->query("SELECT DISTINCT job_location FROM posts WHERE post_type = 'job' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);
              foreach ($locations as $location) {
                $selected = (isset($_GET['location']) && $_GET['location'] === $location) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($location).'" '.$selected.'>'.htmlspecialchars($location).'</option>';
              }
              ?>
            </select>
          </label>

          <label>
            職種:
            <select name="job_type">
              <option value="">全て</option>
              <?php
              $job_types = $pdo->query("SELECT DISTINCT job_type FROM posts WHERE post_type = 'job' ORDER BY job_type")->fetchAll(PDO::FETCH_COLUMN);
              foreach ($job_types as $job_type) {
                $selected = (isset($_GET['job_type']) && $_GET['job_type'] === $job_type) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($job_type).'" '.$selected.'>'.htmlspecialchars($job_type).'</option>';
              }
              ?>
            </select>
          </label>

          <label>
            日本語レベル:
            <select name="japanese_level">
              <option value="">全て</option>
              <?php
              $levels = $pdo->query("SELECT DISTINCT japanese_level FROM posts WHERE post_type = 'job' ORDER BY japanese_level")->fetchAll(PDO::FETCH_COLUMN);
              foreach ($levels as $level) {
                $selected = (isset($_GET['japanese_level']) && $_GET['japanese_level'] === $level) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($level).'" '.$selected.'>'.htmlspecialchars($level).'</option>';
              }
              ?>
            </select>
          </label>

          <label>
            カテゴリ:
            <select name="job_category">
              <option value="">全て</option>
              <?php
              $cats = $pdo->query("SELECT DISTINCT job_category FROM posts WHERE post_type = 'job' ORDER BY job_category")->fetchAll(PDO::FETCH_COLUMN);
              foreach ($cats as $cat) {
                $selected = (isset($_GET['job_category']) && $_GET['job_category'] === $cat) ? 'selected' : '';
                echo '<option value="'.htmlspecialchars($cat).'" '.$selected.'>'.htmlspecialchars($cat).'</option>';
              }
              ?>
            </select>
          </label>

          <button type="submit">適用</button>
        </form>
      </aside>
    </div>
  </section>

  <aside class="l-side"></aside>

  <footer class="footer">
    <div class="footer-container">
      <div class="footer-row">
        <div class="footer-col">
          <h3 class="footer-heading">所在地</h3>
          <div class="footer-link"><a href="index.html" style="color:#fff">株式会社アイティーエフ</a></div>
          <p class="footer-text">
            〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>06-6644-1800<br>
            〒144-0052 東京都大田区蒲田5丁目21-13<br>03-6424-7747<br>info@it-future.jp
          </p>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading">サービス案内</h3>
          <a href="index.html#solution_03" class="footer-link">人財をお探しの企業様</a>
          <a href="index.html#service-naiyou" class="footer-link">サービス紹介</a>
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
      <div class="footer-copyright">© ITF co. Ltd. ALL Rights Reserved</div>
    </div>
  </footer>

  <a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </a>

  <script src="https://unpkg.com/i18next@23.11.5/dist/umd/i18next.min.js"></script>
  <script src="js/i18nextHttpBackend.min.js"></script>
  <script src="https://unpkg.com/i18next-browser-languagedetector@7.1.0/dist/umd/i18nextBrowserLanguageDetector.min.js"></script>
  <script src="js/i18n.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/form.min.js"></script>
  <script src="js/recruit.js"></script>
  <script src="js/front.min.js"></script>
</body>
</html>
