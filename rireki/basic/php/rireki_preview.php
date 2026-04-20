<?php
// /home/it-future/www/itf/rireki/basic/php/rireki_preview.php
// ✅ Upgraded: kaigo-style design, login integration, Excel + PDF download

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/php/db_connect.php';

// Optional auth helpers
$HAS_USER_AUTH = false;
if (is_file(__DIR__ . '/../../../php/user_auth.php')) {
  require_once __DIR__ . '/../../../php/user_auth.php';
  $HAS_USER_AUTH = function_exists('app_is_logged_in') && function_exists('app_user_id');
}
if ($HAS_USER_AUTH) app_ensure_tables($pdo);

$session_uid = ($HAS_USER_AUTH && app_is_logged_in()) ? (int)app_user_id() : 0;

function h($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function keep($name, $value) {
  if (is_array($value)) {
    $html = '';
    foreach ($value as $k => $v) $html .= keep($name . '[' . $k . ']', $v);
    return $html;
  }
  return '<input type="hidden" name="'.h($name).'" value="'.h($value).'">'."\n";
}
function moveTempPhoto(?array $file): ?string {
  if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $dir = $_SERVER['DOCUMENT_ROOT'] . '/rireki/uploads/tmp';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) return null;
  return '/rireki/uploads/tmp/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /rireki/basic/rireki.php', true, 302);
  exit;
}

$post = $_POST;
$photoPath = $post['photo_path'] ?? null;
if (!$photoPath && isset($_FILES['photo'])) {
  $tmp = moveTempPhoto($_FILES['photo']);
  if ($tmp) { $photoPath = $tmp; $post['photo_path'] = $photoPath; }
}

// ── Build preview data ────────────────────────────────────────────
$step1 = [
  'フリガナ'    => $post['personal_name_kana'] ?? '',
  '氏名'        => $post['personal_name_kanji'] ?? '',
  '生年月日'    => trim(($post['dob_yyyy'] ?? '').'/'.($post['dob_mm'] ?? '').'/'.($post['dob_dd'] ?? ''), ' /'),
  '年齢（自動）'=> $post['age'] ?? '',
  '性別'        => $post['gender'] ?? '',
  '住所（フリガナ）' => $post['address_kana'] ?? '',
  '郵便番号'    => $post['postcode'] ?? '',
  '住所'        => $post['address_full'] ?? '',
  '電話番号'    => $post['phone'] ?? '',
  'Eメール'     => $post['email'] ?? '',
];

$eduRows = [];
$ys=$post['edu_start_year']??[]; $ms=$post['edu_start_month']??[]; $sc=$post['edu_school_name']??[];
$fa=$post['edu_faculty']??[]; $lv=$post['edu_level']??[]; $st=$post['edu_status']??[];
$ye=$post['edu_end_year']??[]; $me=$post['edu_end_month']??[];
$N=max(count($ys),count($ms),count($sc),count($fa),count($lv),count($st),count($ye),count($me));
for($i=0;$i<$N;$i++){$eduRows[]=['開始'=>trim(($ys[$i]??'').'/'.($ms[$i]??''),' /'),
  '学校名'=>$sc[$i]??'','学部・学科'=>$fa[$i]??'','区分'=>$lv[$i]??'','在学状況'=>$st[$i]??'',
  '終了'=>trim(($ye[$i]??'').'/'.($me[$i]??''),' /')];}

$workRows = [];
$sY=$post['exp_start_year']??[]; $sM=$post['exp_start_month']??[]; $co=$post['exp_company']??[];
$ti=$post['exp_title']??[]; $es=$post['exp_status']??[]; $eY=$post['exp_end_year']??[]; $eM=$post['exp_end_month']??[];
$W=max(count($sY),count($sM),count($co),count($ti),count($es),count($eY),count($eM));
for($i=0;$i<$W;$i++){$workRows[]=['開始'=>trim(($sY[$i]??'').'/'.($sM[$i]??''),' /'),
  '会社名'=>$co[$i]??'','役職/職種'=>$ti[$i]??'','在職状況'=>$es[$i]??'',
  '終了'=>trim(($eY[$i]??'').'/'.($eM[$i]??''),' /')];}

$licRows = [];
$ly=$post['lic_year']??[]; $lm=$post['lic_month']??[]; $ln=$post['lic_name']??[];
$L=max(count($ly),count($lm),count($ln));
for($i=0;$i<$L;$i++){$licRows[]=['取得年'=>$ly[$i]??'','取得月'=>$lm[$i]??'','資格名'=>$ln[$i]??''];}

$step5 = ['志望動機・自己PR'=>$post['self_pr']??'','本人希望記入欄'=>$post['hopes']??''];
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>プレビュー｜履歴書（標準）</title>
  <meta name="robots" content="noindex,nofollow">
  <style>
    :root{
      --sky:#1e90ff;--sky-d:#0264c8;--ink:#0b2243;--muted:#64748b;
      --line:#e6f2fb;--bg:#f6fbff;--card:#fff;--border:#e0ecf9;
      --shadow:0 10px 30px rgba(2,100,200,.08);--radius:16px;
      --header-h:64px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:var(--bg);font-family:ui-sans-serif,system-ui,"Noto Sans JP",Meiryo,Arial,sans-serif;
         color:var(--ink);padding-top:calc(var(--header-h) + 14px)}
    header{position:fixed;top:0;left:0;right:0;height:var(--header-h);z-index:900;
           background:linear-gradient(135deg,#0264c8,#1e90ff);
           box-shadow:0 2px 16px rgba(2,100,200,.25);display:flex;align-items:center}
    .hdr-inner{max-width:1200px;margin:0 auto;width:100%;padding:0 18px;
               display:flex;align-items:center;justify-content:space-between;gap:12px}
    .hdr-title{color:#fff;font-size:18px;font-weight:900;letter-spacing:.4px}
    .hdr-sub{color:rgba(255,255,255,.75);font-size:12px;margin-top:2px}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;
         padding:9px 14px;border-radius:10px;font-weight:800;font-size:13px;
         text-decoration:none;cursor:pointer;transition:.15s;border:1px solid var(--border)}
    .btn.primary{background:var(--sky-d);color:#fff;border-color:var(--sky-d)}
    .btn.primary:hover{filter:brightness(.92)}
    .btn:not(.primary){background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.35)}
    .btn:not(.primary):hover{background:rgba(255,255,255,.25)}

    /* Layout */
    .wrap{max-width:1200px;margin:0 auto;padding:16px 18px}
    main.layout{display:grid;grid-template-columns:1fr 320px;gap:18px;align-items:start}
    @media(max-width:900px){main.layout{grid-template-columns:1fr}}

    /* Sections */
    .section{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
             overflow:hidden;box-shadow:var(--shadow);margin-bottom:16px}
    .section-head{background:linear-gradient(90deg,#0264c8,#1e90ff);padding:11px 16px;
                  display:flex;align-items:center;justify-content:space-between}
    .section-head h2{color:#fff;font-size:15px;font-weight:900;margin:0}
    .section-head .edit-btn{color:rgba(255,255,255,.85);font-size:12px;font-weight:700;
                            text-decoration:none;padding:4px 10px;border-radius:7px;
                            border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.1)}
    .section-body{padding:14px}
    .row{display:grid;grid-template-columns:200px 1fr;gap:8px;padding:8px 0;
         border-bottom:1px dashed var(--line)}
    .row:last-child{border-bottom:none}
    .label{font-weight:800;color:var(--ink);font-size:13px}
    .val{color:var(--ink);font-size:13px}
    .muted{color:var(--muted);font-size:12px}
    table.tbl{width:100%;border-collapse:collapse;font-size:12px}
    table.tbl th,table.tbl td{border:1px solid var(--line);padding:7px 9px;vertical-align:top}
    table.tbl thead th{background:#eef6ff;font-weight:900;color:var(--ink)}

    /* Aside */
    aside{position:sticky;top:calc(var(--header-h) + 16px);height:fit-content}
    @media(max-width:900px){aside{position:relative;top:auto}}
    .aside-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);
                padding:16px;margin-bottom:14px;box-shadow:var(--shadow)}
    .aside-title{font-size:14px;font-weight:900;color:var(--ink);margin-bottom:10px;
                 padding-bottom:8px;border-bottom:1px solid var(--line)}
    .aside-btn-row{display:flex;flex-direction:column;gap:8px}
    .aside-btn-row .btn{width:100%;justify-content:center;background:#f8fbff;
                        color:var(--ink);border-color:var(--border)}
    .aside-btn-row .btn:hover{background:#edf5ff}
    .okbox{background:#ecfdf5;border:1px solid #bbf7d0;color:#065f46;border-radius:10px;
           padding:10px 12px;font-weight:700;font-size:13px;margin-top:8px}
  </style>
</head>
<body>
<header>
  <div class="hdr-inner">
    <div>
      <div class="hdr-title">📋 プレビュー確認（標準フォーマット）</div>
      <div class="hdr-sub">内容を確認し、Excelをダウンロードしてください</div>
    </div>
    <a class="btn" href="/rireki/index.php">フォーマット選択へ</a>
  </div>
</header>

<div class="wrap">
<main class="layout">
  <section>

    <!-- STEP 1 -->
    <div class="section">
      <div class="section-head">
        <h2>STEP 1：基本情報</h2>
        <a class="edit-btn" href="/rireki/basic/rireki.php#step-1">✏️ 編集</a>
      </div>
      <div class="section-body">
        <?php foreach ($step1 as $k => $v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="val"><?= $v !== '' ? nl2br(h($v)) : '<span class="muted">—</span>' ?></div>
          </div>
        <?php endforeach; ?>
        <?php if ($photoPath): ?>
          <div class="row">
            <div class="label">写真</div>
            <div class="val">
              <img src="<?=h($photoPath)?>" alt="プロフィール写真" style="max-width:120px;border-radius:8px;border:2px solid var(--line)">
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- STEP 2 -->
    <div class="section">
      <div class="section-head">
        <h2>STEP 2：学歴</h2>
        <a class="edit-btn" href="/rireki/basic/rireki.php#step-2">✏️ 編集</a>
      </div>
      <div class="section-body">
        <?php $filled = array_filter($eduRows, fn($r) => implode('', $r) !== '');
        if ($filled): ?>
          <table class="tbl">
            <thead><tr><th>開始</th><th>学校名</th><th>学部・学科</th><th>区分</th><th>在学状況</th><th>終了</th></tr></thead>
            <tbody>
              <?php foreach ($eduRows as $r): ?>
                <tr>
                  <td><?=h($r['開始'])?></td><td><?=h($r['学校名'])?></td>
                  <td><?=h($r['学部・学科'])?></td><td><?=h($r['区分'])?></td>
                  <td><?=h($r['在学状況'])?></td><td><?=h($r['終了'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?><p class="muted">未入力</p><?php endif; ?>
      </div>
    </div>

    <!-- STEP 3 -->
    <div class="section">
      <div class="section-head">
        <h2>STEP 3：職歴</h2>
        <a class="edit-btn" href="/rireki/basic/rireki.php#step-3">✏️ 編集</a>
      </div>
      <div class="section-body">
        <?php $filled = array_filter($workRows, fn($r) => implode('', $r) !== '');
        if ($filled): ?>
          <table class="tbl">
            <thead><tr><th>開始</th><th>会社名</th><th>役職/職種</th><th>在職状況</th><th>終了</th></tr></thead>
            <tbody>
              <?php foreach ($workRows as $r): ?>
                <tr>
                  <td><?=h($r['開始'])?></td><td><?=h($r['会社名'])?></td>
                  <td><?=h($r['役職/職種'])?></td><td><?=h($r['在職状況'])?></td>
                  <td><?=h($r['終了'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?><p class="muted">未入力</p><?php endif; ?>
      </div>
    </div>

    <!-- STEP 4 -->
    <div class="section">
      <div class="section-head">
        <h2>STEP 4：資格・免許</h2>
        <a class="edit-btn" href="/rireki/basic/rireki.php#step-4">✏️ 編集</a>
      </div>
      <div class="section-body">
        <?php $filled = array_filter($licRows, fn($r) => implode('', $r) !== '');
        if ($filled): ?>
          <table class="tbl">
            <thead><tr><th>取得年</th><th>取得月</th><th>資格名</th></tr></thead>
            <tbody>
              <?php foreach ($licRows as $r): ?>
                <tr>
                  <td><?=h($r['取得年'])?></td><td><?=h($r['取得月'])?></td>
                  <td><?=h($r['資格名'])?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?><p class="muted">未入力</p><?php endif; ?>
      </div>
    </div>

    <!-- STEP 5 -->
    <div class="section">
      <div class="section-head">
        <h2>STEP 5：自己PR・希望</h2>
        <a class="edit-btn" href="/rireki/basic/rireki.php#step-5">✏️ 編集</a>
      </div>
      <div class="section-body">
        <?php foreach ($step5 as $k => $v): ?>
          <div class="row">
            <div class="label"><?=h($k)?></div>
            <div class="val"><?= $v !== '' ? nl2br(h($v)) : '<span class="muted">—</span>' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </section>

  <!-- Aside -->
  <aside>
    <!-- Excel download card -->
    <div class="aside-card">
      <div class="aside-title">📥 ダウンロード</div>
      <div class="aside-btn-row">
        <!-- Excel build form -->
        <form id="buildXlsForm" method="POST" action="/rireki/basic/php/submit_rireki.php">
          <?php foreach ($post as $k => $v) echo keep($k, $v); ?>
          <?php if ($photoPath): ?>
            <input type="hidden" name="photo_path" value="<?=h($photoPath)?>">
          <?php endif; ?>
          <button id="btnBuildXls" class="btn primary" type="submit" style="width:100%;justify-content:center">
            📊 Excelを作成・ダウンロード
          </button>
        </form>
        <div id="exportResult" style="display:none;margin-top:8px">
          <div class="okbox">完成しました！</div>
          <a id="xlsDownloadLink" class="btn" href="#" download style="width:100%;justify-content:center;margin-top:8px">
            💾 Excel（.xls）をダウンロード
          </a>
        </div>
      </div>
    </div>

    <!-- Save/send card -->
    <div class="aside-card">
      <div class="aside-title">📤 送信・保存</div>
      <div class="aside-btn-row">
        <p class="muted" style="font-size:12px;line-height:1.6">
          送信後にExcel出力が生成されます。内容確認後に送信してください。
        </p>
        <form method="POST" action="/rireki/basic/php/submit_rireki.php">
          <?php foreach ($post as $k => $v) echo keep($k, $v); ?>
          <?php if ($photoPath): ?>
            <input type="hidden" name="photo_path" value="<?=h($photoPath)?>">
          <?php endif; ?>
          <button type="submit" class="btn primary" style="width:100%;justify-content:center">
            ✅ この内容で送信する
          </button>
        </form>
        <a class="btn" href="/rireki/basic/rireki.php#step-1" style="width:100%;justify-content:center">
          ← 戻って修正する
        </a>
      </div>
    </div>

    <!-- Checklist card -->
    <div class="aside-card">
      <div class="aside-title">✅ 最終チェック</div>
      <ul style="font-size:12px;color:var(--muted);padding-left:1.2em;line-height:1.8">
        <li>氏名・住所・連絡先は正確？</li>
        <li>学歴/職歴の年月は時系列 OK？</li>
        <li>写真（任意）は明るく背景シンプル？</li>
        <li>志望動機は具体的で簡潔？</li>
      </ul>
    </div>

    <!-- Dashboard links -->
    <div class="aside-card">
      <div class="aside-title">関連リンク</div>
      <div class="aside-btn-row">
        <a class="btn" href="/rireki/index.php" style="justify-content:center">➕ 別フォーマット</a>
        <a class="btn" href="/saiyou.php" style="justify-content:center">💼 求人を見る</a>
        <?php if ($session_uid > 0): ?>
          <a class="btn" href="/php/user_logout.php" style="justify-content:center;color:var(--muted)">ログアウト</a>
        <?php else: ?>
          <a class="btn" href="/php/user_login.php" style="justify-content:center">🔑 ログイン</a>
        <?php endif; ?>
      </div>
    </div>
  </aside>

</main>
</div>

<script>
// ── Seed draft to localStorage ───────────────────────────────────
(function () {
  try {
    var data = <?php echo json_encode($post, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>;
    data['photo_path'] = <?php echo json_encode($photoPath ?? '', JSON_UNESCAPED_SLASHES); ?>;
    data['__active_step'] = data['__active_step'] || '5';
    var KEY = 'rireki:basic:draft:v1';
    var json = JSON.stringify(data);
    localStorage.setItem(KEY, json);
    sessionStorage.setItem(KEY, json);
  } catch (e) { console.warn('seed draft failed', e); }
})();

// ── Excel AJAX build (optional enhancement) ──────────────────────
// The form submits normally to submit_rireki.php which returns a success page with DL link.
// No AJAX intercept needed for basic flow — keep form submit default.
</script>
</body>
</html>
