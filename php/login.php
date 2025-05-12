<?php
session_start();

// Database connection (replace with your credentials)
$host = 'localhost';
$dbname = 'itf';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $response = array('success' => false, 'message' => 'Database connection failed: ' . $e->getMessage());
    echo json_encode($response);
    exit();
}

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $response = array('success' => false, 'message' => '不正なリクエストです。もう一度お試しください。');
        echo json_encode($response);
        exit();
    }

    // Unset the CSRF token after verification
    unset($_SESSION['csrf_token']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['is_blocked']) {
                $response = array('success' => false, 'message' => 'このアカウントはブロックされています。');
                echo json_encode($response);
                exit();
            }

            if ($user['failed_attempts'] >= 3) {
                $updateStmt = $pdo->prepare("UPDATE staff SET is_blocked = 1 WHERE username = ?");
                $updateStmt->execute([$username]);
                $response = array('success' => false, 'message' => 'ログイン試行回数が上限に達したため、アカウントをブロックしました。');
                echo json_encode($response);
                exit();
            }

            if (password_verify($password, $user['password'])) {
                $updateStmt = $pdo->prepare("UPDATE staff SET failed_attempts = 0 WHERE username = ?");
                $updateStmt->execute([$username]);

                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $response = array('success' => true, 'redirect' => '../staffdb.php');
                echo json_encode($response);
                exit();
            } else {
                $newAttempts = $user['failed_attempts'] + 1;
                $updateStmt = $pdo->prepare("UPDATE staff SET failed_attempts = ? WHERE username = ?");
                $updateStmt->execute([$newAttempts, $username]);
                $response = array('success' => false, 'message' => 'ユーザー名またはパスワードが正しくありません。');
                echo json_encode($response);
                exit();
            }
        } else {
            $response = array('success' => false, 'message' => 'ユーザー名またはパスワードが正しくありません。');
            echo json_encode($response);
            exit();
        }
    } catch (PDOException $e) {
        $response = array('success' => false, 'message' => 'データベースエラーが発生しました。もう一度お試しください。');
        echo json_encode($response);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スタッフログイン - 株式会社アイティーエフ</title>
    <link rel="stylesheet" href="../css/login.css"> <!-- Keeping the same stylesheet -->
    <script src="../js/login.js"></script> <!-- Updated login.js will be used -->
</head>
<body>
    <div class="login-content">
        <form id="loginForm" method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <p class="login-message">管理者から提供されたユーザーIDとパスワードを入力してください。3回以上間違えるとブロックされますのでご注意ください。</p>
            <input type="text" name="username" placeholder="ユーザー名" required>
            <input type="password" name="password" id="passwordField" placeholder="パスワード" required>
            <label class="view-password-label">
                <input type="checkbox" id="viewPasswordCheckbox" onclick="togglePasswordVisibility()">
                <span>パスワードを表示</span>
            </label>
            <button type="submit">サインイン</button>
        </form>
    </div>
</body>
</html>