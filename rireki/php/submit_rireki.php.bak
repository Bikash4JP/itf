<?php
// Minimal submit endpoint for Rirekisho
// - GET ?demo=1  => uses sample data
// - POST         => uses form data
// Shows success HTML with PDF/XLS download links.

ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/adapters/adapter_xlsx.php'; // provides rireki_render_pdf() + buildCanonicalData()

$mappingFile = __DIR__ . '/../mappings/templateA.json';
$outDir      = __DIR__ . '/../resumes/rirekisho';
@mkdir($outDir, 0750, true);

// -------- Build $data --------
if (isset($_GET['demo']) && $_GET['demo'] == '1') {
  // ASCII-only safe demo payload
  $data = [
    'personal'   => ['name_kana'=>'test', 'name_kanji'=>'test', 'dob_yyyy'=>'1990','dob_mm'=>'01','dob_dd'=>'01','age'=>'34','gender'=>'M'],
    'address'    => ['kana'=>'test', 'postcode'=>'123-4567', 'full'=>'Tokyo 1-2-3'],
    'contact'    => ['phone'=>'0900000000', 'email'=>'test@example.com'],
    'education'  => [['year'=>'2010','month'=>'04','school'=>'High School'], ['year'=>'2014','month'=>'03','school'=>'University']],
    'experience' => [['year'=>'2014','month'=>'04','company'=>'ABC Corp','title'=>'Engineer']],
    'licenses'   => [['year'=>'2018','month'=>'10','certificate'=>'FE']],
    'pr'         => ['self_pr'=>'Self PR'],
    'preferences'=> ['hopes'=>'Tokyo'],
  ];
} else {
  // Use adapter helper to normalize POST/FILES into the expected structure
  $data = buildCanonicalData($_POST ?? [], $_FILES ?? null);
}

// 64-hex token (matches download script regex)
$token = bin2hex(random_bytes(32));

// -------- Render --------
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
if (empty($res['ok'])) {
  header('Content-Type: text/plain; charset=UTF-8', true, 500);
  echo 'Rirekisho render failed: ' . ($res['err'] ?? 'unknown error');
  exit;
}

// -------- Success HTML --------
$pdfUrl = './download_rireki.php?token=' . urlencode($token);
$xlsUrl = './download_rireki.php?token=' . urlencode($token) . '&fmt=xls';

?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書 生成完了</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans JP", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif; margin: 24px; }
    .card { max-width: 720px; padding: 24px; border: 1px solid #e5e5e5; border-radius: 12px; }
    h1 { margin: 0 0 12px; font-size: 20px; }
    .links a { display: inline-block; margin-right: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #ddd; }
    .pdf { background: #f6fff6; }
    .xls { background: #f6f9ff; }
    .note { color: #666; font-size: 12px; margin-top: 8px; }
    embed { width: 100%; height: 70vh; border: 1px solid #eee; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="card">
    <h1>履歴書フォーマットの読み込みに成功しました</h1>
    <div class="links">
      <a class="pdf" href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>">PDFをダウンロード</a>
      <a class="xls" href="<?php echo htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8'); ?>">Excel(.xls)をダウンロード</a>
    </div>
    <p class="note">※ 日本語が□で表示される場合は <code>/home/it-future/www/itf/rireki/fonts/</code> に <code>ipaexg.ttf</code> か <code>NotoSansCJKjp-Regular.otf</code> を配置してください。</p>
    <hr />
    <embed src="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" type="application/pdf" />
  </div>
</body>
</html>
