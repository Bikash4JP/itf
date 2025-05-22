<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

// Check if post ID is provided and valid
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    header("Location: error.php?message=" . urlencode("Invalid post ID."));
    exit;
}

try {
    // Fetch the post by ID and staff_id
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: error.php?message=" . urlencode("Post not found or you do not have permission to edit this post."));
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("無効なリクエストです。");
        }

        $title = trim($_POST['title']);
        $summary = trim($_POST['summary']);
        $content = trim($_POST['content']);
        $category = $_POST['category'] ?? '';
        $company_name = $_POST['company_name'] ?? null;
        $job_location = $_POST['job_location'] ?? null;
        $job_category = $_POST['job_category'] ?? null;
        $job_type = $_POST['job_type'] ?? null;
        $salary = $_POST['salary'] ?? null;
        $bonuses = isset($_POST['bonuses']) ? (int)$_POST['bonuses'] : null;
        $bonus_amount = $_POST['bonus_amount'] ?? null;
        $living_support = isset($_POST['living_support']) ? (int)$_POST['living_support'] : null;
        $rent_support = $_POST['rent_support_amount'] ?? null;
        $insurance = isset($_POST['insurance']) ? (int)$_POST['insurance'] : null;
        $transportation_charges = isset($_POST['transportation_charges']) ? (int)$_POST['transportation_charges'] : null;
        $transport_amount_limit = $_POST['transport_amount'] ?? null;
        $salary_increment = isset($_POST['salary_increment']) ? (int)$_POST['salary_increment'] : null;
        $increment_condition = $_POST['increment_condition'] ?? null;
        $japanese_level = $_POST['japanese_level'] ?? null;
        $experience = $_POST['experience'] ?? null;
        $minimum_leave_per_year = $_POST['minimum_leave_per_year'] ?? null;
        $employee_size = $_POST['employee_size'] ?? null;
        $required_vacancy = $_POST['required_vacancy'] ?? null;

        if (empty($title) || empty($summary) || empty($content)) {
            die("Title, summary, and content cannot be empty.");
        }

        // Handle image upload
        $image_path = $post['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
            $image_path = $upload_dir . $image_name;

            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024; // 2MB
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            $file_size = $_FILES['image']['size'];

            if (!in_array($file_type, $allowed_types)) {
                die("画像はJPEGまたはPNG形式である必要があります。");
            }
            if ($file_size > $max_size) {
                die("画像サイズは2MB以下である必要があります。");
            }

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                die("画像のアップロードに失敗しました。");
            }
        }

        // Update post in the database
        $stmt = $pdo->prepare("
            UPDATE posts SET 
                title = ?, summary = ?, content = ?, category = ?, image = ?, company_name = ?, 
                job_location = ?, job_category = ?, job_type = ?, salary = ?, bonuses = ?, bonus_amount = ?, 
                living_support = ?, rent_support = ?, insurance = ?, transportation_charges = ?, 
                transport_amount_limit = ?, salary_increment = ?, increment_condition = ?, 
                japanese_level = ?, experience = ?, minimum_leave_per_year = ?, employee_size = ?, 
                required_vacancy = ?
            WHERE id = ? AND staff_id = ?
        ");
        $stmt->execute([
            $title, $summary, $content, $category, $image_path, $company_name,
            $job_location, $job_category, $job_type, $salary, $bonuses, $bonus_amount,
            $living_support, $rent_support, $insurance, $transportation_charges,
            $transport_amount_limit, $salary_increment, $increment_condition,
            $japanese_level, $experience, $minimum_leave_per_year, $employee_size,
            $required_vacancy, $post_id, $_SESSION['id']
        ]);

        // Redirect back to manage posts
        header("Location: manage_posts.php");
        exit;
    }

    // Generate new CSRF token for the form
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿編集 - スタッフダッシュボード</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; height: 200px; }
        .current-image { max-width: 200px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo"><a href="../index.html"><img src="../images/logo.png" alt="ITF Logo"></a></div>
            <nav>
                <ul>
                    <li><a href="../staffdb.php">Home</a></li>
                    <li><a href="#" onclick="showForm('posts')">Add Posts</a></li>
                    <li><a href="#" onclick="showForm('jobs')">Add Jobs</a></li>
                    <li><a href="manage_posts.php">Manage Posts</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="dashboard.php">DashBoard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </header>

        <section class="hero">
            <h1>投稿編集</h1>
        </section>

        <section class="edit-post">
            <h3><?php echo $post['post_type'] === 'job' ? '求人編集' : 'ニュース/お知らせ編集'; ?></h3>
            <form action="edit_post.php?id=<?php echo $post_id; ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="post-title">タイトル *</label>
                    <input type="text" class="form-control" id="post-title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="post-summary">概要 (70-100 words) *</label>
                    <textarea class="form-control" id="post-summary" name="summary" required><?php echo htmlspecialchars($post['summary']); ?></textarea>
                    <p class="word-count">Words: <span id="post-word-count">0</span>/100</p>
                </div>
                <div class="form-group">
                    <label for="post-content">内容 *</label>
                    <textarea class="form-control" id="post-content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                <?php if ($post['post_type'] === 'news'): ?>
                    <div class="form-group">
                        <label for="post-category">カテゴリ *</label>
                        <select id="post-category" name="category" class="form-control" required>
                            <option value="入社情報" <?php echo $post['category'] === '入社情報' ? 'selected' : ''; ?>>入社情報</option>
                            <option value="連携" <?php echo $post['category'] === '連携' ? 'selected' : ''; ?>>連携</option>
                            <option value="募集" <?php echo $post['category'] === '募集' ? 'selected' : ''; ?>>募集</option>
                            <option value="イベント" <?php echo $post['category'] === 'イベント' ? 'selected' : ''; ?>>イベント</option>
                            <option value="セミナー" <?php echo $post['category'] === 'セミナー' ? 'selected' : ''; ?>>セミナー</option>
                            <option value="その他" <?php echo $post['category'] === 'その他' ? 'selected' : ''; ?>>その他</option>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="company_name">会社名 *</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($post['company_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="job_location">勤務地 (都道府県) *</label>
                        <select id="job_location" name="job_location" class="form-control" required>
                            <option value="北海道" <?php echo $post['job_location'] === '北海道' ? 'selected' : ''; ?>>北海道</option>
                            <option value="青森県" <?php echo $post['job_location'] === '青森県' ? 'selected' : ''; ?>>青森県</option>
                            <option value="岩手県" <?php echo $post['job_location'] === '岩手県' ? 'selected' : ''; ?>>岩手県</option>
                            <option value="宮城県" <?php echo $post['job_location'] === '宮城県' ? 'selected' : ''; ?>>宮城県</option>
                            <option value="秋田県" <?php echo $post['job_location'] === '秋田県' ? 'selected' : ''; ?>>秋田県</option>
                            <option value="山形県" <?php echo $post['job_location'] === '山形県' ? 'selected' : ''; ?>>山形県</option>
                            <option value="福島県" <?php echo $post['job_location'] === '福島県' ? 'selected' : ''; ?>>福島県</option>
                            <option value="茨城県" <?php echo $post['job_location'] === '茨城県' ? 'selected' : ''; ?>>茨城県</option>
                            <option value="栃木県" <?php echo $post['job_location'] === '栃木県' ? 'selected' : ''; ?>>栃木県</option>
                            <option value="群馬県" <?php echo $post['job_location'] === '群馬県' ? 'selected' : ''; ?>>群馬県</option>
                            <option value="埼玉県" <?php echo $post['job_location'] === '埼玉県' ? 'selected' : ''; ?>>埼玉県</option>
                            <option value="千葉県" <?php echo $post['job_location'] === '千葉県' ? 'selected' : ''; ?>>千葉県</option>
                            <option value="東京都" <?php echo $post['job_location'] === '東京都' ? 'selected' : ''; ?>>東京都</option>
                            <option value="神奈川県" <?php echo $post['job_location'] === '神奈川県' ? 'selected' : ''; ?>>神奈川県</option>
                            <option value="新潟県" <?php echo $post['job_location'] === '新潟県' ? 'selected' : ''; ?>>新潟県</option>
                            <option value="富山県" <?php echo $post['job_location'] === '富山県' ? 'selected' : ''; ?>>富山県</option>
                            <option value="石川県" <?php echo $post['job_location'] === '石川県' ? 'selected' : ''; ?>>石川県</option>
                            <option value="福井県" <?php echo $post['job_location'] === '福井県' ? 'selected' : ''; ?>>福井県</option>
                            <option value="山梨県" <?php echo $post['job_location'] === '山梨県' ? 'selected' : ''; ?>>山梨県</option>
                            <option value="長野県" <?php echo $post['job_location'] === '長野県' ? 'selected' : ''; ?>>長野県</option>
                            <option value="岐阜県" <?php echo $post['job_location'] === '岐阜県' ? 'selected' : ''; ?>>岐阜県</option>
                            <option value="静岡県" <?php echo $post['job_location'] === '静岡県' ? 'selected' : ''; ?>>静岡県</option>
                            <option value="愛知県" <?php echo $post['job_location'] === '愛知県' ? 'selected' : ''; ?>>愛知県</option>
                            <option value="三重県" <?php echo $post['job_location'] === '三重県' ? 'selected' : ''; ?>>三重県</option>
                            <option value="滋賀県" <?php echo $post['job_location'] === '滋賀県' ? 'selected' : ''; ?>>滋賀県</option>
                            <option value="京都府" <?php echo $post['job_location'] === '京都府' ? 'selected' : ''; ?>>京都府</option>
                            <option value="大阪府" <?php echo $post['job_location'] === '大阪府' ? 'selected' : ''; ?>>大阪府</option>
                            <option value="兵庫県" <?php echo $post['job_location'] === '兵庫県' ? 'selected' : ''; ?>>兵庫県</option>
                            <option value="奈良県" <?php echo $post['job_location'] === '奈良県' ? 'selected' : ''; ?>>奈良県</option>
                            <option value="和歌山県" <?php echo $post['job_location'] === '和歌山県' ? 'selected' : ''; ?>>和歌山県</option>
                            <option value="鳥取県" <?php echo $post['job_location'] === '鳥取県' ? 'selected' : ''; ?>>鳥取県</option>
                            <option value="島根県" <?php echo $post['job_location'] === '島根県' ? 'selected' : ''; ?>>島根県</option>
                            <option value="岡山県" <?php echo $post['job_location'] === '岡山県' ? 'selected' : ''; ?>>岡山県</option>
                            <option value="広島県" <?php echo $post['job_location'] === '広島県' ? 'selected' : ''; ?>>広島県</option>
                            <option value="山口県" <?php echo $post['job_location'] === '山口県' ? 'selected' : ''; ?>>山口県</option>
                            <option value="徳島県" <?php echo $post['job_location'] === '徳島県' ? 'selected' : ''; ?>>徳島県</option>
                            <option value="香川県" <?php echo $post['job_location'] === '香川県' ? 'selected' : ''; ?>>香川県</option>
                            <option value="愛媛県" <?php echo $post['job_location'] === '愛媛県' ? 'selected' : ''; ?>>愛媛県</option>
                            <option value="高知県" <?php echo $post['job_location'] === '高知県' ? 'selected' : ''; ?>>高知県</option>
                            <option value="福岡県" <?php echo $post['job_location'] === '福岡県' ? 'selected' : ''; ?>>福岡県</option>
                            <option value="佐賀県" <?php echo $post['job_location'] === '佐賀県' ? 'selected' : ''; ?>>佐賀県</option>
                            <option value="長崎県" <?php echo $post['job_location'] === '長崎県' ? 'selected' : ''; ?>>長崎県</option>
                            <option value="熊本県" <?php echo $post['job_location'] === '熊本県' ? 'selected' : ''; ?>>熊本県</option>
                            <option value="大分県" <?php echo $post['job_location'] === '大分県' ? 'selected' : ''; ?>>大分県</option>
                            <option value="宮崎県" <?php echo $post['job_location'] === '宮崎県' ? 'selected' : ''; ?>>宮崎県</option>
                            <option value="鹿児島県" <?php echo $post['job_location'] === '鹿児島県' ? 'selected' : ''; ?>>鹿児島県</option>
                            <option value="沖縄県" <?php echo $post['job_location'] === '沖縄県' ? 'selected' : ''; ?>>沖縄県</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="job_category">職種カテゴリ *</label>
                        <select id="job_category" name="job_category" class="form-control" required>
                            <option value="介護" <?php echo $post['job_category'] === '介護' ? 'selected' : ''; ?>>介護</option>
                            <option value="レストラン" <?php echo $post['job_category'] === 'レストラン' ? 'selected' : ''; ?>>レストラン</option>
                            <option value="事務" <?php echo $post['job_category'] === '事務' ? 'selected' : ''; ?>>事務</option>
                            <option value="工場作業員" <?php echo $post['job_category'] === '工場作業員' ? 'selected' : ''; ?>>工場作業員</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="job_type">雇用形態 *</label>
                        <select id="job_type" name="job_type" class="form-control" required>
                            <option value="正社員" <?php echo $post['job_type'] === '正社員' ? 'selected' : ''; ?>>正社員</option>
                            <option value="パートタイム" <?php echo $post['job_type'] === 'パートタイム' ? 'selected' : ''; ?>>パートタイム</option>
                            <option value="契約社員" <?php echo $post['job_type'] === '契約社員' ? 'selected' : ''; ?>>契約社員</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>給与 *</label>
                        <label><input type="radio" name="salary_type" value="amount" <?php echo $post['salary'] ? 'checked' : ''; ?>> 金額</label>
                        <label><input type="radio" name="salary_type" value="negotiable" <?php echo !$post['salary'] ? 'checked' : ''; ?>> 応相談</label>
                        <div id="salary-amount-group">
                            <label for="salary">給与額:</label>
                            <input type="number" class="form-control" id="salary" name="salary" value="<?php echo htmlspecialchars($post['salary']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>賞与:</label>
                        <label><input type="radio" name="bonuses" value="0" <?php echo $post['bonuses'] == 0 ? 'checked' : ''; ?>> なし</label>
                        <label><input type="radio" name="bonuses" value="1" <?php echo $post['bonuses'] == 1 ? 'checked' : ''; ?>> あり</label>
                        <div id="bonus-amount-group" style="display:<?php echo $post['bonuses'] == 1 ? 'block' : 'none'; ?>;">
                            <label for="bonus_amount">賞与額:</label>
                            <input type="number" class="form-control" id="bonus_amount" name="bonus_amount" value="<?php echo htmlspecialchars($post['bonus_amount']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>住宅手当:</label>
                        <label><input type="radio" name="living_support" value="0" <?php echo $post['living_support'] == 0 ? 'checked' : ''; ?>> なし</label>
                        <label><input type="radio" name="living_support" value="1" <?php echo $post['living_support'] == 1 ? 'checked' : ''; ?>> あり</label>
                        <div id="rent-support-group" style="display:<?php echo $post['living_support'] == 1 ? 'block' : 'none'; ?>;">
                            <label>住宅手当額:</label>
                            <label><input type="radio" name="rent_support_type" value="amount" checked> 金額</label>
                            <label><input type="radio" name="rent_support_type" value="percentage"> パーセント</label>
                            <div id="rent-support-amount-group">
                                <label for="rent_support_amount">住宅手当額:</label>
                                <input type="number" class="form-control" id="rent_support_amount" name="rent_support_amount" value="<?php echo htmlspecialchars($post['rent_support']); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>保険:</label>
                        <label><input type="radio" name="insurance" value="0" <?php echo $post['insurance'] == 0 ? 'checked' : ''; ?>> なし</label>
                        <label><input type="radio" name="insurance" value="1" <?php echo $post['insurance'] == 1 ? 'checked' : ''; ?>> あり</label>
                    </div>
                    <div class="form-group">
                        <label>交通費:</label>
                        <label><input type="radio" name="transportation_charges" value="0" <?php echo $post['transportation_charges'] == 0 ? 'checked' : ''; ?>> なし</label>
                        <label><input type="radio" name="transportation_charges" value="1" <?php echo $post['transportation_charges'] == 1 ? 'checked' : ''; ?>> あり</label>
                        <div id="transport-amount-group" style="display:<?php echo $post['transportation_charges'] == 1 ? 'block' : 'none'; ?>;">
                            <label for="transport_amount">月額上限:</label>
                            <input type="number" class="form-control" id="transport_amount" name="transport_amount" value="<?php echo htmlspecialchars($post['transport_amount_limit']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>昇給:</label>
                        <label><input type="radio" name="salary_increment" value="0" <?php echo $post['salary_increment'] == 0 ? 'checked' : ''; ?>> なし</label>
                        <label><input type="radio" name="salary_increment" value="1" <?php echo $post['salary_increment'] == 1 ? 'checked' : ''; ?>> あり</label>
                        <div id="increment-condition-group" style="display:<?php echo $post['salary_increment'] == 1 ? 'block' : 'none'; ?>;">
                            <label for="increment_condition">昇給条件:</label>
                            <textarea class="form-control" id="increment_condition" name="increment_condition"><?php echo htmlspecialchars($post['increment_condition']); ?></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="japanese_level">必要日本語レベル *</label>
                        <select id="japanese_level" name="japanese_level" class="form-control" required>
                            <option value="N1" <?php echo $post['japanese_level'] === 'N1' ? 'selected' : ''; ?>>N1</option>
                            <option value="N2" <?php echo $post['japanese_level'] === 'N2' ? 'selected' : ''; ?>>N2</option>
                            <option value="N3" <?php echo $post['japanese_level'] === 'N3' ? 'selected' : ''; ?>>N3</option>
                            <option value="N4" <?php echo $post['japanese_level'] === 'N4' ? 'selected' : ''; ?>>N4</option>
                            <option value="N5" <?php echo $post['japanese_level'] === 'N5' ? 'selected' : ''; ?>>N5</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="experience">経験 *</label>
                        <input type="text" class="form-control" id="experience" name="experience" value="<?php echo htmlspecialchars($post['experience']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="minimum_leave_per_year">年間最低休暇日数 *</label>
                        <input type="number" class="form-control" id="minimum_leave_per_year" name="minimum_leave_per_year" value="<?php echo htmlspecialchars($post['minimum_leave_per_year']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="employee_size">現在の従業員数 *</label>
                        <input type="number" class="form-control" id="employee_size" name="employee_size" value="<?php echo htmlspecialchars($post['employee_size']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="required_vacancy">募集人数 *</label>
                        <input type="number" class="form-control" id="required_vacancy" name="required_vacancy" value="<?php echo htmlspecialchars($post['required_vacancy']); ?>" required>
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="posts-image">画像 (JPEG or PNG, max 2MB)</label>
                    <input type="file" class="form-control" id="posts-image" name="image">
                    <?php if ($post['image']): ?>
                        <p>現在の画像: <img src="<?php echo htmlspecialchars(str_replace('../uploads/', '../uploads/', $post['image'])); ?>" alt="Current Image" class="current-image"></p>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
            </form>
        </section>
    </div>
</body>
</html>