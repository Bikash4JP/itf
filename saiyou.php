<?php
// /home/it-future/www/itf/saiyou.php
?><!DOCTYPE html>
<html lang="ja" itemscope itemtype="https://schema.org/WebPage">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>求人一覧・採用情報｜株式会社アイティーエフ（勤務地・職種・日本語レベルで検索可）</title>
  <meta name="description" content="株式会社アイティーエフの求人一覧ページ。勤務地・職種・日本語レベル・カテゴリ・キーワードで検索できます。最新求人をチェックして応募ください。">
  <link rel="canonical" href="https://it-future.jp/saiyou.php">

  <!-- Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="求人一覧・採用情報｜株式会社アイティーエフ">
  <meta property="og:description" content="勤務地・職種・日本語レベルで検索できるアイティーエフの求人一覧。最新求人をチェックして応募ください。">
  <meta property="og:url" content="https://it-future.jp/saiyou.php">
  <meta property="og:site_name" content="株式会社アイティーエフ">
  <meta property="og:image" content="https://it-future.jp/images/inquiry_main.jpg">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="求人一覧・採用情報｜株式会社アイティーエフ">
  <meta name="twitter:description" content="勤務地・職種・日本語レベルで検索できる求人一覧。">
  <meta name="twitter:image" content="https://it-future.jp/images/inquiry_main.jpg">

  <meta name="robots" content="max-image-preview:large">

  <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900|Noto+Sans+JP:400,500,700&subset=japanese&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" id="wp-block-library-css" href="css/style.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="toc-screen-css" href="css/screen.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="wp-pagenavi-css" href="css/pagenavi-css.css" type="text/css" media="all">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/main_intro.css">
  <link rel="stylesheet" href="css/saiyou.css?v=2.1"> <!-- updated -->
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
  <header id="header" class="l-header header" itemscope itemtype="https://schema.org/WPHeader">
    <div class="header-frame">
      <div class="header-top">
        <div class="wrap pc-flex bet">
          <div class="header-top-in flex bet vcenter">
            <h1 class="sp-2 logo"><a href="https://it-future.jp/" class="logo-link flex vcenter"><img src="images/logo.png" alt="株式会社アイティーエフ ロゴ"></a></h1>
            <div id="sp-menu-open" class="sp l-animebtn sp-3">
              <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')" aria-label="メニューを開閉">
                <div class="bar"><span></span><span></span><span></span></div>
              </a>
            </div>
          </div>
          <div class="header-menu sp-md-acc">
            <div id="sp-menu-acc" class="pc-flex hend acc-body">
              <ul class="contents pc-flex str hend max">
                <li class="contents-item"><a href="about.html">事業紹介</a></li>
                <li class="contents-item"><a href="company_info.html">企業情報</a></li>
                <li class="contents-item"><a href="saiyou.php" aria-current="page">新着採用</a></li>
                <li class="contents-item"><a href="news.html">新着情報</a></li>
              </ul>
              <ul class="cta pc-flex max str">
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

  <!-- Search band -->
  <section class="bg" role="search">
    <div class="overlay">
      <form class="search-form" action="saiyou.php" method="GET" aria-label="求人検索">
        <input type="text" name="q" aria-label="キーワード検索" placeholder="場所、会社、カテゴリ、キーワードで検索" value="<?=htmlspecialchars($_GET['q']??'',ENT_QUOTES,'UTF-8')?>">
        <button type="submit" aria-label="検索"><img src="images/icons8-search-30.png" alt="検索" width="22" height="22"></button>
      </form>
    </div>
  </section>

  <section class="saiyou-section" aria-labelledby="jobs-heading">
    <h1 class="section-title" id="jobs-heading"><?= (isset($_GET['q']) && $_GET['q']!=='') ? 'マッチング求人' : '最近追加された求人' ?></h1>

    <!-- Sticky-frame: title + content-wrapper locked; only results column scrolls -->
    <div class="content-shell">
      <div class="content-wrapper">
        <div class="results" role="list">
          <?php
          require_once 'php/db_connect.php';

          $query = "SELECT * FROM posts WHERE post_type = 'job'";
          $params = [];

          if (!empty($_GET['q'])) {
            $search = "%".$_GET['q']."%";
            $query .= " AND (title LIKE :s OR summary LIKE :s OR company_name LIKE :s OR job_location LIKE :s OR job_category LIKE :s)";
            $params[':s']=$search;
          }
          if (!empty($_GET['location'])) { $query .= " AND job_location = :loc"; $params[':loc']=$_GET['location']; }
          if (!empty($_GET['job_type'])) { $query .= " AND job_type = :jt";     $params[':jt']=$_GET['job_type']; }
          if (!empty($_GET['japanese_level'])) { $query .= " AND japanese_level = :jl"; $params[':jl']=$_GET['japanese_level']; }
          if (!empty($_GET['job_category'])) { $query .= " AND job_category = :jc"; $params[':jc']=$_GET['job_category']; }

          $query .= " ORDER BY date DESC";
          $stmt = $pdo->prepare($query);
          $stmt->execute($params);
          $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

          // Build JSON-LD ItemList for SEO (first up to 10)
          $ldItems = [];
          if ($jobs) {
            $i = 1;
            foreach ($jobs as $job) {
              if ($i > 10) break;
              $ldItems[] = [
                "@type"=>"ListItem",
                "position"=>$i,
                "url"=>"https://it-future.jp/php/job_details.php?job_id=".((int)$job['id']),
                "name"=>(string)$job['title']
              ];
              $i++;
            }
          }
          ?>
          <?php if (!empty($ldItems)): ?>
            <script type="application/ld+json">
              {
                "@context": "https://schema.org",
                "@type": "ItemList",
                "name": "ITF 求人一覧",
                "itemListElement": <?=json_encode($ldItems, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>
              }
            </script>
          <?php endif; ?>

          <?php
          if ($jobs) {
            foreach ($jobs as $idx=>$job) {
              $summary = (string)($job['summary'] ?? '');
              preg_match_all('/#\S+/u', $summary, $m);
              $tags = $m[0] ?? [];

              $jobUrl = 'php/job_details.php?job_id='.htmlspecialchars($job['id']);
              echo '<article class="job-item" itemscope itemtype="https://schema.org/JobPosting" role="listitem">';
              echo '  <h2 class="job-title" itemprop="title"><a href="'.$jobUrl.'" itemprop="url">'.htmlspecialchars($job['title']).'</a></h2>';

              if ($tags) {
                echo '<div class="tag-row" aria-label="タグ">';
                foreach ($tags as $t) { echo '<span class="tag">'.htmlspecialchars($t).'</span>'; }
                echo '</div>';
              }

              echo '  <div class="row line">';
              echo '    <img class="ico" src="/images/yen.png" alt="給与">';
              $salary = trim((string)$job['salary']);
              $salaryText = ($salary!=='' ? htmlspecialchars($salary) : '給与情報なし');
              echo '    <span itemprop="baseSalary">'.$salaryText.'</span>';
              echo '  </div>';

              echo '  <div class="row">';
              echo '    <img class="ico" src="/images/pin.png" alt="勤務地">';
              echo '    <span itemprop="jobLocation">'.htmlspecialchars($job['job_location']).'</span>';
              echo '  </div>';

              echo '  <div class="view-link"><a href="'.$jobUrl.'" aria-label="求人詳細を表示">求人の詳細を見る</a></div>';

              if ($idx < count($jobs)-1) echo '<hr class="job-divider" aria-hidden="true">';
              echo '</article>';
            }
          } else {
            echo '<p>求人が見つかりませんでした。</p>';
          }
          ?>
        </div>

        <aside class="filters" aria-label="求人フィルター">
          <h2>フィルターを設定</h2>
          <form action="saiyou.php" method="GET">
            <label>勤務地:
              <select name="location" aria-label="勤務地で絞り込み">
                <option value="">全て</option>
                <?php
                $locations = $pdo->query("SELECT DISTINCT job_location FROM posts WHERE post_type='job' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($locations as $loc) {
                  $sel = (isset($_GET['location']) && $_GET['location']===$loc) ? 'selected':'';
                  echo '<option value="'.htmlspecialchars($loc).'" '.$sel.'>'.htmlspecialchars($loc).'</option>';
                }
                ?>
              </select>
            </label>
            <label>職種:
              <select name="job_type" aria-label="職種で絞り込み">
                <option value="">全て</option>
                <?php
                $types = $pdo->query("SELECT DISTINCT job_type FROM posts WHERE post_type='job' ORDER BY job_type")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($types as $t) {
                  $sel = (isset($_GET['job_type']) && $_GET['job_type']===$t) ? 'selected':'';
                  echo '<option value="'.htmlspecialchars($t).'" '.$sel.'>'.htmlspecialchars($t).'</option>';
                }
                ?>
              </select>
            </label>
            <label>日本語レベル:
              <select name="japanese_level" aria-label="日本語レベルで絞り込み">
                <option value="">全て</option>
                <?php
                $lvls = $pdo->query("SELECT DISTINCT japanese_level FROM posts WHERE post_type='job' ORDER BY japanese_level")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($lvls as $lvl) {
                  $sel = (isset($_GET['japanese_level']) && $_GET['japanese_level']===$lvl) ? 'selected':'';
                  echo '<option value="'.htmlspecialchars($lvl).'" '.$sel.'>'.htmlspecialchars($lvl).'</option>';
                }
                ?>
              </select>
            </label>
            <label>カテゴリ:
              <select name="job_category" aria-label="カテゴリで絞り込み">
                <option value="">全て</option>
                <?php
                $cats = $pdo->query("SELECT DISTINCT job_category FROM posts WHERE post_type='job' ORDER BY job_category")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($cats as $c) {
                  $sel = (isset($_GET['job_category']) && $_GET['job_category']===$c) ? 'selected':'';
                  echo '<option value="'.htmlspecialchars($c).'" '.$sel.'>'.htmlspecialchars($c).'</option>';
                }
                ?>
              </select>
            </label>
            <button type="submit">適用</button>
          </form>
        </aside>
      </div>
    </div>
  </section>

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
                        <a href="https://it-future.jp/index.html#service-naiyou" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
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
                    © ITF co. Ltd. ALL Rights Reserve
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
  <!-- JSON-LD WebSite + SearchAction -->
  <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"WebSite",
      "name":"株式会社アイティーエフ",
      "url":"https://it-future.jp/",
      "potentialAction":{
        "@type":"SearchAction",
        "target":"https://it-future.jp/saiyou.php?q={search_term_string}",
        "query-input":"required name=search_term_string"
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/form.min.js"></script>
  <script src="js/front.min.js"></script>
</body>
</html>
