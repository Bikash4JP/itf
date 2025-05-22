<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

$message = '';
$error = '';

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "すべてのフィールドを入力してください。";
    } elseif ($new_password !== $confirm_password) {
        $error = "新しいパスワードと確認用パスワードが一致しません。";
    } elseif (strlen($new_password) < 8) {
        $error = "新しいパスワードは8文字以上である必要があります。";
    } else {
        try {
            // Fetch the current user's password hash
            $stmt = $pdo->prepare("SELECT password FROM staff WHERE id = ?");
            $stmt->execute([$_SESSION['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = "ユーザーが見つかりません。";
            } elseif (!password_verify($current_password, $user['password'])) {
                $error = "現在のパスワードが正しくありません。";
            } else {
                // Hash the new password and update it in the database
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE staff SET password = ? WHERE id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['id']]);
                $message = "パスワードが正常に更新されました。";
            }
        } catch (PDOException $e) {
            $error = "エラー: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール管理 - スタッフダッシュボード</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .alert { margin-top: 15px; }
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
            <h1>プロフィール管理</h1>
        </section>

        <section class="profile">
            <h2>パスワードリセット</h2>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="profile.php" method="POST">
                <div class="form-group">
                    <label for="current_password">現在のパスワード *</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">新しいパスワード (8文字以上) *</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">新しいパスワードの確認 *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="reset_password" class="btn btn-primary">パスワードを更新</button>
            </form>
        </section>
    </div>
</body>
</html>