<?php
// Rirekisho submit: Excel only + HTML preview (2 pages)
// - GET ?demo=1  => uses sample data
// - POST         => uses form data

// ===== Error logging harden =====
@mkdir(__DIR__ . '/../logs', 0750, true);
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_error.log');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/adapters/adapter_xlsx.php'; // rireki_render_pdf(), buildCanonicalData()

$mappingFile = __DIR__ . '/../mappings/templateA.json';
$outDir      = __DIR__ . '/../resumes/rirekisho';
@mkdir($outDir, 0750, true);

// --- Helper: split "YYYY/MM/DD" or "YYYYMMDD"
function parse_ymd($s) {
  $s = trim((string)$s);
  if ($s === '') return [null,null,null];
  $digits = preg_replace('/\D+/', '', $s);
  if (strlen($digits) >= 8) {
    $y = substr($digits, 0, 4);
    $m = substr($digits, 4, 2);
    $d = substr($digits, 6, 2);
    return [$y, $m, $d];
  }
  // fallback "YYYY/MM/DD"
  if (preg_match('/^(\d{4})[\/\-\.]?(\d{1,2})[\/\-\.]?(\d{1,2})$/', $s, $m)) {
    return [$m[1], str_pad($m[2], 2, '0', STR_PAD_LEFT), str_pad($m[3], 2, '0', STR_PAD_LEFT)];
  }
  return [null,null,null];
}

// --- Helper: split "YYYY/MM" or "YYYYMM"
function parse_ym($s) {
  $s = trim((string)$s);
  if ($s === '') return [null,null];
  $digits = preg_replace('/\D+/', '', $s);
  if (strlen($digits) >= 6) {
    $y = substr($digits, 0, 4);
    $m = substr($digits, 4, 2);
    return [$y, $m];
  }
  if (preg_match('/^(\d{4})[\/\-\.]?(\d{1,2})$/', $s, $m)) {
    return [$m[1], str_pad($m[2], 2, '0', STR_PAD_LEFT)];
  }
  return [null,null];
}

try {
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
    // Accept new compact date inputs and populate the hidden fields expected by buildCanonicalData()

    // 1) DOB "dob" -> dob_yyyy/mm/dd
    if (!empty($_POST['dob'])) {
      [$yy,$mm,$dd] = parse_ymd($_POST['dob']);
      if ($yy && $mm && $dd) {
        $_POST['dob_yyyy'] = $yy;
        $_POST['dob_mm']   = $mm;
        $_POST['dob_dd']   = $dd;
      }
    }

    // 2) Education "edu_date[]" -> edu_year[] + edu_month[]
    if (!empty($_POST['edu_date']) && is_array($_POST['edu_date'])) {
      $_POST['edu_year']  = $_POST['edu_year']  ?? [];
      $_POST['edu_month'] = $_POST['edu_month'] ?? [];
      foreach ($_POST['edu_date'] as $i => $ym) {
        [$yy,$mm] = parse_ym($ym);
        $_POST['edu_year'][$i]  = $yy ?? '';
        $_POST['edu_month'][$i] = $mm ?? '';
      }
    }

    // 3) Experience "exp_date[]" -> exp_year[] + exp_month[]
    if (!empty($_POST['exp_date']) && is_array($_POST['exp_date'])) {
      $_POST['exp_year']  = $_POST['exp_year']  ?? [];
      $_POST['exp_month'] = $_POST['exp_month'] ?? [];
      foreach ($_POST['exp_date'] as $i => $ym) {
        [$yy,$mm] = parse_ym($ym);
        $_POST['exp_year'][$i]  = $yy ?? '';
        $_POST['exp_month'][$i] = $mm ?? '';
      }
    }

    // 4) Licenses "lic_date[]" -> lic_year[] + lic_month[]
    if (!empty($_POST['lic_date']) && is_array($_POST['lic_date'])) {
      $_POST['lic_year']  = $_POST['lic_year']  ?? [];
      $_POST['lic_month'] = $_POST['lic_month'] ?? [];
      foreach ($_POST['lic_date'] as $i => $ym) {
        [$yy,$mm] = parse_ym($ym);
        $_POST['lic_year'][$i]  = $yy ?? '';
        $_POST['lic_month'][$i] = $mm ?? '';
      }
    }

    $data = buildCanonicalData($_POST ?? [], $_FILES ?? null);
  }

  // Token (64-hex)
  $token = bin2hex(random_bytes(32));

  // Render (Excel + HTML only)
  $res = rireki_render_pdf($data, $mappingFile, $outDir, $token);
  if (empty($res['ok'])) {
    throw new RuntimeException('Rirekisho build failed: ' . ($res['err'] ?? 'unknown error'));
  }

  $xlsDlUrl = './download_rireki.php?token=' . urlencode($token) . '&fmt=xls';

  // simple sanitizer for inline preview
  $leftHtml  = $res['html_left']  ?? '';
  $rightHtml = $res['html_right'] ?? '';
  $sanitize = function($s) {
    $s = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i','',$s);
    $s = preg_replace('/@page\s*\{[^}]*\}/i','',$s);
    return $s;
  };
  $leftHtml  = $sanitize($leftHtml);
  $rightHtml = $sanitize($rightHtml);

} catch (Throwable $ex) {
  error_log('[submit_rireki] ' . $ex->getMessage());
  header('Content-Type: text/plain; charset=UTF-8', true, 500);
  echo 'Rirekisho build failed: ' . $ex->getMessage();
  exit;
}

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
    .page { border: 1px solid #eee; border-radius: 8px; padding: 12px; background: #fafafa; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
    .page h2 { margin: 0 0 8px; font-size: 14px; color: #333; }
    .sheet { overflow: auto; background: white; padding: 8px; border: 1px dashed #e0e0e0; border-radius: 6px; }
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
    <p class="note">※ プレビューはテンプレのセル幅・行高・結合・罫線を再現しています。印刷はExcelのプレビューでご確認ください（A1〜O86 / P1〜X86）。</p>

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
