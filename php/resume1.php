 <?php
// /home/it-future/www/itf/php/resume.php
require_once __DIR__ . '/db_connect.php';

$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($job_id <= 0) {
  http_response_code(400);
  echo '無効な求人IDです。';
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$job) {
  http_response_code(404);
  echo '求人が見つかりませんでした。';
  exit;
}

// 「介護」カテゴリで判定
$isKaigo = isset($job['job_category']) && $job['job_category'] === '介護';

// 新規作成ショートカット
if ($isKaigo && isset($_GET['go']) && $_GET['go'] === 'new') {
  $dest = '/rireki/kaigo/rireki.php?job_id=' . urlencode((string)$job['id']);
  header('Location: ' . $dest, true, 302);
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>応募 - 株式会社アイティーエフ</title>
  <link rel="stylesheet" href="https://it-future.jp/css/common.css">
  <link rel="stylesheet" href="https://it-future.jp/css/style.min.css">
  <link rel="stylesheet" href="https://it-future.jp/css/screen.min.css">
  <link rel="stylesheet" href="https://it-future.jp/css/pagenavi-css.css">
  <link rel="stylesheet" href="https://it-future.jp/css/footer.css">
  <link rel="stylesheet" href="https://it-future.jp/css/main_intro.css">
  <link rel="stylesheet" href="https://it-future.jp/css/saiyou.css">
  <link rel="stylesheet" href="https://it-future.jp/css/login.css">
  <style>
    :root{ --accent:#2a7de1; --muted:#6b7280; --bg:#f0fcfd }
    body{ background:#fff }
    .wrap{ max-width:1000px;margin:40px auto;padding:0 16px }
    .job-hero{
      background:var(--bg);
      border-radius:20px;
      padding:20px;
      box-shadow:0 2px 4px rgba(0,0,0,.1);
      display:flex;gap:12px;align-items:center
    }
    .badge{ font-size:.8rem;background:#e8f1ff;color:#0a5cc7;border:1px solid #cfe2ff;border-radius:999px;padding:4px 10px }
    .job-hero h1{ margin:6px 0 0 0;font-size:1.25rem;line-height:1.35;color:#083f75 }
    .job-meta{ color:var(--muted);font-size:.9rem;margin-top:6px;display:flex;gap:14px;flex-wrap:wrap }

    .grid{ display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:18px }
    @media (max-width:900px){ .grid{ grid-template-columns:1fr } }

    .card{
      background:#fff;border:1px solid #e6edf6;border-radius:16px;padding:20px;
      box-shadow:0 3px 10px rgba(10,60,150,.05);display:flex;flex-direction:column
    }
    .card h2{ margin:0 0 6px 0;font-size:1.5rem;color:#0b3772;font-weight: bold; }
    .card p{ margin:0 0 14px 0;color:#4b5563;font-size:.95rem }
    .card .note{ font-size:.85rem;color:#6b7280 }
    .card .actions{ margin-top:auto;display:flex;gap:10px;flex-wrap:wrap }
    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:8px;
      padding:10px 16px;border-radius:10px;text-decoration:none;border:1px solid transparent;
      font-weight:600;cursor:pointer
    }
    .btn-primary{ background:var(--accent);color:#fff }
    .btn-primary:hover{ filter:brightness(.95) }
    .btn-ghost{ background:#fff;border-color:#dbe7f5;color:#0b3772 }
    .btn-ghost:hover{ background:#f7fbff }

    .btn[disabled],
    .btn:disabled{
      opacity:.5;
      pointer-events:none;
      cursor:not-allowed;
    }

    .u-drop{ border:2px dashed #cfe2ff;border-radius:12px;padding:16px;background:#fbfdff;margin:10px 0 }
    .u-drop small{ color:#64748b }

    .form-grid{ display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:10px 0 4px }
    .form-grid .col-2{ grid-column:1/-1 }
    .form-grid label{ display:block;font-weight:600;margin-bottom:4px;color:#0b3772 }
    .form-grid input[type="text"], .form-grid select{
      width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff
    }

    .backline{ text-align:right;margin-top:16px }
    .backline a{ color:#374151;text-decoration:none;font-size:.9rem }
    .backline a:hover{ text-decoration:underline }
  </style>
</head>
<body>
  <!-- SAME HEADER AS job_details.php -->
  <header id="header" class="l-header header" itemscope itemtype="https://schema.org/WPHeader">
    <div class="header-frame">
      <div class="header-top">
        <div class="wrap pc-flex bet">
          <div class="header-top-in flex bet vcenter">
            <h1 class="sp-2 logo"><a href="https://it-future.jp/" class="logo-link flex vcenter"><img src="https://it-future.jp/images/logo.png" alt=""></a></h1>
            <div id="sp-menu-open" class="sp l-animebtn sp-3">
              <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')">
                <div class="bar"><span></span><span></span><span></span></div>
              </a>
            </div>
          </div>
          <div class="header-menu sp-md-acc">
            <div id="sp-menu-acc" class="pc-flex hend acc-body">
              <ul class="contents pc-flex str hend max">
                <li class="contents-item"><a href="https://it-future.jp/about.html">事業紹介</a></li>
                <li class="contents-item"><a href="https://it-future.jp/company_info.html">企業情報</a></li>
                <li class="contents-item"><a href="https://it-future.jp/saiyou.php">求人情報</a></li>
                <li class="contents-item"><a href="https://it-future.jp/news.html">新着情報</a></li>
              </ul>
              <ul class="cta pc-flex max str">
                <li class="cta-item tel sp">
                  <a href="tel:06-6644-1800" class="sp-flex hcenter vcenter">
                    <i class="icon icon-phone"></i>
                    <span class="text">電話でのお問い合わせ<br><span class="note">09:00～19:00(土日祝除く)</span></span>
                  </a>
                </li>
                <li class="cta-item document flex vcenter">
                  <a href="/itf/Recruitment" class="cta-item-link flex hcenter vcenter">資料請求</a>
                </li>
                <li class="cta-item inquiry flex vcenter">
                  <a href="https://it-future.jp/inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="wrap">
    <!-- Job summary header -->
    <div class="job-hero">
      <span class="badge"><?php echo $isKaigo ? '介護求人' : '求人'; ?></span>
      <div>
        <h1><?php echo htmlspecialchars($job['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
        <div class="job-meta">
          <span>会社名：<?php echo htmlspecialchars($job['company_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <span>勤務地：<?php echo htmlspecialchars($job['job_location'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <span>雇用形態：<?php echo htmlspecialchars($job['job_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
          <span>給与：<?php echo htmlspecialchars($job['salary'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
      </div>
    </div>

    <!-- Two options: upload / create -->
    <div class="grid">
      <!-- Upload existing resume -->
      <div class="card">
        <h2>既存の履歴書をアップロード</h2>
        <p>お手元の履歴書（PDF / 画像 / Excel など）をアップロードして応募します。</p>

        <!-- 必須3項目 + ファイル -->
        <form id="uploadForm" action="/php/upload_resume.php" method="post" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="job_id" value="<?php echo (int)$job_id; ?>">
          <input type="hidden" name="src" value="<?php echo $isKaigo ? 'kaigo' : 'basic'; ?>">

          <div class="form-grid">
            <div class="col-2">
              <label>氏名（ローマ字）<span style="color:#dc2626"> *</span></label>
              <input type="text" id="name_romaji" name="name_romaji" placeholder="Taro Yamada" required>
            </div>
            <div>
              <label>国籍<span style="color:#dc2626"> *</span></label>
              <select id="nationality" name="nationality" required>
                <option value=""></option>
                <?php
                  $prefs = ["バングラデシュ","インドネシア国籍","ベトナム国籍","ネパール国籍","ミャンマー国籍","ペルー国籍","中国国籍","韓国国籍","日本国籍","その他"];
                  foreach($prefs as $p){ echo '<option value="'.htmlspecialchars($p,ENT_QUOTES,'UTF-8').'">'.$p.'</option>'; }
                ?>
              </select>
            </div>
            <div>
              <label>性別<span style="color:#dc2626"> *</span></label>
              <select id="gender" name="gender" required>
                <option value=""></option>
                <option value="男性">男性</option>
                <option value="女性">女性</option>
                <option value="その他">その他</option>
              </select>
            </div>
          </div>

          <div class="u-drop">
            <input type="file" id="resume_file" name="resume_file" accept=".pdf,.xls,.xlsx,.jpg,.jpeg,.png" required>
            <br><small>対応形式：PDF / XLS / XLSX / JPG / PNG（最大 10MB）</small>
          </div>

          <div class="actions">
            <button id="submitBtn" class="btn btn-primary" type="submit" disabled>アップロードして応募</button>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
        </form>
        <div class="note">※ 3項目（氏名・国籍・性別）とファイルは必須です。すべて入力後、ボタンが有効になります。</div>
      </div>

      <!-- Create new resume (kaigo routes to rireki) -->
      <div class="card">
        <h2>新規で履歴書を作成</h2>
        <?php if ($isKaigo): ?>
          <p>フォームに沿って入力し、<strong>介護向け履歴書</strong>を自動作成できます。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/php/resume.php?job_id=<?php echo (int)$job_id; ?>&go=new">新規で作成する</a>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
          <div class="note">次のページで <code>/rireki/kaigo/rireki.php</code> に移動します。</div>
        <?php else: ?>
          <p>この求人は介護カテゴリではありません。汎用の履歴書フローをご利用ください。</p>
          <div class="actions">
            <a class="btn btn-primary" href="/rireki/kaigo/rireki.php?job_id=<?php echo (int)$job_id; ?>">（暫定）フォームへ進む</a>
            <a class="btn btn-ghost" href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">求人詳細に戻る</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="backline">
      <a href="/php/job_details.php?job_id=<?php echo (int)$job_id; ?>">← 求人詳細に戻る</a>
    </div>
  </div>

  <!-- SAME FOOTER AS job_details.php -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-row">
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
          <div class="footer-link">
            <a href="https://it-future.jp/" style="color: white;" data-i18n="footer.company_name">株式会社アイティーエフ</a>
          </div>
          <p class="footer-text" data-i18n="footer.location_details">
            〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
            06-6644-1800<br>
            〒144-0052 東京都大田区蒲田5丁目21-13<br>
            03-6424-7747<br>
            info@it-future.jp
          </p>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.services_title">サービス案内</h3>
          <a href="https://it-future.jp/index.html#solution_03" class="footer-link" data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
          <a href="https://it-future.jp/index.html#service-naiyou" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
          <a href="https://it-future.jp/index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
          <a href="https://it-future.jp/index.html#work-step" class="footer-link" data-i18n="footer.introduction_flow">紹介の流れ</a>
          <a href="https://it-future.jp/about.html#support-naiyou" class="footer-link" data-i18n="footer.support_content">サポート内容</a>
        </div>
        <div class="footer-col">
          <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
          <a href="https://it-future.jp/greeting.html" class="footer-link" data-i18n="footer.president_greeting">代表者挨拶</a>
          <a href="https://it-future.jp/company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
        </div>
        <div class="footer-col">
          <a href="https://it-future.jp/privacy.html" class="footer-btn" data-i18n="footer.privacy_policy">プライバシーポリシー</a>
        </div>
      </div>
      <div class="footer-copyright">
        © ITF co. Ltd. ALL Rights Reserved
      </div>
    </div>
  </footer>

  <a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <polyline points="18 15 12 9 6 15"></polyline>
    </svg>
  </a>

  <script>
    (function(){
      const nameInput = document.getElementById('name_romaji');
      const natSelect = document.getElementById('nationality');
      const genSelect = document.getElementById('gender');
      const fileInput = document.getElementById('resume_file');
      const submitBtn = document.getElementById('submitBtn');
      const form = document.getElementById('uploadForm');

      const MAX_MB = 10;
      const ACCEPT = ['pdf','xls','xlsx','jpg','jpeg','png'];

      function hasValue(el){
        return !!(el && el.value && el.value.trim() !== '');
      }

      function fileOk(){
        const f = fileInput.files && fileInput.files[0];
        if (!f) return false;
        // size check
        if (f.size > MAX_MB * 1024 * 1024) return false;
        // ext check
        const ext = (f.name.split('.').pop() || '').toLowerCase();
        return ACCEPT.includes(ext);
      }

      function updateState(){
        const ready = hasValue(nameInput) && hasValue(natSelect) && hasValue(genSelect) && fileOk();
        submitBtn.disabled = !ready;
      }

      // Prevent submit if invalid (extra guard)
      form.addEventListener('submit', function(e){
        if (submitBtn.disabled) {
          e.preventDefault();
          updateState();
          alert('氏名（ローマ字）・国籍・性別・ファイルを入力／選択してください（ファイルは10MB以下・PDF/XLS/XLSX/JPG/PNG）。');
        }
      });

      [nameInput, natSelect, genSelect, fileInput].forEach(el=>{
        if (!el) return;
        el.addEventListener('input', updateState);
        el.addEventListener('change', updateState);
        el.addEventListener('keyup', updateState);
      });

      // initial
      updateState();
    })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/js/wp-embed.min.js"></script>
</body>
</html>
