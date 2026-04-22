<?php
// php/edit_post.php

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

date_default_timezone_set('Asia/Tokyo');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/activity_logger.php';

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    header("Location: error.php?message=" . urlencode("Invalid post ID."));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND staff_id = ? AND post_type = 'news'");
    $stmt->execute([$post_id, $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: error.php?message=" . urlencode("Post not found or you do not have permission to edit this post."));
        exit;
    }

    $oldTitle    = (string)($post['title'] ?? '');
    $oldType     = (string)($post['post_type'] ?? '');
    $oldCategory = (string)($post['category'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            die("無効なリクエストです。");
        }

        $title    = trim((string)($_POST['title'] ?? ''));
        $summary  = trim((string)($_POST['summary'] ?? ''));
        $content  = trim((string)($_POST['content'] ?? ''));
        $category = (string)($_POST['category'] ?? '');

        if ($title === '' || $summary === '' || $content === '') {
            die("Title, summary, and content cannot be empty.");
        }

        // Handle image upload
        $image_path = $post['image'];
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {

            $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
            $upload_dir = $docroot . '/uploads/'; // existing behavior
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
            $absPath    = $upload_dir . $image_name;

            $allowed_types = ['image/jpeg', 'image/png'];
            $max_size = 2 * 1024 * 1024;
            $file_type = mime_content_type($_FILES['image']['tmp_name']);
            $file_size = (int)$_FILES['image']['size'];

            if (!in_array($file_type, $allowed_types, true)) {
                die("画像はJPEGまたはPNG形式である必要があります。");
            }
            if ($file_size > $max_size) {
                die("画像サイズは2MB以下である必要があります。");
            }

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $absPath)) {
                die("画像のアップロードに失敗しました。");
            }

            // store relative like "uploads/xxxx.png"
            $image_path = 'uploads/' . $image_name;
        }

        // Update post
        $stmt = $pdo->prepare("
            UPDATE posts SET
                title = ?, summary = ?, content = ?, category = ?, image = ?
            WHERE id = ? AND staff_id = ?
        ");
        $stmt->execute([
            $title, $summary, $content, $category, $image_path,
            $post_id, $_SESSION['id']
        ]);

        // ✅ log activity
        $actor = (string)($_SESSION['username'] ?? '');
        $actorId = (int)($_SESSION['id'] ?? 0);

        if (($oldType ?: $post['post_type']) === 'news') {
            $msg = ($oldTitle !== $title)
                ? sprintf('%s が お知らせのタイトルを「%s」→「%s」に更新しました。', $actor, $oldTitle, $title)
                : sprintf('%s が お知らせ「%s」を更新しました。', $actor, $title);

            log_activity($pdo, [
                'actor_type'     => 'staff',
                'actor_staff_id' => $actorId,
                'actor_username' => $actor,
                'action'         => 'news_update',
                'entity_type'    => 'news',
                'entity_id'      => $post_id,
                'message_ja'     => $msg,
            ]);
        }

        header("Location: manage_posts.php");
        exit;
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

} catch (PDOException $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
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
            <h3>ニュース/お知らせ編集</h3>
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