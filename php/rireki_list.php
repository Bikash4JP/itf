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

// DB for job title lookup (when only job_id is saved)
require_once __DIR__ . '/db_connect.php';

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
  require_once $adapterPath;
  $loaded = true;
  return true;
}

// Build XLS from JSON snapshot if missing (best-effort) — PDF removed from UI
function _backfill_from_json_safe(string $token, string $resumeDir, string $mappingFile, string $tmpDir, string $adapterPath): array {
  $json = $resumeDir . '/' . $token . '.json';
  $xls  = $resumeDir . '/' . $token . '.xls';
  $hasXls = is_readable($xls);

  if (!is_readable($json)) return [$hasXls];

  $raw  = @file_get_contents($json);
  $data = $raw !== false ? @json_decode($raw, true) : null;
  if (!is_array($data)) return [$hasXls];

  if (!$hasXls) {
    if (_maybe_load_adapter_once($adapterPath) && function_exists('rireki_render_xls_only')) {
      try {
        @mkdir($resumeDir, 0755, true);
        $res = rireki_render_xls_only($data, $mappingFile, $resumeDir, $token);
        if (!empty($res['ok'])) $hasXls = true;
      } catch (Throwable $e) { /* ignore */ }
    }
  }
  return [$hasXls];
}

function _normalize_jlpt(?string $s): string {
  $s = trim((string)$s);
  if ($s === '') return '';
  if (preg_match('/N\s*([1-5])/i', $s, $m)) return 'N' . strtoupper($m[1]);
  if (preg_match('/JFT/i', $s)) return 'JFT A2';
  if (preg_match('/N([1-5])\s*相当/u', $s, $m)) return 'N' . $m[1];
  return $s;
}
function _has_experience(array $meta): bool {
  if (!empty($meta['work_blocks']) && is_array($meta['work_blocks'])) {
    foreach ($meta['work_blocks'] as $r) {
      if (trim(implode('', array_map(fn($v)=> (string)$v, $r))) !== '') return true;
    }
  }
  if (!empty($meta['experience']) && is_array($meta['experience'])) {
    foreach ($meta['experience'] as $r) {
      if (trim(implode('', array_map(fn($v)=> (string)$v, $r))) !== '') return true;
    }
  }
  return false;
}

// job title lookup cache
$_JOB_TITLE_CACHE = [];
function _job_title_by_id(PDO $pdo, int $jobId): ?string {
  global $_JOB_TITLE_CACHE;
  if ($jobId <= 0) return null;
  if (isset($_JOB_TITLE_CACHE[$jobId])) return $_JOB_TITLE_CACHE[$jobId];
  $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
  $stmt->execute([$jobId]);
  $t = $stmt->fetchColumn();
  $_JOB_TITLE_CACHE[$jobId] = $t ?: null;
  return $_JOB_TITLE_CACHE[$jobId];
}

// Robust source detector
function _detect_source(array $meta, PDO $pdo): array {
  $srcKeyCandidates = [
    $meta['source']      ?? null,
    $meta['_source']     ?? null,
    $meta['origin']      ?? null,
    $meta['via']         ?? null,
    $meta['created_via'] ?? null,
  ];
  $srcKeyCandidates = array_map(fn($v) => is_string($v) ? strtolower(trim($v)) : '', $srcKeyCandidates);

  $jobId  = (int)($meta['job_id'] ?? ($meta['_job_id'] ?? 0));
  $jTitle = trim((string)($meta['job_title'] ?? ($meta['_job_title'] ?? '')));

  // original upload file rel path (for print/download if needed)
  $origRel = '';
  if (!empty($meta['_upload']) && is_array($meta['_upload'])) {
    $origRel = (string)($meta['_upload']['rel_path'] ?? '');
  } elseif (!empty($meta['upload']) && is_array($meta['upload'])) {
    $origRel = (string)($meta['upload']['rel_path'] ?? '');
  }

  // Decide src_type
  $srcType = 'open';
  $jobish = ['job', 'from_job', 'job_flow', 'resume_job', 'via_job'];
  if ($jobId > 0) {
    $srcType = 'job';
  } else {
    foreach ($srcKeyCandidates as $k) {
      if ($k !== '' && in_array($k, $jobish, true)) { $srcType = 'job'; break; }
    }
  }
  $uploadish = ['upload', 'uploaded', 'from_upload'];
  foreach ($srcKeyCandidates as $k) {
    if ($k !== '' && in_array($k, $uploadish, true)) { $srcType = 'upload'; break; }
  }
  if ($srcType === 'open' && $origRel !== '') $srcType = 'upload';

  if ($srcType === 'job' && $jTitle === '' && $jobId > 0) {
    $t = _job_title_by_id($pdo, $jobId);
    if (is_string($t) && $t !== '') $jTitle = $t;
  }

  return [
    'src_type'  => $srcType,   // 'job' | 'upload' | 'open'
    'job_id'    => $jobId,
    'job_title' => $jTitle,
    'orig_rel'  => $origRel,
  ];
}

// ---------- collect rows ----------
$rows = [];
if (is_dir($resumeDir)) {
  $list = glob($resumeDir . '/*.json'); if ($list === false) $list = [];
  foreach ($list as $jsonPath) {
    $token = basename($jsonPath, '.json');
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) continue;

    [$hasXls] = _backfill_from_json_safe($token, $resumeDir, $mappingFile, $tmpDir, $adapterPath);

    $meta = @json_decode(@file_get_contents($jsonPath), true) ?: [];
    $createdAt = @filemtime($jsonPath) ?: 0;

    // display fields
    $name = $meta['name_romaji'] ?? $meta['name_kana']
         ?? ($meta['personal']['name_kanji'] ?? ($meta['personal']['name_kana'] ?? '（名称未設定）'));
    $nat  = (string)($meta['nationality'] ?? ($meta['personal']['nationality'] ?? ''));
    $jlpt = _normalize_jlpt($meta['jp_comm_level'] ?? ($meta['personal']['jp_comm_level'] ?? ''));
    $exp  = _has_experience($meta) ? 'yes' : 'no';

    $sd = _detect_source($meta, $pdo);

    $rows[] = [
      'token'     => $token,
      'name'      => (string)$name,
      'nat'       => (string)$nat,
      'jlpt'      => (string)$jlpt,
      'exp'       => (string)$exp,
      'created'   => (int)$createdAt,
      'has_xls'   => (bool)$hasXls,
      'src_type'  => $sd['src_type'],
      'job_id'    => (int)$sd['job_id'],
      'job_title' => (string)$sd['job_title'],
      'orig_rel'  => (string)$sd['orig_rel'],
    ];
  }
}
usort($rows, fn($a,$b)=> $b['created'] <=> $a['created']);

// unique lists for filters
$nations = array_values(array_unique(array_filter(array_map(fn($r)=>$r['nat'], $rows))));
sort($nations);
$jlptOptions = ['JFT A2','N4','N3','N2','N1'];
$expOptions  = ['yes'=>'あり','no'=>'なし'];

// CSRF for delete
if (empty($_SESSION['csrf_rireki'])) {
  $_SESSION['csrf_rireki'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_rireki'];

// Build absolute host for Office viewer printing
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'it-future.jp';
$base   = $scheme . '://' . $host;
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
    header{display:flex;gap:12px;align-items:center;margin-bottom:8px}
    header h1{margin:0;font-size:20px}
    .src-tabs a{margin-right:8px;text-decoration:none;padding:6px 10px;border:1px solid #e5e7eb;border-radius:8px;color:#0b6b4a}
    .src-tabs a.active{background:#eafff3;border-color:#bbf7d0}

    .search-container { width:80%; height:50px; border:5px solid rgb(29,150,219); border-radius:60px;
      display:flex; justify-content:center; align-items:center; margin:50px 50px 10px 50px; overflow-y:hidden }
    .search-container input { width:96%; border:none; outline:none; font-size:16px; padding:6px 10px; background:transparent }

    .filters{display:flex;gap:12px;align-items:center;margin:10px 50px 16px 50px;flex-wrap:wrap}
    .dropdown{position:relative;display:inline-block}
    .dropbtn{padding:8px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;cursor:pointer}
    .dropmenu{position:absolute;z-index:5;top:calc(100% + 6px);left:0;background:#fff;border:1px solid #e5e7eb;border-radius:10px;min-width:220px;box-shadow:0 8px 20px rgba(0,0,0,.06);display:none;max-height:260px;overflow:auto;padding:8px}
    .dropmenu label{display:flex;align-items:center;gap:8px;padding:6px 4px;cursor:pointer}
    .dropmenu .actions{display:flex;gap:8px;justify-content:flex-end;margin-top:6px}
    .dropmenu .btn{padding:6px 10px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;cursor:pointer}
    .dropmenu .btn:hover{background:#e9f5ff}

    table{width:100%;border-collapse:collapse;border:1px solid #e5e7eb}
    th,td{border-bottom:1px solid #eee;padding:10px 12px;text-align:left}
    th{color:#1e90ff;font-weight:800}
    .badges{display:flex;gap:8px;flex-wrap:wrap}
    .badge{font-size:12px;padding:2px 6px;border:1px solid #dbeafe;border-radius:999px;background:#eff6ff;color:#1e40af}
    .actions{display:flex;gap:8px;flex-wrap:wrap}
    a.btn, button.btn{display:inline-block;padding:6px 10px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;text-decoration:none;cursor:pointer}
    a.btn:hover, button.btn:hover{background:#e9f5ff}
    .danger{border-color:#fecaca;background:#fee2e2;color:#991b1b}
    .danger:hover{background:#fecaca}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>生成済み履歴書一覧（<?=htmlspecialchars($source)?>）</h1>
      <div class="src-tabs">
        <a href="?src=kaigo" class="<?= $source==='kaigo'?'active':'' ?>">介護フォーマット</a>
        <a href="?src=basic" class="<?= $source==='basic'?'active':'' ?>">ベーシック</a>
      </div>
      <a class="btn" href="staffdb.php" style="margin-left:auto">← スタッフDBへ戻る</a>
    </header>

    <div class="search-container">
      <input id="q" type="text" placeholder="氏名 / 国籍 / JLPT / ソース / 日付 を検索（スペース区切り）" oninput="applyFilters()">
    </div>

    <div class="filters">
      <div class="dropdown" data-key="exp">
        <button class="dropbtn" onclick="toggleMenu(this)">経験（あり/なし）</button>
        <div class="dropmenu">
          <?php foreach (['yes'=>'あり','no'=>'なし'] as $value=>$label): ?>
            <label><input type="checkbox" value="<?=htmlspecialchars($value)?>"> <span><?=htmlspecialchars($label)?></span></label>
          <?php endforeach; ?>
          <div class="actions"><button class="btn" onclick="clearMenu(this)">クリア</button><button class="btn" onclick="closeMenu(this)">OK</button></div>
        </div>
      </div>
      <div class="dropdown" data-key="jlpt">
        <button class="dropbtn" onclick="toggleMenu(this)">JLPT / JFT</button>
        <div class="dropmenu">
          <?php foreach (['JFT A2','N4','N3','N2','N1'] as $opt): ?>
            <label><input type="checkbox" value="<?=htmlspecialchars($opt)?>"> <span><?=htmlspecialchars($opt)?></span></label>
          <?php endforeach; ?>
          <div class="actions"><button class="btn" onclick="clearMenu(this)">クリア</button><button class="btn" onclick="closeMenu(this)">OK</button></div>
        </div>
      </div>
      <div class="dropdown" data-key="nat">
        <button class="dropbtn" onclick="toggleMenu(this)">国籍で絞り込み</button>
        <div class="dropmenu" style="min-width:260px">
          <?php foreach ($nations as $n): ?>
            <label><input type="checkbox" value="<?=htmlspecialchars($n)?>"> <span><?=htmlspecialchars($n)?></span></label>
          <?php endforeach; ?>
          <div class="actions"><button class="btn" onclick="clearMenu(this)">クリア</button><button class="btn" onclick="closeMenu(this)">OK</button></div>
        </div>
      </div>
    </div>

    <table id="rirekiTable">
      <thead>
        <tr>
          <th style="width:64px">SN</th>
          <th>氏名</th>
          <th style="width:160px">国籍</th>
          <th style="width:260px">ソース</th>
          <th style="width:160px">作成日時</th>
          <th style="width:340px">ファイル / 印刷</th>
          <th style="width:180px">アクション</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7">データがありません。</td></tr>
        <?php else: $sn=1; foreach ($rows as $r):
          $token   = $r['token'];
          $xlsUrl  = "/rireki/{$source}/resumes/{$token}.xls";
          $absXls  = $base . $xlsUrl; // for Office viewer (print)
          $viewerX = 'https://view.officeapps.live.com/op/view.aspx?src=' . rawurlencode($absXls);
          $date    = $r['created'] ? date('Y-m-d H:i', $r['created']) : '-';

          // source cell: show job title link whenever job_id present
          $srcCellText = 'open';
          $srcCellHtml = 'open';
          if ($r['job_id'] > 0) {
            $jt   = $r['job_title'] !== '' ? $r['job_title'] : ('求人ID '.$r['job_id']);
            $href = '/php/job_details.php?job_id=' . (int)$r['job_id'];
            $srcCellText = $jt;
            $srcCellHtml = '<a href="'.htmlspecialchars($href).'" target="_blank">'.htmlspecialchars($jt).'</a>';
          } elseif ($r['src_type'] === 'upload') {
            $srcCellText = 'アップロード';
            $srcCellHtml = 'アップロード';
          }

          // Build print behavior:
          // - upload rows: print the ORIGINAL uploaded file
          // - others: print the generated Excel via Office viewer
          $printHref = '';
          if ($r['src_type'] === 'upload' && !empty($r['orig_rel'])) {
            $absOrig = $base . $r['orig_rel'];
            $ext = strtolower(pathinfo($r['orig_rel'], PATHINFO_EXTENSION));
            if (in_array($ext, ['xls','xlsx'], true)) {
              $printHref = 'https://view.officeapps.live.com/op/view.aspx?src=' . rawurlencode($absOrig);
            } else {
              // pdf / jpg / jpeg / png → open directly (browser print)
              $printHref = $r['orig_rel'];
            }
          } else {
            $printHref = $viewerX;
          }

          $rowText = strtolower(
            ($r['name'].' '.$r['nat'].' '.$r['jlpt'].' '.$date.' '.$srcCellText)
          );
        ?>
        <tr data-nat="<?=htmlspecialchars($r['nat'])?>"
            data-exp="<?=htmlspecialchars($r['exp'])?>"
            data-jlpt="<?=htmlspecialchars($r['jlpt'])?>"
            data-text="<?=htmlspecialchars($rowText)?>">
          <td><?= $sn++ ?></td>
          <td><?=htmlspecialchars($r['name'])?></td>
          <td><?=htmlspecialchars($r['nat'])?></td>
          <td><?= $srcCellHtml ?></td>
          <td><?=htmlspecialchars($date)?></td>
          <td>
            <div class="badges">
              <?php if ($r['src_type'] === 'upload'): ?>
                <!-- アップロード行：Excel/原本リンクは出さず、印刷のみ（原本を印刷） -->
                <?php if (!empty($printHref)): ?>
                  <a class="btn" href="<?=htmlspecialchars($printHref)?>" target="_blank" rel="noopener">印刷</a>
                <?php else: ?>
                  <span class="badge">印刷不可（原本なし）</span>
                <?php endif; ?>
              <?php else: ?>
                <!-- 通常行：Excel & 印刷（Office Viewer） -->
                <?php if ($r['has_xls']): ?>
                  <a class="btn" href="<?=$xlsUrl?>" download>Excel</a>
                  <a class="btn" href="<?=htmlspecialchars($printHref)?>" target="_blank" rel="noopener">印刷</a>
                <?php else: ?>
                  <span class="badge">Excelなし</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
          <td class="actions">
            <form method="post" action="/php/delete_rireki.php" onsubmit="return confirm('この履歴書を削除しますか？\n対応する Excel / JSON（および原本ファイルがあればそれも）が削除されます。');">
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

  <script>
    function toggleMenu(btn){
      const menu = btn.nextElementSibling;
      const open = menu.style.display === 'block';
      document.querySelectorAll('.dropmenu').forEach(m=>m.style.display='none');
      menu.style.display = open ? 'none' : 'block';
    }
    function closeMenu(el){
      const menu = el.closest('.dropmenu'); if (menu) menu.style.display = 'none';
      applyFilters();
    }
    function clearMenu(el){
      const menu = el.closest('.dropmenu'); if (!menu) return;
      menu.querySelectorAll('input[type="checkbox"]').forEach(ch=>ch.checked=false);
      applyFilters();
    }
    document.addEventListener('click', (e)=>{
      if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropmenu').forEach(m=>m.style.display='none');
      }
    });
    function getSelected(key){
      const dd = document.querySelector('.dropdown[data-key="'+key+'"]');
      if (!dd) return [];
      return Array.from(dd.querySelectorAll('.dropmenu input:checked')).map(i=>i.value.trim());
    }
    function applyFilters(){
      const q = (document.getElementById('q').value || '').toLowerCase().trim();
      const tokens = q ? q.split(/\s+/) : [];
      const expSel  = getSelected('exp');
      const jlptSel = getSelected('jlpt');
      const natSel  = getSelected('nat');
      const rows = document.querySelectorAll('#rirekiTable tbody tr');
      rows.forEach(tr=>{
        let show = true;
        if (tokens.length){
          const text = (tr.getAttribute('data-text')||'');
          for (const t of tokens){ if (!text.includes(t)) { show=false; break; } }
        }
        if (show && expSel.length){
          const v = (tr.getAttribute('data-exp')||'').trim();
          show = expSel.includes(v);
        }
        if (show && jlptSel.length){
          const v = (tr.getAttribute('data-jlpt')||'').trim();
          const norm = v.replace(/相当/u,'').trim();
          show = jlptSel.includes(norm);
        }
        if (show && natSel.length){
          const v = (tr.getAttribute('data-nat')||'').trim();
          show = natSel.includes(v);
        }
        tr.style.display = show ? '' : 'none';
      });
    }
  </script>
</body>
</html>
