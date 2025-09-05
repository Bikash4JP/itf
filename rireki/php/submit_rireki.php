<?php
// Rirekisho submit: Excel only + HTML preview (2 pages)
// - GET ?demo=1  => uses sample data
// - POST         => uses form data

ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/adapters/adapter_xlsx.php'; // rireki_render_pdf(), buildCanonicalData()

$mappingFile = __DIR__ . '/../mappings/templateA.json';
$outDir      = __DIR__ . '/../resumes/rirekisho';
@mkdir($outDir, 0750, true);

// Build $data
if (isset($_GET['demo']) && $_GET['demo'] == '1') {
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
  $data = buildCanonicalData($_POST ?? [], $_FILES ?? null);
}

// Token (64-hex)
$token = bin2hex(random_bytes(32));

// Render (now Excel + HTML only)
$res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
if (empty($res['ok'])) {
  header('Content-Type: text/plain; charset=UTF-8', true, 500);
  echo 'Rirekisho build failed: ' . ($res['err'] ?? 'unknown error');
  exit;
}

$xlsDlUrl = './download_rireki.php?token=' . urlencode($token) . '&fmt=xls';

// simple sanitizer for inline preview
$leftHtml  = $res['html_left']  ?? '';
$rightHtml = $res['html_right'] ?? '';
function safeHtml($s) {
  // We trust PhpSpreadsheet HTML; just ensure no stray @page rules / scripts
  $s = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i','',$s);
  $s = preg_replace('/@page\s*\{[^}]*\}/i','',$s);
  return $s;
}
$leftHtml  = safeHtml($leftHtml);
$rightHtml = safeHtml($rightHtml);

?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>履歴書 生成完了（Excel 2ページ・プレビュー）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans JP", "Hiragino Kaku Gothic ProN", Meiryo, sans-serif; margin: 24px; }
    .card { max-width: 980px; padding: 24px; border: 1px solid #e5e5e5; border-radius: 12px; margin: 0 auto; background: #fff; }
    h1 { margin: 0 0 12px; font-size: 20px; }
    .links a { display: inline-block; margin-right: 12px; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #ddd; background: #f6f9ff; }
    .note { color: #666; font-size: 12px; margin-top: 8px; }

    .preview { margin-top: 16px; display: grid; grid-template-columns: 1fr; gap: 16px; }
    .page {
      border: 1px solid #eee; border-radius: 8px; padding: 12px; background: #fafafa;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .page h2 { margin: 0 0 8px; font-size: 14px; color: #333; }
    .sheet { overflow: auto; background: white; padding: 8px; border: 1px dashed #e0e0e0; border-radius: 6px; }
    /* Try to mimic B5 portrait aspect in preview box */
    .sheet .grid-container { width: 100%; }
    table { border-collapse: collapse; }
    td, th { vertical-align: top; padding: 0; }
  </style>
</head>
<body>
  <div class="card">
    <h1>履歴書（Excel）を生成しました</h1>
    <div class="links">
      <a href="<?php echo htmlspecialchars($xlsDlUrl, ENT_QUOTES, 'UTF-8'); ?>">Excel(.xls)をダウンロード</a>
    </div>
    <p class="note">※ プレビューはExcelから生成したHTMLです。実際の印刷設定はExcelの印刷プレビューでご確認ください（A1〜O86 / P1〜X86 の2ページ構成）。</p>

    <div class="preview">
      <div class="page">
        <h2>Page 1（A1〜O86）</h2>
        <div class="sheet"><?php echo $leftHtml; ?></div>
      </div>
      <div class="page">
        <h2>Page 2（P1〜X86）</h2>
        <div class="sheet"><?php echo $rightHtml; ?></div>
      </div>
    </div>
  </div>
</body>
</html>
