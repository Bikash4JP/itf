<?php
// /rireki/basic/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php';
require_once __DIR__ . '/validators.php';

// mapping + outdir (local to basic)
$mappingFile = rireki_path('mappings/templateA.json');
$outDir      = rireki_path('resumes');
@mkdir($outDir, 0750, true);

// Build data
if (isset($_GET['demo'])) {
  $data = [
    'personal'   => ['name_kana'=>'やまだ たろう','name_kanji'=>'山田 太郎','dob_yyyy'=>'1998','dob_mm'=>'04','dob_dd'=>'01','age'=>'27','gender'=>'男'],
    'address'    => ['kana'=>'とうきょうと〜','postcode'=>'123-4567','full'=>'東京都千代田区1-2-3'],
    'contact'    => ['phone'=>'090-0000-0000', 'email'=>'taro@example.com'],
    'education'  => [['year'=>'2017','month'=>'04','school'=>'〇〇高校 入学'], ['year'=>'2021','month'=>'03','school'=>'△△大学 卒業']],
    'experience' => [['year'=>'2021','month'=>'04','company'=>'ABC株式会社','title'=>'エンジニア']],
    'licenses'   => [['year'=>'2020','month'=>'12','certificate'=>'基本情報技術者']],
    'pr'         => ['self_pr'=>'責任感が強く、学習意欲が高いです。'],
    'preferences'=> ['hopes'=>'東京23区での勤務を希望'],
  ];
} else {
  $data = buildCanonicalData($_POST, $_FILES);
}

$token = bin2hex(random_bytes(16));
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
if (!$res['ok']) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Rirekisho build failed: " . ($res['err'] ?? 'unknown');
  exit;
}

// links
$xlsName = basename($res['xls']);
$xlsUrl  = '/rireki/basic/resumes/' . $xlsName;

$htmlLeft  = $res['html_left'] ?? '';
$htmlRight = $res['html_right'] ?? '';
?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書 生成完了（Excel 2ページ・プレビュー）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, "Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo, Arial, sans-serif; margin: 20px; }
    .card { max-width: 1000px; margin: 0 auto; background:#fff; border:1px solid #e8eef6; border-radius: 12px; padding: 18px; }
    h1 { margin: 0 0 8px; font-size: 20px; }
    .links a { display:inline-block; margin-right: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #dbe7f5; background: #f3f9ff; color:#0c4a7a; }
    .links a:hover{ background:#e9f5ff; }
    .note { color:#667085; font-size:12px; margin-top:6px; }
    .preview{ display:grid; grid-template-columns: 1fr; gap:16px; margin-top:16px; }
    .page{ padding:12px; border:1px solid #eee; border-radius: 10px; background:#fafafa; overflow:auto; }
    .page h2{ margin:0 0 6px; font-size:14px; color:#333; }
  </style>
</head>
<body>
  <div class="card">
    <h1>履歴書を生成しました（basic）</h1>
    <div class="links">
      <a href="<?=$xlsUrl?>" download>Excel（.xls）をダウンロード</a>
      <a href="/rireki/index.php">別のフォーマットを選ぶ</a>
    </div>
    <p class="note">※ プレビューはExcelの見た目をそのままブラウザ表示しています（罫線/結合/サイズ反映）。</p>

    <div class="preview">
      <div class="page">
        <h2>ページ 1</h2>
        <?=$htmlLeft?>
      </div>
      <div class="page">
        <h2>ページ 2</h2>
        <?=$htmlRight?>
      </div>
    </div>
  </div>
</body>
</html>
