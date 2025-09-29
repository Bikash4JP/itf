<?php
// /home/it-future/www/itf/php/rireki_list.php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Filesystem source for generated resumes (JSON snapshots live here)
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
$resumeDir = $docroot . '/rireki/kaigo/resumes';

// Gather rows from *.json (skip unreadable/bad)
$rows = [];
$nationalities = [];

if (is_dir($resumeDir)) {
    $files = glob($resumeDir . '/*.json');
    // newest → oldest by mtime
    usort($files, function($a,$b){ return filemtime($b) <=> filemtime($a); });

    foreach ($files as $jsonPath) {
        $token = basename($jsonPath, '.json');
        $mt    = @filemtime($jsonPath) ?: 0;

        $raw = @file_get_contents($jsonPath);
        if ($raw === false) continue;
        $data = json_decode($raw, true);
        if (!is_array($data)) continue;

        // name: prefer romaji then kana
        $name = '';
        foreach (['name_romaji','name_kana','personal.name_kanji','personal.name_kana'] as $k) {
            // support "dot path"
            $v = $data;
            foreach (explode('.', $k) as $p) {
                if (!is_array($v) || !array_key_exists($p, $v)) { $v = null; break; }
                $v = $v[$p];
            }
            if (!empty($v)) { $name = trim((string)$v); break; }
        }
        if ($name === '') $name = '(no name)';

        // nationality
        $nat = '';
        foreach (['nationality','personal.nationality'] as $k) {
            $v = $data;
            foreach (explode('.', $k) as $p) {
                if (!is_array($v) || !array_key_exists($p, $v)) { $v = null; break; }
                $v = $v[$p];
            }
            if (!empty($v)) { $nat = trim((string)$v); break; }
        }
        if ($nat === '') $nat = '—';

        $nationalities[$nat] = true;

        // file presence
        $xlsPath = $resumeDir . '/' . $token . '.xls';
        $pdfPath = $resumeDir . '/' . $token . '.pdf';
        $xlsUrl  = '/rireki/kaigo/resumes/' . $token . '.xls';
        $pdfUrl  = is_file($pdfPath)
                 ? '/rireki/kaigo/resumes/' . $token . '.pdf'
                 : '/rireki/kaigo/php/submit_rireki.php?download=pdf&token=' . urlencode($token); // on-demand

        $rows[] = [
            'token' => $token,
            'name'  => $name,
            'nat'   => $nat,
            'date'  => date('Y/m/d', $mt),
            'xls_exists' => is_file($xlsPath),
            'pdf_exists' => is_file($pdfPath),
            'xls_url' => $xlsUrl,
            'pdf_url' => $pdfUrl,
        ];
    }
}
ksort($nationalities, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>履歴書一覧（介護フォーマット）</title>
  <link rel="stylesheet" href="../css/staffdb.css">
  <style>
    body { font-family: ui-sans-serif, system-ui, "Noto Sans JP", Meiryo, Arial, sans-serif; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 18px; }
    h1 { font-size: 20px; margin: 0 0 14px; }
    .filters { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
    .filters input, .filters select { padding:8px 10px; border:1px solid #dbe3ef; border-radius:8px; }
    table { width:100%; border-collapse:collapse; }
    thead th { font-weight:600; text-align:left; padding:10px 8px; border-bottom:1px solid #111; }
    tbody td { padding:12px 8px; border-bottom:1px solid #eee; vertical-align:middle; }
    .name { font-weight:500; }
    .actions a { display:inline-block; padding:6px 10px; border:1px solid #dbe3ef; border-radius:8px; text-decoration:none; margin-right:6px; background:#f7fbff }
    .muted { color:#94a3b8; }
    .empty { padding:16px; color:#64748b; }
  </style>
</head>
<body>
  <header>
    <div class="logo"><a href="https://it-future.jp/"><img src="../images/logo.png" alt="ITF Logo"></a></div>
    <nav>
      <ul>
        <li><a href="../staffdb.php">ホーム</a></li>
        <li><a href="#" onclick="showForm('posts')">投稿を追加</a></li>
        <li><a href="#" onclick="showForm('jobs')">求人を追加</a></li>
        <li><a href="manage_posts.php">投稿を管理</a></li>
        <li><a href="searcch.php">検索</a></li>
        <li><a href="logout.php">ログアウト</a></li>
      </ul>
    </nav>
  </header>

  <div class="wrap">
    <h1>履歴書一覧（介護フォーマット）</h1>

    <div class="filters">
      <input type="text" id="q" placeholder="氏名で検索..." oninput="applyFilter()">
      <label>国籍:
        <select id="nat" onchange="applyFilter()">
          <option value="">すべて</option>
          <?php foreach ($nationalities as $nat => $t): ?>
            <option value="<?=htmlspecialchars($nat, ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($nat, ENT_QUOTES,'UTF-8')?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <?php if (empty($rows)): ?>
      <div class="empty">履歴書のスナップショットが見つかりませんでした（/rireki/kaigo/resumes/*.json）。</div>
    <?php else: ?>
      <table id="tbl">
        <thead>
          <tr>
            <th style="width:80px;">SN</th>
            <th>Applicant Name</th>
            <th style="width:220px;">Nationality</th>
            <th style="width:160px;">Created date</th>
            <th style="width:280px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; foreach ($rows as $r): ?>
          <tr data-name="<?=htmlspecialchars(mb_strtolower($r['name']), ENT_QUOTES,'UTF-8')?>"
              data-nat="<?=htmlspecialchars($r['nat'], ENT_QUOTES,'UTF-8')?>">
            <td><?=$i++?></td>
            <td class="name"><?=nl2br(htmlspecialchars($r['name'], ENT_QUOTES,'UTF-8'))?></td>
            <td><?=htmlspecialchars($r['nat'], ENT_QUOTES,'UTF-8')?></td>
            <td>Ex. <?=htmlspecialchars($r['date'], ENT_QUOTES,'UTF-8')?></td>
            <td class="actions">
              <?php if ($r['xls_exists']): ?>
                <a href="<?=$r['xls_url']?>" target="_blank">View XLS</a>
                <a href="<?=$r['xls_url']?>" download>Download XLS</a>
              <?php else: ?>
                <span class="muted">XLS 無し</span>
              <?php endif; ?>

              <a href="<?=$r['pdf_url']?>" target="_blank">Download PDF</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <script>
    function applyFilter() {
      const q = (document.getElementById('q').value || '').trim().toLowerCase();
      const nat = document.getElementById('nat').value || '';
      const rows = document.querySelectorAll('#tbl tbody tr');
      rows.forEach(tr => {
        const name = tr.getAttribute('data-name') || '';
        const n    = tr.getAttribute('data-nat')  || '';
        const okQ  = !q || name.indexOf(q) !== -1;
        const okN  = !nat || n === nat;
        tr.style.display = (okQ && okN) ? '' : 'none';
      });
    }
  </script>
</body>
</html>
