<?php
// /home/it-future/www/itf/php/rireki_list.php
ini_set('session.cookie_path', '/itf');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

// ---------- paths ----------
$source  = isset($_GET['src']) && in_array($_GET['src'], ['kaigo','basic'], true) ? $_GET['src'] : 'kaigo';
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
$root    = $docRoot;

$resumeDir   = $root . '/rireki/' . $source . '/resumes';
$mappingFile = $root . '/rireki/' . $source . '/mappings/' . ($source === 'kaigo' ? 'templateB.json' : 'templateA.json');
$tmpDir      = $root . '/rireki/' . $source . '/tmp';
$adapterPath = $root . '/rireki/' . $source . '/php/adapters/adapter_xlsx.php';

// ---------- helpers ----------
function _maybe_load_adapter_once(string $adapterPath): bool {
  static $loaded = false;
  if ($loaded) return true;
  if (!is_readable($adapterPath)) return false;
  require_once $adapterPath; // adapter brings its own bootstrap/composer as needed
  $loaded = true;
  return true;
}

function _backfill_from_json_safe(string $token, string $resumeDir, string $mappingFile, string $tmpDir, string $adapterPath): array {
  $json = $resumeDir . '/' . $token . '.json';
  $xls  = $resumeDir . '/' . $token . '.xls';
  $pdf  = $resumeDir . '/' . $token . '.pdf';

  $hasXls = is_readable($xls);
  $hasPdf = is_readable($pdf);

  if (!is_readable($json)) return [$hasXls, $hasPdf];

  $raw  = @file_get_contents($json);
  $data = $raw !== false ? @json_decode($raw, true) : null;
  if (!is_array($data)) return [$hasXls, $hasPdf];

  // Build XLS if missing
  if (!$hasXls) {
    if (_maybe_load_adapter_once($adapterPath) && function_exists('rireki_render_xls_only')) {
      try {
        @mkdir($resumeDir, 0755, true);
        $res = rireki_render_xls_only($data, $mappingFile, $resumeDir, $token);
        if (is_array($res) && !empty($res['ok'])) $hasXls = true;
      } catch (Throwable $e) { /* ignore */ }
    }
  }
  // Build PDF if missing
  if (!$hasPdf) {
    if (_maybe_load_adapter_once($adapterPath) && function_exists('rireki_make_pdf_via_html')) {
      try {
        @mkdir($resumeDir, 0755, true);
        @mkdir($tmpDir, 0755, true);
        $res = rireki_make_pdf_via_html($data, $mappingFile, $resumeDir, $token, $tmpDir);
        if (is_array($res) && !empty($res['ok'])) $hasPdf = true;
      } catch (Throwable $e) { /* ignore */ }
    }
  }
  return [$hasXls, $hasPdf];
}

// ---------- collect rows ----------
$rows = [];
if (is_dir($resumeDir)) {
  $list = glob($resumeDir . '/*.json'); if ($list === false) $list = [];
  foreach ($list as $jsonPath) {
    $token = basename($jsonPath, '.json');
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) continue;

    // Ensure both files exist (best-effort)
    [$hasXls, $hasPdf] = _backfill_from_json_safe($token, $resumeDir, $mappingFile, $tmpDir, $adapterPath);

    $meta = @json_decode(@file_get_contents($jsonPath), true);
    $createdAt = @filemtime($jsonPath) ?: 0;

    $name = '（名称未設定）';
    $nat  = '';
    if (is_array($meta)) {
      $name = $meta['name_romaji'] ?? $meta['name_kana']
           ?? ($meta['personal']['name_kanji'] ?? ($meta['personal']['name_kana'] ?? $name));
      $nat  = $meta['nationality'] ?? '';
    }
    $rows[] = [
      'token'   => $token,
      'name'    => (string)$name,
      'nat'     => (string)$nat,
      'created' => (int)$createdAt,
      'has_xls' => (bool)$hasXls,
      'has_pdf' => (bool)$hasPdf,
    ];
  }
}
usort($rows, fn($a,$b)=> $b['created'] <=> $a['created']);

// CSRF for delete
if (empty($_SESSION['csrf_rireki'])) {
  $_SESSION['csrf_rireki'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_rireki'];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>生成済み履歴書一覧（<?=htmlspecialchars($source)?>）</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;margin:20px}
    .wrap{max-width:1100px;margin:0 auto}
    header{display:flex;gap:12px;align-items:center;margin-bottom:14px}
    header h1{margin:0;font-size:20px}
    .filters{display:flex;gap:8px;align-items:center;margin:10px 0}
    table{width:100%;border-collapse:collapse;border:1px solid #e5e7eb}
    th,td{border-bottom:1px solid #eee;padding:10px 12px;text-align:left}
    th{color:#1e90ff;font-weight:800}
    .badges{display:flex;gap:8px;flex-wrap:wrap}
    .badge{font-size:12px;padding:2px 6px;border:1px solid #dbeafe;border-radius:999px;background:#eff6ff;color:#1e40af}
    .actions{display:flex;gap:8px}
    a.btn, button.btn{display:inline-block;padding:6px 10px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;text-decoration:none;cursor:pointer}
    a.btn:hover, button.btn:hover{background:#e9f5ff}
    .danger{border-color:#fecaca;background:#fee2e2;color:#991b1b}
    .danger:hover{background:#fecaca}
    .src-tabs a{margin-right:8px;text-decoration:none;padding:6px 10px;border:1px solid #e5e7eb;border-radius:8px;color:#0b6b4a}
    .src-tabs a.active{background:#eafff3;border-color:#bbf7d0}
    select, input[type="text"]{padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px}
  </style>
  <script>
    function confirmDelete(){return confirm('この履歴書を削除しますか？\n対応する XLS / PDF / JSON が削除されます。');}
    function filterByNat(){
      const sel=document.getElementById('natFilter').value.trim();
      document.querySelectorAll('tbody tr').forEach(tr=>{
        const nat=(tr.getAttribute('data-nat')||'').trim();
        tr.style.display=(!sel||nat===sel)?'':'none';
      });
    }
    function filterByQuery(){
      const q=document.getElementById('q').value.toLowerCase();
      document.querySelectorAll('tbody tr').forEach(tr=>{
        tr.style.display=tr.textContent.toLowerCase().includes(q)?'':'none';
      });
    }
  </script>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>生成済み履歴書一覧（<?=htmlspecialchars($source)?>）</h1>
      <div class="src-tabs">
        <a href="?src=kaigo" class="<?= $source==='kaigo'?'active':'' ?>">介護フォーマット</a>
        <a href="?src=basic" class="<?= $source==='basic'?'active':'' ?>">ベーシック</a>
      </div>
      <a class="btn" href="/staffdb.php" style="margin-left:auto">← スタッフDBへ戻る</a>
    </header>

    <div class="filters">
      <label>国籍:
        <?php $nats = array_values(array_unique(array_map(fn($r)=>$r['nat']??'', $rows))); sort($nats); ?>
        <select id="natFilter" onchange="filterByNat()">
          <option value="">すべて</option>
          <?php foreach($nats as $n){ if($n==='') continue; ?>
            <option value="<?=htmlspecialchars($n)?>"><?=htmlspecialchars($n)?></option>
          <?php } ?>
        </select>
      </label>
      <label>キーワード:
        <input id="q" type="text" placeholder="氏名 / 国籍 を検索" oninput="filterByQuery()">
      </label>
    </div>

    <table>
      <thead>
        <tr>
          <th style="width:72px">SN</th>
          <th>氏名</th>
          <th style="width:160px">国籍</th>
          <th style="width:160px">作成日時</th>
          <th style="width:260px">ファイル</th>
          <th style="width:200px">アクション</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6">データがありません。</td></tr>
        <?php else: $sn=1; foreach ($rows as $r):
          $token = $r['token'];
          $xlsUrl = "/rireki/{$source}/resumes/{$token}.xls";
          $pdfUrl = "/rireki/{$source}/resumes/{$token}.pdf";
          $date = $r['created'] ? date('Y-m-d H:i', $r['created']) : '-';
        ?>
        <tr data-nat="<?=htmlspecialchars($r['nat'])?>">
          <td><?= $sn++ ?></td>
          <td><?=htmlspecialchars($r['name'])?></td>
          <td><?=htmlspecialchars($r['nat'])?></td>
          <td><?=htmlspecialchars($date)?></td>
          <td>
            <div class="badges">
              <?php if ($r['has_xls']): ?>
                <a class="btn" href="<?=$xlsUrl?>" download>Excel</a>
              <?php else: ?>
                <span class="badge">Excelなし</span>
              <?php endif; ?>
              <?php if ($r['has_pdf']): ?>
                <a class="btn" href="<?=$pdfUrl?>" download>PDF</a>
              <?php else: ?>
                <span class="badge">PDFなし</span>
              <?php endif; ?>
            </div>
          </td>
          <td class="actions">
            <form method="post" action="/php/delete_rireki.php" onsubmit="return confirmDelete()">
              <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrfToken)?>">
              <input type="hidden" name="src"  value="<?=htmlspecialchars($source)?>">
              <input type="hidden" name="token" value="<?=htmlspecialchars($token)?>">
              <button type="submit" class="btn danger">削除</button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
