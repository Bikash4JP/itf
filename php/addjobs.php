<?php
// /home/it-future/www/itf/php/addjobs.php
// Add Job Posting (求人情報) — separate page version

/* ---- session & auth ---- */
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: /php/login.php");
  exit;
}

/* ---- DB ---- */
require_once __DIR__ . '/db_connect.php';

/* ---- CSRF ---- */
if (empty($_SESSION['csrf_addjobs'])) {
  $_SESSION['csrf_addjobs'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_addjobs'];

/* ---- POST handler ---- */
$err = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF check
  if (!isset($_POST['csrf']) || !hash_equals($csrf, $_POST['csrf'])) {
    $err[] = '不正なリクエストです（CSRF）。もう一度お試しください。';
  }

  // Basic required fields
  $date       = trim($_POST['date'] ?? date('Y-m-d'));
  $title      = trim($_POST['title'] ?? '');
  $summary    = trim($_POST['summary'] ?? '');
  $content    = trim($_POST['content'] ?? '');
  $category   = '求人'; // 固定でもOK。必要ならフォームで変更可
  $posted_by  = $_SESSION['username'];
  $staff_id   = (int)$_SESSION['id'];

  // Job specifics
  $company_name  = trim($_POST['company_name'] ?? '');
  $job_location  = trim($_POST['job_location'] ?? '');
  $job_category  = trim($_POST['job_category'] ?? '');
  $job_type      = trim($_POST['job_type'] ?? '');
  $salary_type   = $_POST['salary_type'] ?? 'amount'; // amount / negotiable
  $salary        = $salary_type === 'amount' ? trim($_POST['salary'] ?? '') : null;

  $bonuses       = isset($_POST['bonuses']) ? (int)$_POST['bonuses'] : 0;
  $bonus_amount  = $bonuses ? trim($_POST['bonus_amount'] ?? '') : null;

  $living_support      = isset($_POST['living_support']) ? (int)$_POST['living_support'] : 0;
  $rent_support_type   = $_POST['rent_support_type'] ?? 'amount'; // amount / percentage
  $rent_support_amount = $living_support ? trim($_POST['rent_support_amount'] ?? '') : null;

  $insurance     = isset($_POST['insurance']) ? (int)$_POST['insurance'] : 0;

  $transportation_charges = isset($_POST['transportation_charges']) ? (int)$_POST['transportation_charges'] : 0;
  $transport_amount       = $transportation_charges ? trim($_POST['transport_amount'] ?? '') : null;

  $salary_increment  = isset($_POST['salary_increment']) ? (int)$_POST['salary_increment'] : 0;
  $increment_condition = $salary_increment ? trim($_POST['increment_condition'] ?? '') : null;

  $japanese_level = trim($_POST['japanese_level'] ?? '');
  $experience     = trim($_POST['experience'] ?? '');

  $minimum_leave_per_year = trim($_POST['minimum_leave_per_year'] ?? '');
  $employee_size          = trim($_POST['employee_size'] ?? '');
  $required_vacancy       = trim($_POST['required_vacancy'] ?? '');

  // Validate minimal set
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $err[] = '日付の形式が正しくありません。';
  if ($title === '')    $err[] = 'タイトルは必須です。';
  if ($content === '')  $err[] = '内容は必須です。';
  if ($job_type === '') $err[] = '雇用形態は必須です。';

  if ($salary_type === 'amount' && $salary === '') $err[] = '給与額を入力してください。';

  // Optional image upload
  $imageRel = null; // DB には "uploads/jobs/xxx.jpg" のように保存
  if (!empty($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $up = $_FILES['image'];
    if ($up['error'] !== UPLOAD_ERR_OK) {
      $err[] = '画像のアップロードに失敗しました。';
    } else {
      if ($up['size'] > 2 * 1024 * 1024) {
        $err[] = '画像は 2MB 以下にしてください。';
      } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($up['tmp_name']);
        $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($ok[$mime])) {
          $err[] = '画像形式は JPEG/PNG のみ対応しています。';
        } else {
          $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');
          $absDir  = $docroot . '/uploads/jobs';
          if (!is_dir($absDir)) @mkdir($absDir, 0775, true);
          $ext  = $ok[$mime];
          $base = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          $absPath = $absDir . '/' . $base;
          if (!move_uploaded_file($up['tmp_name'], $absPath)) {
            $err[] = '画像の保存に失敗しました。';
          } else {
            $imageRel = 'uploads/jobs/' . $base;
          }
        }
      }
    }
  }

  // Insert
  if (!$err) {
    try {
      $sql = "INSERT INTO posts (
                staff_id, post_type, category, title, summary, content, image,
                company_name, job_location, job_category, job_type, salary,
                bonuses, bonus_amount, living_support, rent_support, insurance,
                transportation_charges, transport_amount_limit, salary_increment,
                increment_condition, japanese_level, experience,
                minimum_leave_per_year, employee_size, required_vacancy,
                date, posted_by
              ) VALUES (
                :staff_id, 'job', :category, :title, :summary, :content, :image,
                :company_name, :job_location, :job_category, :job_type, :salary,
                :bonuses, :bonus_amount, :living_support, :rent_support, :insurance,
                :transportation_charges, :transport_amount_limit, :salary_increment,
                :increment_condition, :japanese_level, :experience,
                :minimum_leave_per_year, :employee_size, :required_vacancy,
                :date, :posted_by
              )";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':staff_id' => $staff_id,
        ':category' => $category,
        ':title'    => $title,
        ':summary'  => $summary ?: null,
        ':content'  => $content,
        ':image'    => $imageRel,
        ':company_name'  => $company_name ?: null,
        ':job_location'  => $job_location ?: null,
        ':job_category'  => $job_category ?: null,
        ':job_type'      => $job_type,
        ':salary'        => $salary, // null if negotiable
        ':bonuses'       => $bonuses,
        ':bonus_amount'  => $bonus_amount ?: null,
        ':living_support'=> $living_support,
        ':rent_support'  => $rent_support_amount ?: null, // 金額 or ％数値のみ保存（種別は任意で追加可）
        ':insurance'     => $insurance,
        ':transportation_charges' => $transportation_charges,
        ':transport_amount_limit' => $transport_amount ?: null,
        ':salary_increment'       => $salary_increment,
        ':increment_condition'    => $increment_condition ?: null,
        ':japanese_level'         => $japanese_level ?: null,
        ':experience'             => $experience ?: null,
        ':minimum_leave_per_year' => $minimum_leave_per_year ?: null,
        ':employee_size'          => $employee_size ?: null,
        ':required_vacancy'       => $required_vacancy ?: null,
        ':date'       => $date,
        ':posted_by'  => $posted_by,
      ]);

      $_SESSION['flash_ok'] = '求人情報を投稿しました。';
      header('Location: /staffdb.php');
      exit;

    } catch (PDOException $e) {
      $err[] = 'データベース保存時にエラーが発生しました。';
      @file_put_contents("/home/it-future/www/itf/logs/db_error.log",
        "AddJobs Error: ".$e->getMessage()." | Time: ".date('Y-m-d H:i:s')."\n", FILE_APPEND);
    }
  }
}

/* ---- View ---- */
$prefectures = [
  "北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県",
  "茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県",
  "新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県",
  "静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県",
  "奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県",
  "徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県",
  "熊本県","大分県","宮崎県","鹿児島県","沖縄県"
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>✙ 求人情報を追加</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/staffdb.css?v=1.4">
  <style>
    body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;margin:20px}
    .wrap{max-width:980px;margin:0 auto}
    header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
    header h1{margin:0;font-size:20px}
    a.btn{display:inline-block;padding:8px 12px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;text-decoration:none}
    a.btn:hover{background:#e9f5ff}
    form{background:#fff;border:1px solid #e6edf6;border-radius:12px;padding:16px}
    label{display:block;margin:10px 0 6px;font-weight:600}
    input[type="text"],input[type="number"],input[type="date"],textarea,select{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;box-sizing:border-box}
    textarea{min-height:120px}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
    .actions{display:flex;gap:10px;justify-content:flex-end;margin-top:14px}
    button{padding:10px 14px;border:1px solid #dbe7f5;border-radius:8px;background:#0ea5e9;color:#fff;cursor:pointer}
    button.secondary{background:#f3f9ff;color:#0c4a7a}
    .errors{background:#fff7f7;border:1px solid #fecaca;color:#7f1d1d;padding:12px;border-radius:10px;margin-bottom:12px}
    .note{color:#667085;font-size:12px}
    .inline{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .muted{font-size:12px;color:#6b7280}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>✙ 求人情報を追加</h1>
      <a class="btn" href="/staffdb.php" style="margin-left:auto">← ダッシュボードへ戻る</a>
    </header>

    <?php if ($err): ?>
      <div class="errors">
        <ul style="margin:0;padding-left:18px">
          <?php foreach ($err as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">
      <div class="grid-2">
        <div>
          <label>掲載日 *</label>
          <input type="date" name="date" value="<?=htmlspecialchars(date('Y-m-d'))?>" required>
        </div>
        <div>
          <label>職種カテゴリ *</label>
          <select name="job_category" required>
            <option value="介護">介護</option>
            <option value="レストラン">レストラン</option>
            <option value="事務">事務</option>
            <option value="工場作業員">工場作業員</option>
          </select>
        </div>
      </div>

      <label>タイトル *</label>
      <input type="text" name="title" maxlength="200" required>

      <label>概要（70〜100語目安）</label>
      <textarea name="summary"></textarea>

      <label>内容 *</label>
      <textarea name="content" rows="10" required></textarea>

      <div class="grid-2">
        <div>
          <label>会社名 *</label>
          <input type="text" name="company_name" required>
        </div>
        <div>
          <label>勤務地（都道府県） *</label>
          <select name="job_location" required>
            <?php foreach ($prefectures as $p): ?>
              <option value="<?=htmlspecialchars($p)?>"><?=htmlspecialchars($p)?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid-3">
        <div>
          <label>雇用形態 *</label>
          <select name="job_type" required>
            <option value="正社員">正社員</option>
            <option value="パートタイム">パートタイム</option>
            <option value="契約社員">契約社員</option>
          </select>
        </div>
        <div>
          <label>必要日本語レベル *</label>
          <select name="japanese_level" required>
            <option value="N1">N1</option>
            <option value="N2">N2</option>
            <option value="N3">N3</option>
            <option value="N4">N4</option>
            <option value="N5">N5</option>
          </select>
        </div>
        <div>
          <label>経験 *</label>
          <input type="text" name="experience" placeholder="例：介護経験1年以上 等" required>
        </div>
      </div>

      <label>給与 *</label>
      <div class="inline">
        <label><input type="radio" name="salary_type" value="amount" checked> 金額</label>
        <label><input type="radio" name="salary_type" value="negotiable"> 応相談</label>
        <input type="number" name="salary" id="salaryAmount" placeholder="金額（円）">
      </div>

      <label>賞与</label>
      <div class="inline">
        <label><input type="radio" name="bonuses" value="0" checked> なし</label>
        <label><input type="radio" name="bonuses" value="1"> あり</label>
        <input type="number" name="bonus_amount" id="bonusAmount" placeholder="賞与額（任意）" style="display:none;">
      </div>

      <label>住宅手当</label>
      <div class="inline">
        <label><input type="radio" name="living_support" value="0" checked> なし</label>
        <label><input type="radio" name="living_support" value="1"> あり</label>
        <span id="rentSupportBox" style="display:none;">
          <label><input type="radio" name="rent_support_type" value="amount" checked> 金額</label>
          <label><input type="radio" name="rent_support_type" value="percentage"> パーセント</label>
          <input type="number" name="rent_support_amount" id="rentSupportAmount" placeholder="金額 or ％">
        </span>
      </div>

      <label>保険</label>
      <div class="inline">
        <label><input type="radio" name="insurance" value="0" checked> なし</label>
        <label><input type="radio" name="insurance" value="1"> あり</label>
      </div>

      <label>交通費</label>
      <div class="inline">
        <label><input type="radio" name="transportation_charges" value="0" checked> なし</label>
        <label><input type="radio" name="transportation_charges" value="1"> あり</label>
        <input type="number" name="transport_amount" id="transportAmount" placeholder="月額上限" style="display:none;">
      </div>

      <label>昇給</label>
      <div class="inline">
        <label><input type="radio" name="salary_increment" value="0" checked> なし</label>
        <label><input type="radio" name="salary_increment" value="1"> あり</label>
        <input type="text" name="increment_condition" id="incrementCondition" placeholder="昇給条件（任意）" style="display:none;">
      </div>

      <div class="grid-3">
        <div>
          <label>年間最低休暇日数 *</label>
          <input type="number" name="minimum_leave_per_year" required>
        </div>
        <div>
          <label>現在の従業員数 *</label>
          <input type="number" name="employee_size" required>
        </div>
        <div>
          <label>募集人数 *</label>
          <input type="number" name="required_vacancy" required>
        </div>
      </div>

      <label>画像（JPEG/PNG、最大2MB）</label>
      <input type="file" name="image" accept="image/jpeg,image/png">
      <p class="note">※ 画像は任意です。アップロードされた場合は <code>uploads/jobs/</code> に保存されます。</p>

      <div class="actions">
        <a class="btn secondary" href="/staffdb.php">キャンセル</a>
        <button type="submit">投稿する</button>
      </div>
    </form>
  </div>

  <script>
    // Salary amount toggle
    function updateSalary() {
      const type = document.querySelector('input[name="salary_type"]:checked')?.value;
      const inp  = document.getElementById('salaryAmount');
      if (type === 'amount') { inp.style.display = ''; }
      else { inp.value = ''; inp.style.display = 'none'; }
    }
    document.querySelectorAll('input[name="salary_type"]').forEach(r => r.addEventListener('change', updateSalary));
    updateSalary();

    // Bonus amount toggle
    function updateBonus() {
      const v = document.querySelector('input[name="bonuses"]:checked')?.value;
      const el = document.getElementById('bonusAmount');
      if (v === '1') { el.style.display = ''; } else { el.value=''; el.style.display='none'; }
    }
    document.querySelectorAll('input[name="bonuses"]').forEach(r => r.addEventListener('change', updateBonus));
    updateBonus();

    // Rent support toggle
    function updateRent() {
      const v = document.querySelector('input[name="living_support"]:checked')?.value;
      const box = document.getElementById('rentSupportBox');
      const amt = document.getElementById('rentSupportAmount');
      if (v === '1') { box.style.display=''; }
      else { box.style.display='none'; amt.value=''; }
    }
    document.querySelectorAll('input[name="living_support"]').forEach(r => r.addEventListener('change', updateRent));
    updateRent();

    // Transport amount toggle
    function updateTransport() {
      const v = document.querySelector('input[name="transportation_charges"]:checked')?.value;
      const el = document.getElementById('transportAmount');
      if (v === '1') { el.style.display=''; } else { el.style.display='none'; el.value=''; }
    }
    document.querySelectorAll('input[name="transportation_charges"]').forEach(r => r.addEventListener('change', updateTransport));
    updateTransport();

    // Increment condition toggle
    function updateIncrement() {
      const v = document.querySelector('input[name="salary_increment"]:checked')?.value;
      const el = document.getElementById('incrementCondition');
      if (v === '1') { el.style.display=''; } else { el.style.display='none'; el.value=''; }
    }
    document.querySelectorAll('input[name="salary_increment"]').forEach(r => r.addEventListener('change', updateIncrement));
    updateIncrement();
  </script>
</body>
</html>
