<?php
// /home/it-future/www/itf/rireki/basic/php/submit_rireki.php
error_reporting(E_ALL);
ini_set('display_errors', isset($_GET['debug']) ? '1' : '0');

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/adapters/adapter_xlsx.php'; // provides rireki_render_pdf() + buildCanonicalData()
require_once __DIR__ . '/validators.php';

// ===== Paths =====
$mappingFile = rireki_path('mappings/templateA.json');
$outDir      = rireki_path('resumes');
@mkdir($outDir, 0750, true);

// ===== Build data =====
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
  // validators.php + adapter buildCanonicalData handle normalization
  $data = buildCanonicalData($_POST, $_FILES);
}

// ===== Render (XLS only; no preview, no PDF) =====
$token = bin2hex(random_bytes(16));
$res   = rireki_render_pdf($data, $mappingFile, $outDir, $token); // NOTE: function name says pdf but returns XLS
if (empty($res['ok']) || empty($res['xls'])) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=UTF-8');
  echo "Rirekisho build failed: " . ($res['err'] ?? 'unknown');
  exit;
}

// Save JSON snapshot for list/print etc.
@file_put_contents($outDir . '/' . $token . '.json', json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// Links
$xlsUrl = '/rireki/basic/resumes/' . basename((string)$res['xls']);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>履歴書の作成が完了しました｜Excelダウンロード</title>
  <style>
    :root{
      --ink:#0b0f19; --muted:#667085; --border:#e6edf6;
      --bg:#f8fbff; --ok:#0b6b4a; --ring:#bfe2ff;
      --btn-bg:#f3f9ff; --btn-bd:#dbe7f5; --btn-ink:#0c4a7a;
      --warn:#b45309; --warn-bg:#fff7ed; --warn-bd:#fed7aa;
    }
    *{ box-sizing:border-box }
    body{
      margin:0; padding:20px;
      background:linear-gradient(180deg,#f8fbff,#eef6ff);
      font-family: ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;
      color:var(--ink);
    }
    .wrap{ max-width:980px; margin:0 auto }
    .card{
      background:#fff; border:1px solid var(--border); border-radius:16px;
      padding:18px; box-shadow:0 10px 24px rgba(0,0,0,.05)
    }
    h1{ margin:0 0 8px; font-size:22px }
    .sub{ color:var(--muted); margin:0 0 12px }
    .done{
      display:flex; gap:10px; align-items:flex-start; margin:12px 0 16px;
      background:#ecfdf5; border:1px solid #bbf7d0; color:var(--ok);
      padding:12px 14px; border-radius:12px; font-weight:700
    }
    .notice{
      display:flex; gap:10px; align-items:flex-start;
      background:var(--warn-bg); border:1px solid var(--warn-bd); color:var(--warn);
      padding:12px 14px; border-radius:12px; margin:12px 0
    }
    .actions{ display:flex; gap:10px; flex-wrap:wrap; margin:12px 0 2px }
    .btn{
      display:inline-flex; align-items:center; gap:8px; text-decoration:none; cursor:pointer;
      padding:12px 14px; border-radius:10px; font-weight:800;
      background:var(--btn-bg); border:1px solid var(--btn-bd); color:var(--btn-ink);
      transition: background .2s, transform .2s, box-shadow .2s, border-color .2s;
    }
    .btn:hover{ background:#e9f5ff; transform: translateY(-1px); box-shadow:0 6px 18px rgba(57,167,255,.18) }
    .hint{ color:var(--muted); font-size:12px; margin-top:4px }
    hr{ border:none; border-top:1px dashed #e5e7eb; margin:16px 0 }
    .sec h2{ margin:0 0 8px; font-size:18px }
    details{ border-top:1px dashed #e5e7eb; padding:10px 0 }
    details:first-of-type{ border-top:none }
    summary{ cursor:pointer; font-weight:700 }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size:12px; color:#1f2937 }
    .links-row{ display:flex; gap:10px; flex-wrap:wrap; margin:10px 0 0 }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>履歴書の作成が完了しました（標準フォーマット）</h1>
      <p class="sub">Excelファイルを保存し、そのまま印刷または編集してご利用ください。</p>

      <div class="done">
        <!-- check icon -->
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        履歴書が完成しました。ダウンロードしてご確認ください。
      </div>

      <div class="notice" role="note" aria-live="polite">
        <!-- alert icon -->
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        </svg>
        PDF は環境差により体裁が100%一致しない場合があります。<strong>Excel（.xls）からの印刷</strong>を推奨します。
      </div>

      <div class="actions">
        <a class="btn" href="<?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?>" download>
          <!-- file icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <path d="M14 2v6h6"></path>
          </svg>
          Excel（.xls）をダウンロード
        </a>
        <a class="btn" href="/saiyou.php">
          <!-- briefcase -->
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"></path>
          </svg>
          新着採用をチェック（マッチする求人を探す）
        </a>
        <a class="btn" href="https://it-future.jp/">
          <!-- home -->
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m3 9 9-7 9 7"></path>
            <path d="M9 22V12h6v10"></path>
          </svg>
          会社ホームページへ
        </a>
        <a class="btn" href="/rireki/index.php">
          <!-- repeat -->
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 1 0 6.2 18.8"></path>
          </svg>
          別のフォーマットで作る
        </a>
      </div>
      <p class="hint">※ Excel から「余白：上下左右小さめ」「ページ設定：A4」にして印刷すると綺麗に出力できます。</p>

      <hr>

      <section class="sec">
        <h2>よくある質問（FAQ）</h2>
        <details>
          <summary>PDF での出力はできますか？</summary>
          <div>現在は Excel（.xls）での出力に最適化しています。PDF は環境差で体裁が崩れることがあるため、Excel からの印刷をおすすめします。</div>
        </details>
        <details>
          <summary>内容を修正したいです。</summary>
          <div>ダウンロードした Excel をそのまま編集できます。不要な枠線や余分な余白はページ設定から調整してください。</div>
        </details>
        <details>
          <summary>求人へはどう応募しますか？</summary>
          <div><a href="/saiyou.php">新着採用</a>ページから、求人詳細の指示に従って応募してください。必要に応じて履歴書（Excel）を添付して送付できます。</div>
        </details>
        <details>
          <summary>スマホでも使えますか？</summary>
          <div>はい。スマホ・タブレット・PCに対応しています。スマホの場合は、Excel アプリや対応ビューワーで開いて印刷してください。</div>
        </details>
      </section>

      <hr>

      <p class="mono">トークン: <?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?> / 出力: <?= htmlspecialchars($xlsUrl, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
  </div>
</body>
</html>
