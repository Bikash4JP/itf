<?php
ini_set('session.cookie_path', '/itf');
session_start();

// Get the error message from the URL
$message = isset($_GET['message']) ? urldecode($_GET['message']) : 'An error occurred.';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>エラー - スタッフダッシュボード</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link rel="stylesheet" href="../css/news.css">
    <style>
        .error-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
        }
        .error-container h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        .error-container p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .error-container a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .error-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>エラー</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <p><a href="manage_posts.php">投稿管理に戻る</a></p>
    </div>
</body>
</html>