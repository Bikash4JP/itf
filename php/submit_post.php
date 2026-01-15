<?php
// /home/it-future/www/itf/php/submit_post.php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// We will return an HTML page (not JSON)
header("Content-Type: text/html; charset=UTF-8");

// Check login
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  http_response_code(401);
  echo "<!doctype html><html><body><p>ログインしてください。</p><p><a href=\"/php/login.php\">ログインページへ</a></p></body></html>";
  exit;
}

// CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
  http_response_code(400);
  echo "<!doctype html><html><body><p>無効なリクエストです。</p><p><a href=\"/php/staffdb.php\">スタッフDBへ戻る</a></p></body></html>";
  exit;
}

require_once __DIR__ . '/db_connect.php';

try {
  $form_type = $_POST['form_type'] ?? '';
  $post_type = $_POST['post_type'] ?? ($form_type === 'jobs' ? 'job' : 'news');

  // Common
  $title    = trim($_POST['title']   ?? '');
  $summary  = trim($_POST['summary'] ?? '');
  $content  = trim($_POST['content'] ?? '');
  $category = $_POST['category'] ?? null; // news-only
  $date     = date('Y-m-d');
  $posted_by = $_SESSION['username'];
  $staff_id  = (int)$_SESSION['id'];

  // Image upload (optional)
  $image_path = null;
  if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../Uploads/';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    $image_name = uniqid('', true) . '_' . basename($_FILES['image']['name']);
    $dest_abs   = $upload_dir . $image_name;
    // public path stored to DB
    $dest_rel   = '../Uploads/' . $image_name;

    $allowed = ['image/jpeg','image/png'];
    $max_size = 2 * 1024 * 1024;

    $type = mime_content_type($_FILES['image']['tmp_name']);
    $size = (int)$_FILES['image']['size'];

    if (!in_array($type, $allowed, true)) {
      throw new RuntimeException('画像はJPEGまたはPNG形式である必要があります。');
    }
    if ($size > $max_size) {
      throw new RuntimeException('画像サイズは2MB以下である必要があります。');
    }
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest_abs)) {
      throw new RuntimeException('画像のアップロードに失敗しました。');
    }
    $image_path = $dest_rel;
  } elseif (!empty($_SESSION['preview_image'])) {
    $image_path = $_SESSION['preview_image'];
  }

  // Jobs-only fields
  $company_name = $_POST['company_name'] ?? null; // kept (internal)
  $job_location = $_POST['job_location'] ?? null;
  $job_category = $_POST['job_category'] ?? null; // e.g. 介護
  $job_type     = $_POST['job_type'] ?? null;
  $salary       = $_POST['salary'] ?? null;

  $bonuses      = isset($_POST['bonuses']) ? (int)$_POST['bonuses'] : null;
  $bonus_amount = $_POST['bonus_amount'] ?? null;

  $living_support = isset($_POST['living_support']) ? (int)$_POST['living_support'] : null;
  // naming fixed to match form & DB
  $rent_support  = $_POST['rent_support'] ?? null;

  $insurance     = isset($_POST['insurance']) ? (int)$_POST['insurance'] : null;

  $transportation_charges = isset($_POST['transportation_charges']) ? (int)$_POST['transportation_charges'] : null;
  // naming fixed to match form & DB
  $transport_amount_limit = $_POST['transport_amount_limit'] ?? null;

  $salary_increment   = isset($_POST['salary_increment']) ? (int)$_POST['salary_increment'] : null;
  $increment_condition = $_POST['increment_condition'] ?? null;

  $japanese_level = $_POST['japanese_level'] ?? null;
  $experience     = $_POST['experience'] ?? null;
  $minimum_leave_per_year = $_POST['minimum_leave_per_year'] ?? null;
  // removed from form, keep null to DB
  $employee_size = null;

  $required_vacancy = $_POST['required_vacancy'] ?? null;

  // NEW preference fields
  $preferred_nationalities    = $_POST['preferred_nationalities'] ?? null;
  $preferred_candidate_status = $_POST['preferred_candidate_status'] ?? null; // 日本在住 / 海外在住 / どちらでも / null
  $job_memo = $_POST['job_memo'] ?? null;

  // Validation
  if ($form_type === 'posts') {
    if ($title === '' || $summary === '' || $content === '' || empty($category)) {
      throw new InvalidArgumentException('必須フィールドを入力してください。');
    }
  } elseif ($form_type === 'jobs') {
    if ($title === '' || $job_type === null || $content === '') {
      throw new InvalidArgumentException('必須フィールドを入力してください。');
    }
  } else {
    // fallback
    if ($title === '' || $content === '') {
      throw new InvalidArgumentException('必須フィールドを入力してください。');
    }
  }

  // Build INSERT with new columns
  $sql = "
    INSERT INTO posts (
      staff_id, post_type, category, title, summary, content, image,
      company_name, job_location, job_category, job_type, salary,
      bonuses, bonus_amount, living_support, rent_support,
      insurance, transportation_charges, transport_amount_limit,
      salary_increment, increment_condition, japanese_level, experience,
      minimum_leave_per_year, employee_size, required_vacancy,
      preferred_nationalities, preferred_candidate_status, job_memo,
      date, posted_by
    ) VALUES (
      :staff_id, :post_type, :category, :title, :summary, :content, :image,
      :company_name, :job_location, :job_category, :job_type, :salary,
      :bonuses, :bonus_amount, :living_support, :rent_support,
      :insurance, :transportation_charges, :transport_amount_limit,
      :salary_increment, :increment_condition, :japanese_level, :experience,
      :minimum_leave_per_year, :employee_size, :required_vacancy,
      :preferred_nationalities, :preferred_candidate_status, :job_memo,
      :date, :posted_by
    )
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':staff_id'  => $staff_id,
    ':post_type' => $post_type,
    ':category'  => $category,
    ':title'     => $title,
    ':summary'   => $summary,
    ':content'   => $content,
    ':image'     => $image_path,

    ':company_name' => $company_name,
    ':job_location' => $job_location,
    ':job_category' => $job_category,
    ':job_type'     => $job_type,
    ':salary'       => $salary,

    ':bonuses'      => $bonuses,
    ':bonus_amount' => $bonus_amount,
    ':living_support' => $living_support,
    ':rent_support'   => $rent_support,

    ':insurance' => $insurance,
    ':transportation_charges' => $transportation_charges,
    ':transport_amount_limit' => $transport_amount_limit,

    ':salary_increment'   => $salary_increment,
    ':increment_condition' => $increment_condition,

    ':japanese_level' => $japanese_level,
    ':experience'     => $experience,
    ':minimum_leave_per_year' => $minimum_leave_per_year,
    ':employee_size'  => $employee_size, // null

    ':required_vacancy' => $required_vacancy,

    ':preferred_nationalities'    => $preferred_nationalities,
    ':preferred_candidate_status' => $preferred_candidate_status,
    ':job_memo' => $job_memo,

    ':date' => $date,
    ':posted_by' => $posted_by,
  ]);

  // Clear temp preview
  unset($_SESSION['preview_title'], $_SESSION['preview_content'], $_SESSION['preview_summary'],
        $_SESSION['preview_category'], $_SESSION['preview_form_type'], $_SESSION['preview_image']);

  // Success HTML
  ?>
  <!doctype html>
  <html lang="ja">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>投稿が完了しました</title>
    <style>
      body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;background:#f8fafc;margin:0;padding:24px}
      .card{max-width:720px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px;box-shadow:0 4px 12px rgba(0,0,0,.06)}
      h1{margin:0 0 8px;font-size:20px;color:#111827}
      p{margin:8px 0;color:#374151}
      .actions{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap}
      a.btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none;border:1px solid #dbe7f5;background:#f3f9ff;color:#0c4a7a}
      a.btn.primary{background:#2a7de1;color:#fff;border-color:#2a7de1}
      a.btn:hover{background:#e9f5ff}
      a.btn.primary:hover{filter:brightness(.95)}
    </style>
  </head>
  <body>
    <div class="card">
      <h1>投稿が完了しました</h1>
      <p>新着採用は <strong>「saiyou.php」</strong> にてご確認ください。</p>
      <div class="actions">
        <a class="btn primary" href="/saiyou.php">求人報一覧を開く</a>
        <a class="btn" href="/php/staffdb.php">スタッフDBへ戻る</a>
      </div>
    </div>
  </body>
  </html>
  <?php
  exit;

} catch (Throwable $e) {
  http_response_code(500);
  $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
  echo "<!doctype html><html lang=\"ja\"><head><meta charset=\"utf-8\" /><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" /><title>エラー</title></head><body style=\"font-family:ui-sans-serif,system-ui,'Segoe UI',Roboto,'Noto Sans JP',Meiryo,Arial,sans-serif;padding:24px\"><div style=\"max-width:720px;margin:40px auto;background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:22px\"><h1>投稿に失敗しました</h1><p style=\"color:#7f1d1d\">".$msg."</p><p><a href=\"/php/staffdb.php\">スタッフDBへ戻る</a></p></div></body></html>";
  exit;
}
