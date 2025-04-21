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
                $response = array('success' => true, 'redirect' => '../IDT/staffdb.php');
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
} else {
    $response = array('success' => false, 'message' => '不正なリクエストです。');
    echo json_encode($response);
    exit();
}
?>