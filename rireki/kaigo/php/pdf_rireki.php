<?php
// /home/it-future/www/itf/rireki/kaigo/php/pdf_rireki.php
// Renders the kaigo resume as a pixel-perfect HTML page for printing to PDF.
// Layout matches kaigo.xlsx (templateB.json) exactly.

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/php/user_auth.php';

function h($v): string
{
  return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function normalize_token($t): string
{
  $t = strtolower(trim((string) $t));
  return preg_match('/^[a-f0-9]{32}$/', $t) ? $t : '';
}

// Auth guard
if (!app_is_logged_in()) {
  header('Location: /php/user_login.php?next=' . urlencode($_SERVER['REQUEST_URI']), true, 302);
  exit;
}

$uid = (int) app_user_id();
$token = normalize_token($_GET['token'] ?? '');

// If no token, try to find the latest one for this user
if (!$token) {
  $st2 = $pdo->prepare("SELECT token FROM app_resumes WHERE user_id = ? AND fmt = 'kaigo' ORDER BY id DESC LIMIT 1");
  $st2->execute([$uid]);
  $token = (string) ($st2->fetchColumn() ?? '');
}

if (!$token) {
  http_response_code(400);
  echo '<p>No resume found. Please <a href="/rireki/kaigo/rireki.php">create your resume first</a>.</p>';
  exit;
}

$st = $pdo->prepare("SELECT * FROM app_resume_kaigo WHERE token = :t LIMIT 1");
$st->execute([':t' => $token]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(400);
  echo '<p>Resume not found. <a href="/rireki/kaigo/rireki.php">Return to form</a>.</p>';
  exit;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function val(array $row, string ...$keys): string
{
  foreach ($keys as $k) {
    $v = trim((string) ($row[$k] ?? ''));
    if ($v !== '')
      return $v;
  }
  return '';
}

function edu_row(array $row, int $i): array
{
  return [
    'from' => trim($row["edu{$i}_from_year"] ?? '') . (trim($row["edu{$i}_from_month"] ?? '') !== '' ? '年' . $row["edu{$i}_from_month"] . '月' : ''),
    'to' => trim($row["edu{$i}_to_year"] ?? '') . (trim($row["edu{$i}_to_month"] ?? '') !== '' ? '年' . $row["edu{$i}_to_month"] . '月' : ''),
    'institution' => trim($row["edu{$i}_institution"] ?? ''),
    'faculty' => trim($row["edu{$i}_faculty"] ?? ''),
    'status' => trim($row["edu{$i}_status"] ?? ''),
  ];
}

function work_row(array $row, int $i): array
{
  return [
    'from' => trim($row["work{$i}_from_year"] ?? '') . (trim($row["work{$i}_from_month"] ?? '') !== '' ? '年' . $row["work{$i}_from_month"] . '月' : ''),
    'to' => trim($row["work{$i}_to_year"] ?? '') . (trim($row["work{$i}_to_month"] ?? '') !== '' ? '年' . $row["work{$i}_to_month"] . '月' : ''),
    'status' => trim($row["work{$i}_status"] ?? ''),
    'org' => trim($row["work{$i}_org"] ?? ''),
    'job_title' => trim($row["work{$i}_job_title"] ?? ''),
    'desc' => trim($row["work{$i}_description"] ?? ''),
  ];
}

function lic_row(array $row, int $i): array
{
  return [
    'year' => trim($row["lic{$i}_year"] ?? ''),
    'month' => trim($row["lic{$i}_month"] ?? ''),
    'name' => trim($row["lic{$i}_name"] ?? ''),
  ];
}

// Photo
$photoPath = trim((string) ($row['photo_path'] ?? ''));
$photoHtml = '';
if ($photoPath !== '' && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)) {
  $photoHtml = '<img src="' . h($photoPath) . '" alt="写真" style="display:block;width:calc(100% - 2px);height:calc(100% - 2px);margin:1px;object-fit:fill;">';
}

// Date today
$today = date('Y年m月d日');
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>履歴書 - <?= h(val($row, 'name_romaji', 'name_kana')) ?></title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html,
    body {
      width: 100%;
      background: #f0f0f0;
      font-family: 'MS Gothic', 'Meiryo', 'Hiragino Kaku Gothic ProN', sans-serif;
      font-size: 8.5pt;
      color: #000;
    }

    /* ── Print / A4 sheet ── */
    .page {
      width: 210mm;
      /* A4 portrait */
      /* min-height: 297mm; */
      background: #fff;
      margin: 5px auto 0;
      padding: 5px 6mm 6mm;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .3);
    }

    @media print {

      html,
      body {
        background: #fff;
        height: auto !important;
        padding-top: 0 !important;
        margin-top: 0 !important;
      }

      .page {
        width: 100%;
        margin: 0;
        padding: 2mm 5mm;
        box-shadow: none;
        min-height: 0 !important;
        /* height: auto !important; */
      }

      .no-print {
        display: none !important;
      }

      @page {
        size: A4 portrait;
        margin: 2mm 5mm;
      }

      /* CRITICAL: force background colors and images to print */
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }

    /* ── Tables ── */
    table {
      border-collapse: collapse;
      width: 100%;
    }

    td,
    th {
      border: 1px solid #333;
      padding: 1.2mm 2mm;
      vertical-align: middle;
      font-size: 7pt;
      line-height: 1.3;
    }

    th {
      background: #dce6f1;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      font-weight: bold;
      font-size: 6.5pt;
      text-align: center;
      white-space: nowrap;
    }

    .label {
      background: #dce6f1;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      font-weight: bold;
      font-size: 6.5pt;
      white-space: nowrap;
      text-align: right;
      padding-right: 3mm;
    }

    .val {
      background: #fff;
    }

    .section-head {
      background: #2e75b6;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color: #fff;
      font-weight: bold;
      font-size: 8pt;
      padding: 1.5mm 3mm;
      border: 1px solid #1a4d82;
      margin-bottom: 0;
    }

    /* Text areas */
    .textarea-cell {
      min-height: 18mm;
      vertical-align: top;
      white-space: pre-wrap;
      font-size: 7.5pt;
    }

    /* ── Print header bar ── */
    .print-header {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      margin-bottom: 1.5mm;
    }

    .print-header h1 {
      font-size: 13pt;
      font-weight: bold;
      letter-spacing: 4mm;
      flex: 1;
      text-align: center;
    }

    .print-date {
      font-size: 7pt;
      white-space: nowrap;
      text-align: right;
    }

    /* ── No-print bar ── */
    .no-print {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
      background: #1a3a5c;
      color: #fff;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      font-family: sans-serif;
    }

    .no-print button {
      background: #3b9eff;
      border: none;
      color: #fff;
      padding: 8px 22px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 700;
    }

    .no-print button:hover {
      background: #1e78e8;
    }

    body.has-bar {
      padding-top: 52px;
    }

    .num {
      text-align: center;
    }

    .photo-box {
      border: 1px solid #333;
      background: #f8f8f8;
      overflow: hidden;
      width: 100%;
      height: 48mm;
    }

    .photo-placeholder {
      color: #aaa;
      font-size: 7pt;
      text-align: center;
      line-height: 48mm;
    }
  </style>
</head>

<body class="has-bar">

  <!-- ── Toolbar (no-print) ── -->
  <div class="no-print" id="toolbar">
    <strong>📄 履歴書 — PDF として保存</strong>
    <button onclick="window.print()">🖨️ PDF として保存 / 印刷</button>
    <a href="javascript:history.back()" style="color:#aad4ff; text-decoration:none; font-size:13px;">← 戻る</a>
    <span style="margin-left:auto; font-size:12px; opacity:.7;">印刷ダイアログで「PDFに保存」を選択してください</span>
  </div>

  <div class="page">

    <!-- TITLE + DATE on one line -->
    <div class="print-header">
      <span style="width:80px"></span><!-- spacer to keep h1 visually centred -->
      <h1>履　歴　書</h1>
      <span class="print-date"><?= h($today) ?>現在</span>
    </div>

    <!-- ── Section 1: Personal Info + Photo ── -->
    <div class="section-head">基本情報</div>
    <table>
      <tr>
        <td class="label" style="width:12%">氏名（ローマ字）</td>
        <td class="val" colspan="3" style="width:38%"><?= h(val($row, 'name_romaji')) ?></td>
        <td class="label" style="width:10%">国籍</td>
        <td class="val" style="width:15%"><?= h(val($row, 'nationality')) ?></td>
        <td rowspan="8" style="width:25%; border:1px solid #333; padding:0; vertical-align:top;">
          <div class="photo-box" style="width:100%; height:50mm; float:none; margin:0;">
            <?php if ($photoHtml):
              echo $photoHtml;
            else: ?>
              <div class="photo-placeholder">写真<br>（3.5×4.5cm）</div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <tr>
        <td class="label">フリガナ</td>
        <td class="val" colspan="3"><?= h(val($row, 'name_kana')) ?></td>
        <td class="label">性別</td>
        <td class="val"><?= h(val($row, 'gender')) ?></td>
      </tr>
      <tr>
        <td class="label">生年月日</td>
        <td class="val num" style="width:8%"><?= h(val($row, 'dob_year')) ?></td>
        <td style="width:2%;border:none;text-align:center">年</td>
        <td class="val" style="width:8%"><?= h(val($row, 'dob_month')) ?>月 <?= h(val($row, 'dob_day')) ?>日</td>
        <td class="label">宗教</td>
        <td class="val"><?= h(val($row, 'religion')) ?></td>
      </tr>
      <tr>
        <td class="label">年齢</td>
        <td class="val num" colspan="3"><?= h(val($row, 'age_autofill')) ?> 歳</td>
        <td class="label">婚姻状況</td>
        <td class="val"><?= h(val($row, 'marital_status')) ?></td>
      </tr>
      <tr>
        <td class="label">出生地</td>
        <td class="val" colspan="3"><?= h(val($row, 'birthplace')) ?></td>
        <td class="label">連絡先</td>
        <td class="val"><?= h(val($row, 'contact_phone')) ?></td>
      </tr>
      <tr>
        <td class="label">郵便番号</td>
        <td class="val" colspan="3"><?= h(val($row, 'postal')) ?></td>
        <td class="label">メール</td>
        <td class="val" style="font-size:7pt;"><?= h(val($row, 'email')) ?></td>
      </tr>
      <tr>
        <td class="label">住所</td>
        <td class="val" colspan="3"><?= h(trim(val($row, 'address_auto') . ' ' . val($row, 'address'))) ?></td>
        <td class="label">身長 / 体重</td>
        <td class="val"><?= h(val($row, 'height_cm')) ?> cm / <?= h(val($row, 'weight_kg')) ?> kg</td>
      </tr>
      <tr>
        <td class="label">旅券</td>
        <td class="val" colspan="3">
          <?= h(val($row, 'passport_has')) ?>
          <?php $pexp = trim(($row['passport_exp_year'] ?? '') . '年' . ($row['passport_exp_month'] ?? '') . '月' . ($row['passport_exp_day'] ?? '') . '日');
          if (strlen($pexp) > 3): ?>
            ／期限: <?= h($pexp) ?> ／番号: <?= h(val($row, 'passport_no')) ?>
          <?php endif; ?>
        </td>
        <td class="label">海外渡航回数</td>
        <td class="val num"><?= h(val($row, 'past_travel_count')) ?></td>
      </tr>
    </table>

    <!-- ── Visa / Status ── -->
    <div class="section-head" style="margin-top:2mm;">在留資格</div>
    <table>
      <tr>
        <td class="label" style="width:12%">現在の在留資格</td>
        <td class="val" style="width:28%"><?= h(val($row, 'current_status')) ?></td>
        <td class="label" style="width:12%">在留期間（開始）</td>
        <td class="val num" style="width:15%"><?php
        $sfY = val($row, 'status_from_year');
        $sfM = val($row, 'status_from_month');
        $sfD = val($row, 'status_from_day');
        echo ($sfY !== '' || $sfM !== '' || $sfD !== '')
          ? h($sfY) . ($sfY !== '' ? '年' : '') . h($sfM) . ($sfM !== '' ? '月' : '') . h($sfD) . ($sfD !== '' ? '日' : '')
          : '—';
        ?></td>
        <td class="label" style="width:10%">（終了）</td>
        <td class="val num"><?php
        $stY = val($row, 'status_to_year');
        $stM = val($row, 'status_to_month');
        $stD = val($row, 'status_to_day');
        echo ($stY !== '' || $stM !== '' || $stD !== '')
          ? h($stY) . ($stY !== '' ? '年' : '') . h($stM) . ($stM !== '' ? '月' : '') . h($stD) . ($stD !== '' ? '日' : '')
          : '—';
        ?></td>
      </tr>
      <tr>
        <td class="label">最近の入国</td>
        <td class="val num"><?php
        $reY = val($row, 'recent_entry_year');
        $reM = val($row, 'recent_entry_month');
        $reD = val($row, 'recent_entry_day');
        echo ($reY !== '' || $reM !== '' || $reD !== '')
          ? h($reY) . ($reY !== '' ? '年' : '') . h($reM) . ($reM !== '' ? '月' : '') . h($reD) . ($reD !== '' ? '日' : '')
          : '—';
        ?></td>
        <td class="label">最近の出国</td>
        <td class="val num" colspan="3"><?php
        $rxY = val($row, 'recent_exit_year');
        $rxM = val($row, 'recent_exit_month');
        $rxD = val($row, 'recent_exit_day');
        echo ($rxY !== '' || $rxM !== '' || $rxD !== '')
          ? h($rxY) . ($rxY !== '' ? '年' : '') . h($rxM) . ($rxM !== '' ? '月' : '') . h($rxD) . ($rxD !== '' ? '日' : '')
          : '—';
        ?></td>
      </tr>
    </table>

    <!-- ── Education ── -->
    <div class="section-head" style="margin-top:2mm;">学歴</div>
    <table>
      <thead>
        <tr>
          <th style="width:14%">入学年月</th>
          <th style="width:14%">卒業年月</th>
          <th style="width:42%">学校・機関名</th>
          <th>学部・専攻 / 状況</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 1; $i <= 8; $i++):
          $e = edu_row($row, $i); ?>
          <?php if ($e['institution'] !== '' || $e['from'] !== '' || $e['to'] !== ''): ?>
            <tr>
              <td class="num"><?= h($e['from']) ?></td>
              <td class="num"><?= h($e['to']) ?></td>
              <td><?= h($e['institution']) ?></td>
              <td><?= h($e['faculty']) ?><?= $e['status'] !== '' ? '（' . $e['status'] . '）' : '' ?></td>
            </tr>
          <?php endif; ?>
        <?php endfor; ?>
      </tbody>
    </table>

    <!-- ── Licenses ── -->
    <div class="section-head" style="margin-top:2mm;">免許・資格</div>
    <table>
      <thead>
        <tr>
          <th style="width:10%">取得年</th>
          <th style="width:8%">取得月</th>
          <th>資格・免許名</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 1; $i <= 8; $i++):
          $l = lic_row($row, $i); ?>
          <?php if ($l['name'] !== '' || $l['year'] !== ''): ?>
            <tr>
              <td class="num"><?= h($l['year']) ?></td>
              <td class="num"><?= h($l['month']) ?></td>
              <td><?= h($l['name']) ?></td>
            </tr>
          <?php endif; ?>
        <?php endfor; ?>
      </tbody>
    </table>

    <!-- ── Work History ── -->
    <div class="section-head" style="margin-top:2mm;">職歴</div>
    <table>
      <thead>
        <tr>
          <th style="width:13%">開始年月</th>
          <th style="width:10%">在職状況</th>
          <th style="width:13%">終了年月</th>
          <th style="width:28%">会社・施設名</th>
          <th style="width:14%">職種 / 役職</th>
          <th>仕事内容</th>
        </tr>
      </thead>
      <tbody>
        <?php for ($i = 1; $i <= 8; $i++):
          $w = work_row($row, $i); ?>
          <?php if ($w['org'] !== '' || $w['from'] !== ''): ?>
            <tr>
              <td class="num"><?= h($w['from']) ?></td>
              <td><?= h($w['status']) ?></td>
              <td class="num"><?= h($w['to']) ?></td>
              <td><?= h($w['org']) ?></td>
              <td><?= h($w['job_title']) ?></td>
              <td style="white-space:pre-line;font-size:7pt;"><?= h($w['desc']) ?></td>
            </tr>
          <?php endif; ?>
        <?php endfor; ?>
      </tbody>
    </table>

    <?php $rr = trim((string) ($row['reason_for_resignation'] ?? ''));
    if ($rr !== ''): ?>
      <table style="margin-top:1mm;">
        <tr>
          <td class="label" style="width:15%">退職理由</td>
          <td class="val"><?= h($rr) ?></td>
        </tr>
      </table>
    <?php endif; ?>

    <!-- ── Self PR / Motivation / Preferences ── -->
    <div class="section-head" style="margin-top:2mm;">自己PR・志望動機</div>
    <table>
      <tr>
        <td class="label" style="width:12%; vertical-align:top;">自己PR</td>
        <td class="val textarea-cell"><?= h(val($row, 'self_pr')) ?></td>
      </tr>
      <tr>
        <td class="label" style="vertical-align:top;">志望の動機</td>
        <td class="val textarea-cell"><?= h(val($row, 'motivation')) ?></td>
      </tr>
      <tr>
        <td class="label" style="vertical-align:top;">本人希望</td>
        <td class="val textarea-cell"><?= h(val($row, 'preferences')) ?></td>
      </tr>
    </table>

    <!-- ── Lifestyle / Additional ── -->
    <div class="section-head" style="margin-top:2mm;">別途情報</div>
    <table>
      <tr>
        <th style="width:16.6%">日本語コミュニケーション</th>
        <th style="width:16.6%">漢字の読み書き</th>
        <th style="width:16.6%">血液型</th>
        <th style="width:16.6%">英語レベル</th>
        <th style="width:16.6%">日本に知人・友人</th>
        <th>日本に同国人の友人</th>
      </tr>
      <tr>
        <td class="val num"><?= h(val($row, 'jp_comm_level')) ?></td>
        <td class="val num"><?= h(val($row, 'kanji_rw')) ?></td>
        <td class="val num"><?= h(val($row, 'blood_type')) ?></td>
        <td class="val num"><?= h(val($row, 'english_level')) ?></td>
        <td class="val num"><?= h(val($row, 'acquaintances_in_japan')) ?>（<?= h(val($row, 'jp_friends_count')) ?>）</td>
        <td class="val num"><?= h(val($row, 'home_country_friends_in_japan')) ?></td>
      </tr>
      <tr>
        <th>喫煙</th>
        <th>飲酒</th>
        <th>タトゥー</th>
        <th>服のサイズ</th>
        <th>靴のサイズ</th>
        <th>仕事の継続希望</th>
      </tr>
      <tr>
        <td class="val num"><?= h(val($row, 'smoking')) ?></td>
        <td class="val num"><?= h(val($row, 'alcohol')) ?></td>
        <td class="val num"><?= h(val($row, 'tattoo')) ?></td>
        <td class="val num"><?= h(val($row, 'clothes_size')) ?></td>
        <td class="val num"><?= h(val($row, 'shoe_size')) ?></td>
        <td class="val num"><?= h(val($row, 'work_duration_intent')) ?></td>
      </tr>
      <tr>
        <th>お祈り</th>
        <th>断食</th>
        <th>食べ物の制限</th>
        <th>ヒジャブ</th>
        <th>日本語勉強中</th>
        <th>専門職勉強中</th>
      </tr>
      <tr>
        <td class="val num"><?= h(val($row, 'prayer')) ?></td>
        <td class="val num"><?= h(val($row, 'fasting')) ?></td>
        <td class="val num"><?= h(val($row, 'food_rules')) ?></td>
        <td class="val num"><?= h(val($row, 'hijab')) ?></td>
        <td class="val num"><?= h(val($row, 'studying_japanese_now')) ?></td>
        <td class="val num"><?= h(val($row, 'studying_specialty_now')) ?></td>
      </tr>
      <?php $oai = trim((string) ($row['other_agency_or_facility_interview'] ?? ''));
      if ($oai !== ''): ?>
        <tr>
          <td class="label" colspan="2">別の送り出し/施設面接</td>
          <td class="val" colspan="4"><?= h($oai) ?></td>
        </tr>
      <?php endif; ?>
    </table>

    <!-- ── Signature footer ── -->
    <!-- removed per user request -->

  </div><!-- /page -->

  <script>
    const params = new URLSearchParams(location.search);
    if (params.get('print') === '1') {
      window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    }
  </script>
</body>

</html>