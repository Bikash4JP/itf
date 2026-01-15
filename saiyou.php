<?php
// /home/it-future/www/itf/saiyou.php

require_once __DIR__ . '/php/user_auth.php';

// Ensure applicant tables exist (safe)
$pdo_app = app_pdo();
app_ensure_tables($pdo_app);

// ---- helpers (parsers & mappers) ------------------------------------------
function j_to_half($s){ // normalize full-width digits/tilde/space
  $map = ['０'=>'0','１'=>'1','２'=>'2','３'=>'3','４'=>'4','５'=>'5','６'=>'6','７'=>'7','８'=>'8','９'=>'9','－'=>'-','〜'=>'~','～'=>'~','　'=>' '];
  return strtr($s, $map);
}
function parse_salary_jp($raw){
  $s = trim(j_to_half(preg_replace('/\s+/u',' ', $raw ?? '')));
  if ($s === '') return null;

  $unit = null;
  if (preg_match('/年(給|収|俸)?|\/年|年収/i', $s)) $unit = 'YEAR';
  if (preg_match('/月(給|収)?|\/月/i', $s)) $unit = $unit ?? 'MONTH';
  if (!$unit && preg_match('/時給|\/時|hour/i', $s)) $unit = 'HOUR';

  $yen = null; $min = null; $max = null;
  if (preg_match('/(\d+(?:\.\d+)?)\s*万\s*円?/u', $s)) {
    $matches = [];
    preg_match_all('/(\d+(?:\.\d+)?)\s*万/u', $s, $matches);
    if (count($matches[1]) === 1) {
      $yen = (float)$matches[1][0] * 10000;
    } elseif (count($matches[1]) >= 2) {
      $min = (float)$matches[1][0] * 10000;
      $max = (float)$matches[1][1] * 10000;
    }
  } elseif (preg_match('/(\d+(?:,\d{3})+|\d+)\s*円/u', $s)) {
    preg_match_all('/(\d+(?:,\d{3})+|\d+)\s*円/u', $s, $m2);
    $nums = array_map(fn($n)=> (float)str_replace(',', '', $n), $m2[1]);
    if (count($nums) === 1) $yen = $nums[0];
    elseif (count($nums) >= 2) { $min = $nums[0]; $max = $nums[1]; }
  } elseif (preg_match('/(\d+(?:\.\d+)?)\s*万/u', $s)) {
    $matches = [];
    preg_match_all('/(\d+(?:\.\d+)?)\s*万/u', $s, $matches);
    if (count($matches[1]) === 1) {
      $yen = (float)$matches[1][0] * 10000;
    } elseif (count($matches[1]) >= 2) {
      $min = (float)$matches[1][0] * 10000;
      $max = (float)$matches[1][1] * 10000;
    }
  } elseif (preg_match('/(\d+(?:\.\d+)?)(?:\s*~\s*|\s*～\s*|\s*-\s*)(\d+(?:\.\d+)?)/u', $s, $m)) {
    $a = (float)$m[1]; $b = (float)$m[2];
    if ($a >= 15 && $a <= 100 && $b >= 15 && $b <= 100) { $min=$a*10000; $max=$b*10000; }
    else { $min=$a; $max=$b; }
  }

  if (!$unit) {
    if (preg_match('/月/u', $s)) $unit = 'MONTH';
    elseif (preg_match('/年/u', $s)) $unit = 'YEAR';
  }
  if (!$unit) $unit = 'MONTH';

  $out = ['currency'=>'JPY','unit'=> $unit];
  if (!is_null($yen)) $out['value'] = $yen;
  if (!is_null($min)) $out['min']   = $min;
  if (!is_null($max)) $out['max']   = $max;
  return $out;
}
function make_base_salary_ld($parsed){
  if (!$parsed) return null;
  $unitText = $parsed['unit'] ?? 'MONTH';
  $val = $parsed['value'] ?? null;
  $min = $parsed['min']   ?? null;
  $max = $parsed['max']   ?? null;

  $valueObj = ["@type"=>"QuantitativeValue","unitText"=>$unitText];
  if (!is_null($val)) $valueObj["value"] = round($val);
  if (!is_null($min)) $valueObj["minValue"] = round($min);
  if (!is_null($max)) $valueObj["maxValue"] = round($max);

  return ["@type"=>"MonetaryAmount","currency"=>"JPY","value"=>$valueObj];
}
function map_employment_type($raw){
  $s = mb_strtolower(trim($raw ?? ''), 'UTF-8');
  if ($s === '') return "OTHER";
  if (preg_match('/正社員|フル|full|常勤/u', $s)) return "FULL_TIME";
  if (preg_match('/パート|アルバイト|part|バイト|非常勤/u', $s)) return "PART_TIME";
  if (preg_match('/契約|contract/u', $s)) return "CONTRACTOR";
  if (preg_match('/派遣|temporary|有期/u', $s)) return "TEMPORARY";
  if (preg_match('/業務委託|委託|委任|outsourc/u', $s)) return "OTHER";
  if (preg_match('/インターン|intern/u', $s)) return "INTERN";
  return "OTHER";
}
function to_iso8601($dateStr){
  $t = strtotime($dateStr ?: 'now');
  return date('c', $t ?: time());
}
function safe_summary($html, $limit=600){
  $txt = trim(preg_replace('/\s+/u',' ', strip_tags((string)$html)));
  if (mb_strlen($txt,'UTF-8') > $limit) $txt = mb_substr($txt,0,$limit,'UTF-8').'…';
  return $txt ?: '仕事内容の詳細は求人ページをご確認ください。';
}
function build_job_ld($job){
  $id  = (int)($job['id'] ?? 0);
  $url = "https://it-future.jp/php/job_details.php?job_id=".$id;
  $title = (string)($job['title'] ?? '求人');
  $desc  = safe_summary($job['summary'] ?? '');
  $posted= to_iso8601($job['date'] ?? 'now');
  $valid = to_iso8601(($job['date'] ?? 'now').' +90 days');

  $locRegion = trim((string)($job['job_location'] ?? ''));
  if ($locRegion === '') $locRegion = '日本';

  $salaryParsed = parse_salary_jp($job['salary'] ?? '');
  $baseSalaryLD = make_base_salary_ld($salaryParsed);
  $employmentType = map_employment_type($job['job_type'] ?? '');

  $ld = [
    "@context"=>"https://schema.org",
    "@type"=>"JobPosting",
    "title"=>$title,
    "description"=>$desc,
    "datePosted"=>$posted,
    "validThrough"=>$valid,
    "employmentType"=>$employmentType,
    "hiringOrganization"=>[
      "@type"=>"Organization",
      "name"=>"株式会社アイティーエフ",
      "sameAs"=>"https://it-future.jp/",
      "logo"=>"https://it-future.jp/images/logo.png"
    ],
    "jobLocationType"=>"ON_SITE",
    "jobLocation"=>[
      "@type"=>"Place",
      "name"=>$locRegion,
      "address"=>[
        "@type"=>"PostalAddress",
        "addressCountry"=>"JP",
        "addressRegion"=>$locRegion
      ]
    ],
    "applicantLocationRequirements"=>[
      "@type"=>"Country",
      "name"=>"Japan"
    ],
    "industry"=>(string)($job['job_category'] ?? ''),
    "identifier"=>[
      "@type"=>"PropertyValue",
      "name"=>"ITF",
      "value"=>"JOB-".$id
    ],
    "url"=>$url
  ];
  if ($baseSalaryLD) $ld["baseSalary"] = $baseSalaryLD;
  return $ld;
}
?>
<!DOCTYPE html>
<html lang="ja" itemscope itemtype="https://schema.org/WebPage">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>求人一覧・採用情報｜株式会社アイティーエフ（勤務地・職種・日本語レベルで検索可）</title>
  <meta name="description" content="株式会社アイティーエフの求人一覧ページ。勤務地・職種・日本語レベル・カテゴリ・キーワードで検索できます。最新求人をチェックして応募ください。">
  <link rel="canonical" href="https://it-future.jp/saiyou.php">

  <meta property="og:type" content="website">
  <meta property="og:title" content="求人一覧・採用情報｜株式会社アイティーエフ">
  <meta property="og:description" content="勤務地・職種・日本語レベルで検索できるアイティーエフの求人一覧。最新求人をチェックして応募ください。">
  <meta property="og:url" content="https://it-future.jp/saiyou.php">
  <meta property="og:site_name" content="株式会社アイティーエフ">
  <meta property="og:image" content="https://it-future.jp/images/og/saiyou_og.png">

  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="求人一覧・採用情報｜株式会社アイティーエフ">
  <meta name="twitter:description" content="勤務地・職種・日本語レベルで検索できる求人一覧。">
  <meta name="twitter:image" content="https://it-future.jp/images/og/saiyou_og.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:type" content="image/png">

  <meta name="robots" content="max-image-preview:large">

  <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900|Noto+Sans+JP:400,500,700&subset=japanese&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" id="wp-block-library-css" href="css/style.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="toc-screen-css" href="css/screen.min.css" type="text/css" media="all">
  <link rel="stylesheet" id="wp-pagenavi-css" href="css/pagenavi-css.css" type="text/css" media="all">
  <link rel="stylesheet" href="css/footer.css">
  <link rel="stylesheet" href="css/main_intro.css">
  <link rel="stylesheet" href="css/saiyou.css?v=2.3">
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

    <div class="content-shell">
      <div class="content-wrapper">
        <div class="results" role="list">
          <?php
          require_once 'php/db_connect.php';

          $query = "SELECT * FROM posts
          WHERE post_type='job'
            AND publish_state='published'
            AND (status IS NULL OR status='' OR status NOT IN ('募集終','募集終了','募集終わり','2'))";
$params = [];

          if (!empty($_GET['q'])) {
            $search = "%".$_GET['q']."%";
            $query .= " AND (title LIKE :s OR summary LIKE :s OR company_name LIKE :s OR job_location LIKE :s OR job_category LIKE :s)";
            $params[':s']=$search;
          }
          if (!empty($_GET['location']))       { $query .= " AND job_location = :loc";      $params[':loc']=$_GET['location']; }
          if (!empty($_GET['job_type']))       { $query .= " AND job_type = :jt";           $params[':jt']=$_GET['job_type']; }
          if (!empty($_GET['japanese_level'])) { $query .= " AND japanese_level = :jl";     $params[':jl']=$_GET['japanese_level']; }
          if (!empty($_GET['job_category']))   { $query .= " AND job_category = :jc";       $params[':jc']=$_GET['job_category']; }

          $query .= "
            ORDER BY
              CASE
                WHEN status IN ('急募','1') THEN 0
                WHEN status IN ('募集中','0','', NULL) THEN 1
                WHEN status IN ('募集終','募集終了','募集終わり','2') THEN 2
                ELSE 1
              END ASC,
              COALESCE(updated_at, date) DESC,
            id DESC
          ";

          $stmt = $pdo->prepare($query);
          $stmt->execute($params);
          $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

          $jobPostingsLD = [];
          if ($jobs) {
            $count = 0;
            foreach ($jobs as $job) {
              $jobPostingsLD[] = build_job_ld($job);
              $count++;
              if ($count >= 25) break;
            }
          }
          ?>
          <?php if (!empty($ldItems)): ?>
            <script type="application/ld+json">
              <?= json_encode([
                    "@context"=>"https://schema.org",
                    "@type"=>"ItemList",
                    "name"=>"ITF 求人一覧",
                    "itemListElement"=>$ldItems
                  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>
            </script>
          <?php endif; ?>
          <?php if (!empty($jobPostingsLD)): ?>
            <script type="application/ld+json">
              <?= json_encode($jobPostingsLD, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>
            </script>
          <?php endif; ?>

          <?php
          if ($jobs) {
            foreach ($jobs as $idx=>$job) {
              $summary = (string)($job['summary'] ?? '');
              preg_match_all('/#\S+/u', $summary, $m);
              $tags = $m[0] ?? [];

              $jobUrl = 'php/job_details.php?job_id='.htmlspecialchars($job['id']);
              echo '<article class="job-item" role="listitem">';
              echo '  <h2 class="job-title"><a href="'.$jobUrl.'">'.htmlspecialchars($job['title']).'</a></h2>';

              if ($tags) {
                echo '<div class="tag-row" aria-label="タグ">';
                foreach ($tags as $t) echo '<span class="tag">'.htmlspecialchars($t).'</span>';
                echo '</div>';
              }

              echo '  <div class="row line">';
              echo '    <img class="ico" src="/images/yen.png" alt="給与">';
              $salary = trim((string)$job['salary']);
              $salaryText = ($salary!=='' ? htmlspecialchars($salary) : '給与情報なし');
              echo '    <span>'.$salaryText.'</span>';
              echo '  </div>';

              echo '  <div class="row">';
              echo '    <img class="ico" src="/images/pin.png" alt="勤務地">';
              echo '    <span>'.htmlspecialchars($job['job_location']).'</span>';
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
          <?php
            $next = $_SERVER['REQUEST_URI'] ?? '/saiyou.php';
            $loginUrl  = '/php/user_login.php?next=' . urlencode($next);
            $logoutUrl = '/php/user_logout.php'; // ✅ correct logout
          ?>

          <!-- ✅ USER PANEL -->
          <div class="user-box" aria-label="ユーザー">
            <div class="user-head">
              <span class="user-title">ユーザー</span>
              <?php if (app_is_logged_in()): ?>
                <span class="user-badge">ログイン中</span>
              <?php else: ?>
                <span class="user-badge off">ゲスト</span>
              <?php endif; ?>
            </div>

            <?php if (app_is_logged_in()): ?>
              <div class="user-actions">
                <a class="user-link" href="/php/user_applied_jobs.php">応募履歴</a>
                <a class="user-link" href="/rireki/kaigo/php/rireki_preview.php">マイ情報（履歴書）</a>
                <a class="user-link ghost" href="<?=htmlspecialchars($logoutUrl,ENT_QUOTES,'UTF-8')?>">ログアウト</a>
              </div>
              <div class="user-note">※ 「マイ情報」から履歴書を作成・更新できます。</div>
            <?php else: ?>
              <div class="user-note">ログインすると、履歴書を保存して後からダウンロードできます。</div>
              <div class="user-actions">
                <a class="user-link primary" href="<?=htmlspecialchars($loginUrl,ENT_QUOTES,'UTF-8')?>">ログイン / 新規登録</a>
              </div>
            <?php endif; ?>
          </div>

          <hr class="user-divider" aria-hidden="true">

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
        © ITF co. Ltd. ALL Rights Reserved
      </div>
    </div>
  </footer>

  <a href="#" id="back-to-top" class="back-to-top" title="Back to Top" aria-label="Back to Top">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </a>

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
