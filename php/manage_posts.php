<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

// Fetch all posts
$stmt = $pdo->query("SELECT * FROM posts ORDER BY date DESC");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿管理 - スタッフダッシュボード</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link rel="stylesheet" href="../css/news.css">
    <style>
        .manage-posts {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .manage-posts h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .post-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .post-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .post-item h3 {
            font-size: 20px;
            color: #007bff;
            margin: 0 0 10px 0;
        }
        .post-item p {
            margin: 5px 0;
            color: #555;
            font-size: 16px;
        }
        .post-actions {
            margin-top: 15px;
            display: flex;
            gap: 15px;
        }
        .post-actions a {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.2s ease;
        }
        .post-actions a.edit {
            background-color: #28a745;
            color: white;
        }
        .post-actions a.edit:hover {
            background-color: #218838;
        }
        .post-actions a.delete {
            background-color: #dc3545;
            color: white;
        }
        .post-actions a.delete:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
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
        <h1>投稿管理</h1>
    </section>

    <section class="manage-posts">
        <h2>投稿リスト</h2>
        <?php if (count($posts) > 0): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-item">
                    <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                    <p>タイプ: <?php echo htmlspecialchars($post['post_type'] === 'job' ? '求人' : 'ニュース'); ?></p>
                    <p>投稿日: <?php echo htmlspecialchars($post['date']); ?></p>
                    <p>投稿者: <?php echo htmlspecialchars($post['posted_by']); ?></p>
                    <div class="post-actions">
                        <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="edit">編集</a>
                        <a href="delete_post.php?id=<?php echo $post['id']; ?>" class="delete" onclick="return confirm('この投稿を削除しますか？')">削除</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>投稿が見つかりませんでした。</p>
        <?php endif; ?>
    </section>
</body>
</html>