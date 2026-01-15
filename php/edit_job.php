<?php
// /home/it-future/www/itf/php/edit_job.php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . '/db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo '無効なIDです。';
  exit;
}

// CSRF
if (empty($_SESSION['csrf_job_edit'])) {
  $_SESSION['csrf_job_edit'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_job_edit'];

// Load job
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
$stmt->execute([$id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$job) {
  http_response_code(404);
  echo '該当する求人が見つかりません。';
  exit;
}

// Update
$okMsg = $errMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf_job_edit'] ?? '', $_POST['csrf'] ?? '')) {
    $errMsg = '不正なリクエストです（CSRF）。';
  } else {
    // collect & sanitize
    $title        = trim($_POST['title'] ?? '');
    $summary      = trim($_POST['summary'] ?? '');
    $content      = trim($_POST['content'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $job_location = trim($_POST['job_location'] ?? '');
    $job_category = trim($_POST['job_category'] ?? '');
    $job_type     = trim($_POST['job_type'] ?? '');
    $salary       = trim($_POST['salary'] ?? '');
    $japanese_level = trim($_POST['japanese_level'] ?? '');
    $experience   = trim($_POST['experience'] ?? '');
    $minimum_leave_per_year = (string)($_POST['minimum_leave_per_year'] ?? '');
    $required_vacancy = (string)($_POST['required_vacancy'] ?? '');

    $bonuses      = isset($_POST['bonuses']) ? (int)$_POST['bonuses'] : 0;
    $bonus_amount = trim($_POST['bonus_amount'] ?? '');
    $living_support = isset($_POST['living_support']) ? (int)$_POST['living_support'] : 0;
    // naming normalized
    $rent_support = trim($_POST['rent_support'] ?? '');
    $insurance    = isset($_POST['insurance']) ? (int)$_POST['insurance'] : 0;
    $transportation_charges = isset($_POST['transportation_charges']) ? (int)$_POST['transportation_charges'] : 0;
    // naming normalized
    $transport_amount_limit = trim($_POST['transport_amount_limit'] ?? '');
    $salary_increment = isset($_POST['salary_increment']) ? (int)$_POST['salary_increment'] : 0;
    $increment_condition = trim($_POST['increment_condition'] ?? '');

    // NEW preference fields
    $preferred_nationalities    = trim($_POST['preferred_nationalities'] ?? '');
    $preferred_candidate_status = trim($_POST['preferred_candidate_status'] ?? '');
    $job_memo = trim($_POST['job_memo'] ?? '');

    // Required validation (employee_size removed)
    if (
      $title === '' || $summary === '' || $content === '' ||
      $company_name === '' || $job_location === '' || $job_category === '' ||
      $job_type === '' || $salary === '' || $japanese_level === '' ||
      $experience === '' || $minimum_leave_per_year === '' || $required_vacancy === ''
    ) {
      $errMsg = '必須項目が未入力です。';
    } else {
      $sql = "UPDATE posts SET
                title=?, summary=?, content=?, company_name=?,
                job_location=?, job_category=?, job_type=?, salary=?,
                japanese_level=?, experience=?, minimum_leave_per_year=?,
                required_vacancy=?,
                bonuses=?, bonus_amount=?, living_support=?, rent_support=?,
                insurance=?, transportation_charges=?, transport_amount_limit=?,
                salary_increment=?, increment_condition=?,
                preferred_nationalities=?, preferred_candidate_status=?, job_memo=?,
                posted_by=?,
                date=date
              WHERE id=? AND post_type='job'";
      $stmt = $pdo->prepare($sql);
      $ok = $stmt->execute([
        $title, $summary, $content, $company_name,
        $job_location, $job_category, $job_type, $salary,
        $japanese_level, $experience, $minimum_leave_per_year,
        $required_vacancy,
        $bonuses, $bonus_amount, $living_support, $rent_support,
        $insurance, $transportation_charges, $transport_amount_limit,
        $salary_increment, $increment_condition,
        $preferred_nationalities, $preferred_candidate_status, $job_memo,
        $_SESSION['username'],
        $id
      ]);
      if ($ok) {
        $okMsg = '求人情報を更新しました。';
        // fetch fresh
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job' LIMIT 1");
        $stmt->execute([$id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
      } else {
        $errMsg = '更新に失敗しました。';
      }
    }
  }
}

// Prefill helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>求人を編集 - スタッフダッシュボード</title>
  <link rel="stylesheet" href="../css/staffdb.css">
  <style>
    :root{ --border:#e6edf6; --ink:#0b2243; --muted:#667085; --primary:#1e90ff; --primary-d:#1677d3; }
    body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;background:#fff;margin:0}
    header{border-bottom:1px solid var(--border);background:#fff}
    header .wrap{max-width:1100px;margin:0 auto;padding:10px 16px;display:flex;align-items:center;gap:12px}
    .wrap-main{max-width:1000px;margin:0 auto;padding:18px 16px}
    h1{margin:10px 0 14px 0;font-size:22px;color:var(--ink)}
    .card{background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px;box-shadow:0 3px 10px rgba(10,60,150,.06)}
    label{display:block;margin:8px 0 4px;font-weight:700}
    input[type=text],input[type=number],select,textarea{width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;background:#fff}
    textarea{min-height:120px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .col-2{grid-column:1/-1}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid #dbe7f5;background:#f3f9ff;color:#0b3772;text-decoration:none;cursor:pointer}
    .btn:hover{background:#e9f5ff}
    .btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}
    .btn.primary:hover{background:var(--primary-d)}
    .msg{margin:10px 0;padding:10px 12px;border-radius:10px}
    .ok{background:#e8fff2;border:1px solid #bbf7d0;color:#166534}
    .err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b}
  </style>
</head>
<body>
  <header>
    <div class="wrap">
      <a class="btn" href="manage_posts.php">← 投稿管理へ戻る</a>
      <strong>編集: #<?= (int)$job['id'] ?> <?= h($job['title']) ?></strong>
      <span style="margin-left:auto;color:#6b7280">投稿者: <?= h($job['posted_by']) ?> / 投稿日: <?= h($job['date']) ?></span>
    </div>
  </header>

  <div class="wrap-main">
    <h1>求人を編集</h1>

    <?php if ($okMsg): ?><div class="msg ok"><?= h($okMsg) ?></div><?php endif; ?>
    <?php if ($errMsg): ?><div class="msg err"><?= h($errMsg) ?></div><?php endif; ?>

    <form class="card" method="post" action="edit_job.php?id=<?= (int)$id ?>">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

      <label>タイトル *</label>
      <input type="text" name="title" value="<?= h($job['title']) ?>" required>

      <label>概要（ハッシュタグ推奨：例「#介護 #大阪 #特定技能」） *</label>
      <textarea name="summary" required><?= h($job['summary']) ?></textarea>

      <label>内容 *</label>
      <textarea name="content" required><?= h($job['content']) ?></textarea>

      <div class="grid">
        <div>
          <label>会社名 *</label>
          <input type="text" name="company_name" value="<?= h($job['company_name']) ?>" required>
        </div>
        <div>
          <label>勤務地（都道府県） *</label>
          <select name="job_location" required>
            <?php
              $prefs = ["北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"];
              $loc = (string)($job['job_location'] ?? '');
              foreach($prefs as $p){
                $sel = ($p===$loc) ? ' selected' : '';
                echo '<option value="'.h($p).'"'.$sel.'>'.$p.'</option>';
              }
            ?>
          </select>
        </div>
        <div>
          <label>職種カテゴリ *</label>
          <select name="job_category" required>
            <?php
              $cats = ["介護","レストラン","事務","工場作業員"];
              $catv = (string)($job['job_category'] ?? '');
              foreach($cats as $c){
                $sel = ($c===$catv) ? ' selected' : '';
                echo '<option value="'.h($c).'"'.$sel.'>'.$c.'</option>';
              }
            ?>
          </select>
        </div>
        <div>
          <label>雇用形態 *</label>
          <select name="job_type" required>
            <?php
              $types = ["正社員","パートタイム","契約社員"];
              $tv = (string)($job['job_type'] ?? '');
              foreach($types as $t){
                $sel = ($t===$tv) ? ' selected' : '';
                echo '<option value="'.h($t).'"'.$sel.'>'.$t.'</option>';
              }
            ?>
          </select>
        </div>
        <div>
          <label>給与（例：年収500万円～700万円 / 370万～420万） *</label>
          <input type="text" name="salary" value="<?= h($job['salary']) ?>" required>
        </div>
        <div>
          <label>必要日本語レベル *</label>
          <select name="japanese_level" required>
            <?php
              $lvls = ["N1","N2","N3","N4","N5"];
              $cv = (string)($job['japanese_level'] ?? '');
              foreach($lvls as $lv){
                $sel = ($lv===$cv) ? ' selected' : '';
                echo '<option value="'.h($lv).'"'.$sel.'>'.$lv.'</option>';
              }
            ?>
          </select>
        </div>
      </div>

      <div class="grid">
        <div>
          <label>経験 *</label>
          <input type="text" name="experience" value="<?= h($job['experience']) ?>" required>
        </div>
        <div>
          <label>年間最低休暇日数 *</label>
          <input type="number" name="minimum_leave_per_year" value="<?= h($job['minimum_leave_per_year']) ?>" required>
        </div>
        <div>
          <label>募集人数 *</label>
          <input type="number" name="required_vacancy" value="<?= h($job['required_vacancy']) ?>" required>
        </div>
      </div>

      <div class="grid">
        <div>
          <label>賞与</label>
          <select name="bonuses">
            <option value="0" <?= ((int)$job['bonuses']===0?'selected':'') ?>>なし</option>
            <option value="1" <?= ((int)$job['bonuses']===1?'selected':'') ?>>あり</option>
          </select>
        </div>
        <div>
          <label>賞与額（任意）</label>
          <input type="text" name="bonus_amount" value="<?= h($job['bonus_amount']) ?>">
        </div>
        <div>
          <label>住宅手当</label>
          <select name="living_support">
            <option value="0" <?= ((int)$job['living_support']===0?'selected':'') ?>>なし</option>
            <option value="1" <?= ((int)$job['living_support']===1?'selected':'') ?>>あり</option>
          </select>
        </div>
        <div>
          <label>住宅手当額（任意）</label>
          <input type="text" name="rent_support" value="<?= h($job['rent_support']) ?>">
        </div>
        <div>
          <label>保険</label>
          <select name="insurance">
            <option value="0" <?= ((int)$job['insurance']===0?'selected':'') ?>>なし</option>
            <option value="1" <?= ((int)$job['insurance']===1?'selected':'') ?>>あり</option>
          </select>
        </div>
        <div>
          <label>交通費</label>
          <select name="transportation_charges">
            <option value="0" <?= ((int)$job['transportation_charges']===0?'selected':'') ?>>なし</option>
            <option value="1" <?= ((int)$job['transportation_charges']===1?'selected':'') ?>>あり</option>
          </select>
        </div>
        <div>
          <label>月額上限（交通費・任意）</label>
          <input type="text" name="transport_amount_limit" value="<?= h($job['transport_amount_limit']) ?>">
        </div>
        <div>
          <label>昇給</label>
          <select name="salary_increment">
            <option value="0" <?= ((int)$job['salary_increment']===0?'selected':'') ?>>なし</option>
            <option value="1" <?= ((int)$job['salary_increment']===1?'selected':'') ?>>あり</option>
          </select>
        </div>
        <div class="col-2">
          <label>昇給条件（任意）</label>
          <textarea name="increment_condition"><?= h($job['increment_condition']) ?></textarea>
        </div>
      </div>

      <div class="card" style="margin-top:14px">
        <h3 style="margin:0 0 8px;font-size:16px;color:#0b2243;">募集の希望条件 / メモ</h3>
        <div class="grid">
          <div class="col-2">
            <label>希望国籍（カンマ区切り可）</label>
            <textarea name="preferred_nationalities" placeholder="例：ベトナム、ネパール、インドネシア"><?= h($job['preferred_nationalities']) ?></textarea>
          </div>
          <div>
            <label>候補者の現在地</label>
            <?php
              $pcsVal = (string)($job['preferred_candidate_status'] ?? '');
              $pcsOpts = [
                '' => '指定なし',
                '日本在住' => '日本在住',
                '海外在住' => '海外在住',
                'どちらでも' => 'どちらでも',
              ];
            ?>
            <select name="preferred_candidate_status">
              <?php foreach($pcsOpts as $v=>$label): ?>
                <option value="<?= h($v) ?>" <?= ($v===$pcsVal?'selected':'') ?>><?= h($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-2">
            <label>メモ（社内向け）</label>
            <textarea name="job_memo" placeholder="例：面接は2回、夜勤可能者歓迎など"><?= h($job['job_memo']) ?></textarea>
          </div>
        </div>
      </div>

      <div class="row">
        <button type="submit" class="btn primary">保存する</button>
        <a class="btn" href="job_details.php?job_id=<?= (int)$job['id'] ?>" target="_blank">公開ページを見る</a>
        <a class="btn" href="manage_posts.php">一覧へ戻る</a>
      </div>
    </form>
  </div>
</body>
</html>
