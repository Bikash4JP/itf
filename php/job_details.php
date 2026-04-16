<?php
// /home/it-future/www/itf/php/job_details.php

declare(strict_types=1);

require_once __DIR__ . '/db_connect.php';

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function nl($s): string {
  $s = (string)$s;
  if ($s === '') return '';
  return nl2br(h($s));
}

/**
 * ✅ tinyint / boolean -> 「あり / なし」
 */
function yn($v): string {
  if ($v === null) return '—';
  if (is_bool($v)) return $v ? 'あり' : 'なし';

  $s = trim((string)$v);
  if ($s === '') return '—';

  if (in_array($s, ['1','あり','有','true','TRUE','yes','YES'], true)) return 'あり';
  if (in_array($s, ['0','なし','無','false','FALSE','no','NO'], true)) return 'なし';

  return h($s);
}

/**
 * ✅ tinyint / boolean -> 「完備 / なし」 (社会保険など)
 */
function cover($v): string {
  if ($v === null) return '—';
  if (is_bool($v)) return $v ? '完備' : 'なし';

  $s = trim((string)$v);
  if ($s === '') return '—';

  if (in_array($s, ['1','完備','有','true','TRUE','yes','YES'], true)) return '完備';
  if (in_array($s, ['0','なし','無','false','FALSE','no','NO'], true)) return 'なし';

  return h($s);
}

/**
 * ✅ For fields that can be either TEXT or 0/1:
 * - if 0/1 => 完備/なし
 * - else => show text with nl2br
 */
function cover_or_text($v): string {
  $s = trim((string)($v ?? ''));
  if ($s === '') return '—';

  if (in_array($s, ['1','完備','有','true','TRUE','yes','YES'], true)) return '完備';
  if (in_array($s, ['0','なし','無','false','FALSE','no','NO'], true)) return 'なし';

  return nl($s);
}

// Try to parse yen numbers from text (e.g., "24万", "240,000円", "24万円 + 手当")
function parse_yen(?string $raw): ?int {
  $s = trim((string)$raw);
  if ($s === '') return null;

  $s = str_replace(['，','円','¥','￥',' '], [',','','','',''], $s);

  if (preg_match('/(\d+(?:\.\d+)?)\s*万/u', $s, $m)) {
    return (int)round(((float)$m[1]) * 10000);
  }
  if (preg_match('/(\d[\d,]*)/u', $s, $m)) {
    $n = (int)str_replace(',', '', $m[1]);
    return $n > 0 ? $n : null;
  }
  return null;
}
function yen_fmt(?int $n): string {
  if (!$n) return '—';
  return number_format($n) . '円';
}

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($job_id <= 0) {
  http_response_code(400);
  echo "無効な求人IDです。";
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type='job' LIMIT 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
  http_response_code(404);
  echo "求人が見つかりませんでした。";
  exit;
}

// ===== Public fields (match DB column names) =====
$title   = (string)($job['title'] ?? '');
$summary = (string)($job['summary'] ?? '');
$content = (string)($job['content'] ?? '');

$job_location          = (string)($job['job_location'] ?? '');
$work_location_detail  = (string)($job['work_location_detail'] ?? '');
$contract_period       = (string)($job['contract_period'] ?? '');
$probation_period      = (string)($job['probation_period'] ?? '');
$job_change_scope      = (string)($job['job_change_scope'] ?? '');
$workplace_change_scope= (string)($job['workplace_change_scope'] ?? '');
$work_hours_shift      = (string)($job['work_hours_shift'] ?? '');
$break_time            = (string)($job['break_time'] ?? '');
$overtime              = (string)($job['overtime'] ?? '');
$holidays              = (string)($job['holidays'] ?? '');
$paid_leave            = (string)($job['paid_leave'] ?? '');
$annual_holidays       = (string)($job['annual_holidays'] ?? '');

$current_residence     = (string)($job['current_residence'] ?? '');
$japanese_level        = (string)($job['japanese_level'] ?? '');
$required_age          = (string)($job['required_age'] ?? '');
$gender_pref           = (string)($job['gender_pref'] ?? '');
$experience            = (string)($job['experience'] ?? '');
$skills_certifications = (string)($job['skills_certifications'] ?? '');
$required_vacancy      = (string)($job['required_vacancy'] ?? '');

$salary                = (string)($job['salary'] ?? '');
$salary_basic          = (string)($job['salary_basic'] ?? '');
$salary_takehome       = (string)($job['salary_takehome'] ?? '');

// ✅ can be TEXT or 0/1 (we’ll display smartly)
$tax_pension_insurance = $job['tax_pension_insurance'] ?? '';

$bonus_amount          = (string)($job['bonus_amount'] ?? '');
$transport_amount_limit= (string)($job['transport_amount_limit'] ?? '');
$rent_support          = (string)($job['rent_support'] ?? '');
$increment_condition   = (string)($job['increment_condition'] ?? '');

// ✅ These are tinyint in DB
$bonuses          = $job['bonuses'] ?? null;
$visa_support     = $job['visa_support'] ?? null;
$social_insurance = $job['social_insurance'] ?? null; // ✅ 完備/なし
$salary_increment = $job['salary_increment'] ?? null;

$life_support     = $job['life_support'] ?? null;

// Calculations
$takehome_num = parse_yen($salary_takehome);
$bonus_num    = parse_yen($bonus_amount);
$total_yearly_takeaway = null;
if ($takehome_num !== null) {
  $total_yearly_takeaway = ($takehome_num * 12) + (int)($bonus_num ?? 0);
}

$published_date = (string)($job['date'] ?? '');
$apply_url     = "/php/apply_with_profile.php?job_id=" . (int)$job['id'] . "&fmt=kaigo";
$apply_new_url = "/rireki/kaigo/rireki.php?job_id=" . (int)$job['id'];
$back_url      = "https://it-future.jp/saiyou.php";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= h($title ?: '求人詳細') ?>｜求人情報</title>

  <link rel="stylesheet" href="https://it-future.jp/css/common.css">
  <link rel="stylesheet" href="https://it-future.jp/css/style.min.css">
  <link rel="stylesheet" href="https://it-future.jp/css/screen.min.css">
  <link rel="stylesheet" href="https://it-future.jp/css/pagenavi-css.css">
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <link rel="stylesheet" href="https://it-future.jp/css/main_intro.css">
  <link rel="stylesheet" href="https://it-future.jp/css/login.css">

  <style>
    :root{--sky:#38bdf8;--sky-d:#0284c7;--ink:#0b2243;--muted:#64748b;--line:#e6f2fb;--bg:#f6fbff;--card:#ffffff;--shadow:0 10px 30px rgba(2,132,199,.10);--radius:18px}
    body{background:var(--bg)}
    .container{max-width:1120px;margin:0 auto;padding:18px 14px}
    .crumbs{font-size:12px;color:var(--muted);margin:10px 0}
    .crumbs a{color:var(--sky-d);text-decoration:none}
    .crumbs a:hover{text-decoration:underline}
    .job-hero{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:22px 20px}
    .job-hero h1{margin:0;font-size:28px;line-height:1.25;color:var(--ink);font-weight:900;letter-spacing:.2px}
    .hero-sub{margin-top:10px;display:flex;flex-wrap:wrap;gap:10px 14px;color:var(--muted);font-size:13px}
    .hero-chip{display:inline-flex;align-items:center;gap:8px;background:#eef9ff;border:1px solid var(--line);padding:8px 10px;border-radius:999px;font-weight:800;color:#0b3772}
    .hero-chip b{color:var(--sky-d)}
    .hero-note{margin-top:10px;color:var(--muted);font-size:12px}
    .tabs{margin-top:14px;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:10px;position:sticky;top:0;z-index:50}
    .tabs .tab-row{display:flex;gap:8px;flex-wrap:wrap}
    .tab{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--sky-d);font-weight:900;text-decoration:none;font-size:13px;transition:.12s}
    .tab:hover{filter:brightness(.98);transform:translateY(-1px)}
    .tab.primary{background:var(--sky-d);border-color:var(--sky-d);color:#fff}
    .grid{margin-top:14px;display:grid;grid-template-columns:1fr 340px;gap:14px;align-items:start}
    @media (max-width:980px){.grid{grid-template-columns:1fr}.tabs{position:relative;top:auto}}
    .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px}
    .section-title{margin:0 0 12px 0;display:flex;align-items:center;gap:10px;color:var(--ink);font-size:18px;font-weight:900}
    .section-title .dot{width:10px;height:10px;border-radius:50%;background:var(--sky);box-shadow:0 0 0 4px rgba(56,189,248,.22)}
    .desc{color:var(--ink);line-height:1.75;font-size:14px}
    .muted{color:var(--muted);font-size:12px}
    .dl{display:grid;grid-template-columns:220px 1fr;border:1px solid var(--line);border-radius:14px;overflow:hidden;background:#fff}
    .dl .dt{padding:12px;background:#f0fbff;border-bottom:1px solid var(--line);color:#0b3772;font-weight:900;font-size:13px}
    .dl .dd{padding:12px;border-bottom:1px solid var(--line);color:var(--ink);font-size:13px;line-height:1.7;background:#fff}
    .dl .dt:last-of-type,.dl .dd:last-of-type{border-bottom:none}
    @media (max-width:700px){.dl{grid-template-columns:1fr}.dl .dt{border-bottom:none}.dl .dd{border-bottom:1px solid var(--line)}}
    .side{position:sticky;top:86px;display:flex;flex-direction:column;gap:12px}
    @media (max-width:980px){.side{position:relative;top:auto}}
    .cta{border-radius:16px;padding:14px;border:1px solid var(--line);background:#fff;box-shadow:var(--shadow)}
    .btn{display:inline-flex;width:100%;align-items:center;justify-content:center;padding:12px 14px;border-radius:14px;font-weight:900;text-decoration:none;border:1px solid transparent;font-size:14px;cursor:pointer;transition:.12s}
    .btn.apply{background:var(--sky-d);color:#fff}
    .btn.apply:hover{filter:brightness(.98);transform:translateY(-1px)}
    .btn.back{background:#fff;border-color:var(--line);color:var(--sky-d)}
    .btn.back:hover{background:#f5fbff}
    .mini{margin-top:10px;display:grid;gap:8px;font-size:13px;color:var(--muted)}
    .mini b{color:var(--ink)}
    .hr{height:1px;background:var(--line);margin:12px 0}
    .anchor{scroll-margin-top:90px}
  </style>
</head>

<body>
<header id="header" class="l-header header" itemscope itemtype="https://schema.org/WPHeader">
  <div class="header-frame">
    <div class="header-top">
      <div class="wrap pc-flex bet">
        <div class="header-top-in flex bet vcenter">
          <h1 class="sp-2 logo">
            <a href="https://it-future.jp/" class="logo-link flex vcenter">
              <img src="https://it-future.jp/images/logo.png" alt="ロゴ">
            </a>
          </h1>
          <div id="sp-menu-open" class="sp l-animebtn sp-3">
            <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')" aria-label="メニューを開閉">
              <div class="bar"><span></span><span></span><span></span></div>
            </a>
          </div>
        </div>
        <div class="header-menu sp-md-acc">
          <div id="sp-menu-acc" class="pc-flex hend acc-body">
            <ul class="contents pc-flex str hend max">
              <li class="contents-item"><a href="https://it-future.jp/about.html">事業紹介</a></li>
              <li class="contents-item"><a href="https://it-future.jp/company_info.html">企業情報</a></li>
              <li class="contents-item"><a href="https://it-future.jp/saiyou.php" aria-current="page">求人情報</a></li>
              <li class="contents-item"><a href="https://it-future.jp/news.html">新着情報</a></li>
            </ul>
            <ul class="cta pc-flex max str">
              <li class="cta-item inquiry flex vcenter">
                <a href="https://it-future.jp/inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<div class="container">
  <div class="crumbs">
    <a href="https://it-future.jp/">ホーム</a>  ›  <a href="<?= h($back_url) ?>">求人一覧</a>  ›  求人詳細
  </div>

  <section class="job-hero" aria-label="求人ヘッダー">
    <h1><?= h($title ?: '求人詳細') ?></h1>

    <div class="hero-sub">
      <span class="hero-chip"><b>勤務地</b><?= h($job_location ?: '未設定') ?></span>
      <span class="hero-chip"><b>月給</b><?= h($salary ?: '—') ?></span>
      <span class="hero-chip"><b>日本語</b><?= h($japanese_level ?: '—') ?></span>
      <span class="hero-chip"><b>募集人数</b><?= h($required_vacancy ?: '—') ?></span>
      <span class="hero-chip"><b>経験</b><?= h($experience ?: '—') ?></span>
    </div>

    <?php if ($published_date !== ''): ?>
      <div class="hero-note">更新日：<?= h($published_date) ?></div>
    <?php endif; ?>
  </section>

  <nav class="tabs" aria-label="ページ内ナビ">
    <div class="tab-row">
      <a class="tab primary" href="#sec-work">仕事内容</a>
      <a class="tab" href="#sec-req">募集要項</a>
      <a class="tab" href="#sec-pay">給与・待遇</a>
    </div>
  </nav>

  <div class="grid">
    <main>
      <section id="sec-work" class="card anchor" aria-labelledby="h-work">
        <h2 class="section-title" id="h-work"><span class="dot"></span>仕事内容</h2>

        <?php if (trim($summary) !== ''): ?>
          <div class="muted" style="margin-bottom:8px">キーワード</div>
          <div class="desc" style="margin-bottom:14px"><?= nl($summary) ?></div>
        <?php endif; ?>

        <div class="muted" style="margin-bottom:8px">詳細内容</div>
        <div class="desc"><?= trim($content) !== '' ? nl($content) : '—' ?></div>

        <div class="hr"></div>

        <div class="dl" aria-label="労働条件（必須項目）">
          <div class="dt">勤務地住所（詳細）</div>
          <div class="dd"><?= $work_location_detail !== '' ? nl($work_location_detail) : '—' ?></div>

          <div class="dt">契約期間</div>
          <div class="dd"><?= $contract_period !== '' ? nl($contract_period) : '—' ?></div>

          <div class="dt">試用期間</div>
          <div class="dd"><?= $probation_period !== '' ? nl($probation_period) : '—' ?></div>

          <div class="dt">業務変更の範囲</div>
          <div class="dd"><?= $job_change_scope !== '' ? nl($job_change_scope) : '—' ?></div>

          <div class="dt">就業場所変更の範囲</div>
          <div class="dd"><?= $workplace_change_scope !== '' ? nl($workplace_change_scope) : '—' ?></div>

          <div class="dt">就業時間（シフト）</div>
          <div class="dd"><?= $work_hours_shift !== '' ? nl($work_hours_shift) : '—' ?></div>

          <div class="dt">休憩時間</div>
          <div class="dd"><?= $break_time !== '' ? nl($break_time) : '—' ?></div>

          <div class="dt">時間外労働</div>
          <div class="dd"><?= $overtime !== '' ? nl($overtime) : '—' ?></div>

          <div class="dt">休日</div>
          <div class="dd"><?= $holidays !== '' ? nl($holidays) : '—' ?></div>

          <div class="dt">年次有給休暇</div>
          <div class="dd"><?= $paid_leave !== '' ? nl($paid_leave) : '—' ?></div>

          <div class="dt">年間休日</div>
          <div class="dd"><?= $annual_holidays !== '' ? nl($annual_holidays) : '—' ?></div>
        </div>
      </section>

      <section id="sec-req" class="card anchor" aria-labelledby="h-req" style="margin-top:14px">
        <h2 class="section-title" id="h-req"><span class="dot"></span>募集要項</h2>

        <div class="dl" aria-label="応募条件">
          <div class="dt">応募者の現在地</div>
          <div class="dd"><?= $current_residence !== '' ? h($current_residence) : '—' ?></div>

          <div class="dt">必要日本語レベル</div>
          <div class="dd"><?= $japanese_level !== '' ? h($japanese_level) : '—' ?></div>

          <div class="dt">必要年齢</div>
          <div class="dd"><?= $required_age !== '' ? h($required_age) : '—' ?></div>

          <div class="dt">性別</div>
          <div class="dd"><?= $gender_pref !== '' ? h($gender_pref) : '—' ?></div>

          <div class="dt">経験</div>
          <div class="dd"><?= $experience !== '' ? h($experience) : '—' ?></div>

          <div class="dt">募集人数</div>
          <div class="dd"><?= $required_vacancy !== '' ? h($required_vacancy) : '—' ?></div>

          <div class="dt">スキル・資格</div>
          <div class="dd"><?= $skills_certifications !== '' ? nl($skills_certifications) : '—' ?></div>
        </div>
      </section>

      <section id="sec-pay" class="card anchor" aria-labelledby="h-pay" style="margin-top:14px">
        <h2 class="section-title" id="h-pay"><span class="dot"></span>給与・待遇</h2>

        <div class="dl" aria-label="給与と待遇">
          <div class="dt">月給（総支給目安）</div>
          <div class="dd"><?= $salary !== '' ? h($salary) : '—' ?></div>

          <div class="dt">基本給</div>
          <div class="dd"><?= $salary_basic !== '' ? h($salary_basic) : '—' ?></div>

          <div class="dt">税金・年金・保険等</div>
          <div class="dd"><?= cover_or_text($tax_pension_insurance) ?></div>

          <div class="dt">手取り（控除後）</div>
          <div class="dd"><?= $salary_takehome !== '' ? h($salary_takehome) : '—' ?></div>

          <div class="dt">賞与</div>
          <div class="dd"><?= yn($bonuses) ?></div>

          <div class="dt">賞与内容（目安金額）</div>
          <div class="dd"><?= $bonus_amount !== '' ? h($bonus_amount) : '—' ?></div>

          <div class="dt">年間手取り概算</div>
          <div class="dd">
            <?php if ($total_yearly_takeaway !== null): ?>
              <?= h(yen_fmt($total_yearly_takeaway)) ?>
              <div class="muted">※ 手取り×12 + 賞与（目安）で計算</div>
            <?php else: ?>
              —
            <?php endif; ?>
          </div>

          <div class="dt">交通費上限</div>
          <div class="dd"><?= $transport_amount_limit !== '' ? h($transport_amount_limit) : '—' ?></div>

          <div class="dt">住宅手当</div>
          <div class="dd"><?= $rent_support !== '' ? h($rent_support) : '—' ?></div>

          <?php if ($life_support !== null && trim((string)$life_support) !== ''): ?>
            <div class="dt">生活支援</div>
            <div class="dd"><?= yn($life_support) ?></div>
          <?php endif; ?>

          <div class="dt">ビザ支援</div>
          <div class="dd"><?= yn($visa_support) ?></div>

          <div class="dt">社会保険</div>
          <div class="dd"><?= cover($social_insurance) ?></div>

          <div class="dt">昇給あり</div>
          <div class="dd"><?= yn($salary_increment) ?></div>

          <div class="dt">昇給条件</div>
          <div class="dd"><?= $increment_condition !== '' ? nl($increment_condition) : '—' ?></div>
        </div>

        <div class="muted" style="margin-top:10px">
          ※ 企業名は非公開です（社内管理項目）。
        </div>
      </section>
    </main>

    <aside class="side" aria-label="応募パネル">
      <div class="cta">
        <a class="btn apply" href="<?= h($apply_url) ?>">💼 この求人にプロフィールで応募</a>
        <div class="mini" style="margin-top:6px; font-size:12px; color:var(--muted); text-align:center">ログイン後、登録済みプロフィールで即応募できます</div>
        <div class="hr"></div>
        <a class="btn back" href="<?= h($apply_new_url) ?>" style="font-size:13px">🗒️ 新規で履歴書を作成して応募</a>
        <div class="hr"></div>
        <a class="btn back" href="<?= h($back_url) ?>">求人一覧に戻る</a>
      </div>

      <div class="cta">
        <div class="muted" style="margin-bottom:8px">応募前の確認</div>
        <div class="desc" style="font-size:13px">
          面談時に求人票（PDF/画像）を見ながら、シフト・給与内訳・手当・控除など詳細を丁寧に説明します。
        </div>
      </div>
    </aside>
  </div>
</div>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-row">
      <div class="footer-col">
        <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
        <div class="footer-link">
          <a href="https://it-future.jp/" style="color: white;" data-i18n="footer.company_name">会社情報</a>
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
      © ALL Rights Reserved
    </div>
  </div>
</footer>

<script>
  document.querySelectorAll('a[href^="#"]').forEach(a=>{
    a.addEventListener("click",(e)=>{
      const id = a.getAttribute("href");
      const el = document.querySelector(id);
      if(!el) return;
      e.preventDefault();
      el.scrollIntoView({behavior:"smooth", block:"start"});
    });
  });
</script>

</body>
</html>
