<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: news.php"); // Redirect back to news page if not logged in
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Staff Portal</title>
    <link rel="stylesheet" href="css/staffdb.css"/>
    <script src="js/staffdb.js" defer></script>
</head>
<body>
    <header>
        <div class="logo"><a href="index.html"><img src="images/logo.png" alt="ITF Logo"></a></div>
        <nav>
            <ul>
                <li><a href="staffdb.php">Home</a></li>
                <li><a href="#" onclick="showForm('posts')">Add Posts</a></li>
                <li><a href="#" onclick="showForm('jobs')">Add Jobs</a></li>
                <li><a href="#">DashBoard</a></li>
            </ul>
        </nav>
    </header>

    <section class="hero">
        <div class="overlay-text">Hello<br/><?php echo htmlspecialchars($_SESSION['username']); ?><br>Welcome to your personal dashboard <br>Wish you a Happy day.</div>
    </section>

    <section class="recent-posts">
        <h2>Recent Posts</h2>
        <hr />
        <div class="post">
            <div class="post-image">IMAGE (If Attached)</div>
            <div class="post-content">
                <p class="meta"><em>Posted Date, Main(Job/News/Announcement), Posted By(just display the staff name)</em></p>
                <p class="title"><a href="#">POST TITLE SHOULD BE DISPLAY HERE</a> <span>(as a link – see full info on clicked)</span></p>
                <p>Short Summary of the post 70-100 words or on 3,4 points (base will be decided later)<br/>
                [salary - 270k]<br/>
                Job type – care giver , Just an example</p>
            </div>
        </div>
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
                <label for="job-category">カテゴリ *</label>
                <select id="job-category" name="category" required>
                    <option value="募集">募集</option>
                    <option value="採用">採用</option>
                </select>
                <label for="job-content">内容 *</label>
                <textarea id="job-content" name="content" rows="10" required></textarea>
                <label for="salary">給与 *</label>
                <input type="number" id="salary" name="salary" required>
                <label for="job_type">雇用形態 *</label>
                <input type="text" id="job_type" name="job_type" required>
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
                    <label for="rent_support_amount">住宅手当額:</label>
                    <input type="number" id="rent_support_amount" name="rent_support_amount">
                </div>
                <label>交通費:</label>
                <label><input type="radio" name="transportation_charges" value="0" checked> なし</label>
                <label><input type="radio" name="transportation_charges" value="1"> あり</label>
                <div id="transport-amount-group" style="display:none;">
                    <label for="transport_amount">交通費:</label>
                    <input type="number" id="transport_amount" name="transport_amount">
                </div>
                <label>昇給:</label>
                <label><input type="radio" name="salary_increment" value="0" checked> なし</label>
                <label><input type="radio" name="salary_increment" value="1"> あり</label>
                <div id="increment-condition-group" style="display:none;">
                    <label for="increment_condition">昇給条件:</label>
                    <textarea id="increment_condition" name="increment_condition"></textarea>
                </div>
                <label for="jobs-image">画像 (JPEG or PNG, max 2MB)</label>
                <input type="file" id="jobs-image" name="image">
                <div class="buttons">
                    <button type="button" onclick="previewForm('jobs')">プレビュー</button>
                    <button type="submit">投稿</button>
                </div>
            </form>
        </div>
    </div>

    <div id="previewPopup" class="preview-popup">
        <div class="preview-content">
            <span class="close-btn" onclick="hidePreview()">×</span>
            <h2>プレビュー</h2>
            <div id="previewContent"></div>
            <div class="preview-actions">
                <button onclick="editForm()">編集</button>
                <button onclick="submitForm()">投稿</button>
            </div>
        </div>
    </div>

    <div id="successPopup" class="success-popup">
        <p>投稿が成功しました！</p>
    </div>

    <footer>
        <p>&copy; 2025 ITF</p>
    </footer>
</body>
</html>