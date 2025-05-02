<?php
ini_set('session.cookie_path', '/IDT');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: /IDT/php/login.php");
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフダッシュボード</title>
    <link rel="stylesheet" href="css/staffdb.css">
    <link rel="stylesheet" href="css/news.css">
</head>
<body>
    <header>
        <div class="logo"><a href="index.html"><img src="images/logo.png" alt="ITF Logo"></a></div>
        <nav>
            <ul>
                <li><a href="staffdb.php">Home</a></li>
                <li><a href="#" onclick="showForm('posts')">Add Posts</a></li>
                <li><a href="#" onclick="showForm('jobs')">Add Jobs</a></li>
                <li><a href="dashboard.html">DashBoard</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <h1>スタッフダッシュボード</h1>
    </section>
    
    <section class="recent-posts">
        <h2>新規投稿</h2>
        <div id="postsList"></div>
    </section>

    <div class="form-popup" id="postsForm">
        <div class="form-content">
            <span class="close-btn" onclick="hideForm('posts')">×</span>
            <form id="postsFormSubmit" action="php/submit_post.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="form_type" value="posts">
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="posted_by" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <h3>Add News/Announcement</h3>
                <label for="post-title">タイトル *</label>
                <input type="text" id="post-title" name="title" required>
                <label for="post-summary">概要 (70-100 words) *</label>
                <textarea id="post-summary" name="summary" required></textarea>
                <p class="word-count">Words: <span id="post-word-count">0</span>/100</p>
                <label for="post-category">カテゴリ *</label>
                <select id="post-category" name="category" required>
                    <option value="入社情報">入社情報</option>
                    <option value="連携">連携</option>
                    <option value="募集">募集</option>
                    <option value="イベント">イベント</option>
                    <option value="セミナー">セミナー</option>
                    <option value="その他">その他</option>
                </select>
                <label for="post-content">内容 *</label>
                <textarea id="post-content" name="content" rows="10" required></textarea>
                <label for="posts-image">画像 (JPEG or PNG, max 2MB)</label>
                <input type="file" id="posts-image" name="image">
                <div class="buttons">
                    <button type="button" onclick="previewForm('posts')">プレビュー</button>
                    <button type="submit">投稿</button>
                </div>
            </form>
        </div>
    </div>

    <div class="form-popup" id="jobsForm">
        <div class="form-content">
            <span class="close-btn" onclick="hideForm('jobs')">×</span>
            <form id="jobsFormSubmit" action="php/submit_post.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="form_type" value="jobs">
                <input type="hidden" name="date" value="<?php echo date('Y-m-d'); ?>">
                <input type="hidden" name="posted_by" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                <h3>Add Job Posting</h3>

                <label for="job-title">タイトル *</label>
                <input type="text" id="job-title" name="title" required>

                <label for="job-summary">概要 (70-100 words) *</label>
                <textarea id="job-summary" name="summary" required></textarea>
                <p class="word-count">Words: <span id="job-word-count">0</span>/100</p>

                <label for="job-content">内容 *</label>
                <textarea id="job-content" name="content" rows="10" required></textarea>

                <label for="company_name">会社名 *</label>
                <input type="text" id="company_name" name="company_name" required>

                <label for="job_location">勤務地 (都道府県) *</label>
                <select id="job_location" name="job_location" required>
                    <option value="北海道">北海道</option>
                    <option value="青森県">青森県</option>
                    <option value="岩手県">岩手県</option>
                    <option value="宮城県">宮城県</option>
                    <option value="秋田県">秋田県</option>
                    <option value="山形県">山形県</option>
                    <option value="福島県">福島県</option>
                    <option value="茨城県">茨城県</option>
                    <option value="栃木県">栃木県</option>
                    <option value="群馬県">群馬県</option>
                    <option value="埼玉県">埼玉県</option>
                    <option value="千葉県">千葉県</option>
                    <option value="東京都">東京都</option>
                    <option value="神奈川県">神奈川県</option>
                    <option value="新潟県">新潟県</option>
                    <option value="富山県">富山県</option>
                    <option value="石川県">石川県</option>
                    <option value="福井県">福井県</option>
                    <option value="山梨県">山梨県</option>
                    <option value="長野県">長野県</option>
                    <option value="岐阜県">岐阜県</option>
                    <option value="静岡県">静岡県</option>
                    <option value="愛知県">愛知県</option>
                    <option value="三重県">三重県</option>
                    <option value="滋賀県">滋賀県</option>
                    <option value="京都府">京都府</option>
                    <option value="大阪府">大阪府</option>
                    <option value="兵庫県">兵庫県</option>
                    <option value="奈良県">奈良県</option>
                    <option value="和歌山県">和歌山県</option>
                    <option value="鳥取県">鳥取県</option>
                    <option value="島根県">島根県</option>
                    <option value="岡山県">岡山県</option>
                    <option value="広島県">広島県</option>
                    <option value="山口県">山口県</option>
                    <option value="徳島県">徳島県</option>
                    <option value="香川県">香川県</option>
                    <option value="愛媛県">愛媛県</option>
                    <option value="高知県">高知県</option>
                    <option value="福岡県">福岡県</option>
                    <option value="佐賀県">佐賀県</option>
                    <option value="長崎県">長崎県</option>
                    <option value="熊本県">熊本県</option>
                    <option value="大分県">大分県</option>
                    <option value="宮崎県">宮崎県</option>
                    <option value="鹿児島県">鹿児島県</option>
                    <option value="沖縄県">沖縄県</option>
                </select>

                <label for="job_category">職種カテゴリ *</label>
                <select id="job_category" name="job_category" required>
                    <option value="介護">介護</option>
                    <option value="レストラン">レストラン</option>
                    <option value="事務">事務</option>
                    <option value="工場作業員">工場作業員</option>
                </select>

                <label for="job_type">雇用形態 *</label>
                <select id="job_type" name="job_type" required>
                    <option value="正社員">正社員</option>
                    <option value="パートタイム">パートタイム</option>
                    <option value="契約社員">契約社員</option>
                </select>

                <label>給与 *</label>
                <label><input type="radio" name="salary_type" value="amount" checked> 金額</label>
                <label><input type="radio" name="salary_type" value="negotiable"> 応相談</label>
                <div id="salary-amount-group">
                    <label for="salary">給与額:</label>
                    <input type="number" id="salary" name="salary" required>
                </div>

                <label>賞与:</label>
                <label><input type="radio" name="bonuses" value="0" checked> なし</label>
                <label><input type="radio" name="bonuses" value="1"> あり</label>
                <div id="bonus-amount-group" style="display:none;">
                    <label for="bonus_amount">賞与額:</label>
                    <input type="number" id="bonus_amount" name="bonus_amount">
                </div>

                <label>住宅手当:</label>
                <label><input type="radio" name="living_support" value="0" checked> なし</label>
                <label><input type="radio" name="living_support" value="1"> あり</label>
                <div id="rent-support-group" style="display:none;">
                    <label>住宅手当額:</label>
                    <label><input type="radio" name="rent_support_type" value="amount" checked> 金額</label>
                    <label><input type="radio" name="rent_support_type" value="percentage"> パーセント</label>
                    <div id="rent-support-amount-group">
                        <label for="rent_support_amount">住宅手当額:</label>
                        <input type="number" id="rent_support_amount" name="rent_support_amount">
                    </div>
                </div>

                <label>保険:</label>
                <label><input type="radio" name="insurance" value="0" checked> なし</label>
                <label><input type="radio" name="insurance" value="1"> あり</label>

                <label>交通費:</label>
                <label><input type="radio" name="transportation_charges" value="0" checked> なし</label>
                <label><input type="radio" name="transportation_charges" value="1"> あり</label>
                <div id="transport-amount-group" style="display:none;">
                    <label for="transport_amount">月額上限:</label>
                    <input type="number" id="transport_amount" name="transport_amount">
                </div>

                <label>昇給:</label>
                <label><input type="radio" name="salary_increment" value="0" checked> なし</label>
                <label><input type="radio" name="salary_increment" value="1"> あり</label>
                <div id="increment-condition-group" style="display:none;">
                    <label for="increment_condition">昇給条件:</label>
                    <textarea id="increment_condition" name="increment_condition"></textarea>
                </div>

                <label for="japanese_level">必要日本語レベル *</label>
                <select id="japanese_level" name="japanese_level" required>
                    <option value="N1">N1</option>
                    <option value="N2">N2</option>
                    <option value="N3">N3</option>
                    <option value="N4">N4</option>
                    <option value="N5">N5</option>
                </select>

                <label for="experience">経験 *</label>
                <input type="text" id="experience" name="experience" required>

                <label for="minimum_leave_per_year">年間最低休暇日数 *</label>
                <input type="number" id="minimum_leave_per_year" name="minimum_leave_per_year" required>

                <label for="employee_size">現在の従業員数 *</label>
                <input type="number" id="employee_size" name="employee_size" required>

                <label for="required_vacancy">募集人数 *</label>
                <input type="number" id="required_vacancy" name="required_vacancy" required>

                <label for="jobs-image">画像 (JPEG or PNG, max 2MB)</label>
                <input type="file" id="jobs-image" name="image">

                <div class="buttons">
                    <button type="button" onclick="previewForm('jobs')">プレビュー</button>
                    <button type="submit">投稿</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Popup -->
    <div class="preview-popup" id="previewPopup">
        <div class="preview-content">
            <span class="close-btn">×</span>
            <div id="previewContent"></div>
            <div class="preview-actions">
                <button>編集</button>
                <button>投稿</button>
            </div>
        </div>
    </div>

    <script src="js/staffdb.js"></script>
</body>
</html>