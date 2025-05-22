<?php
// Ensure error reporting is enabled for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent unwanted output
ob_start();

// Set headers
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: https://it-future.jp");
header("Access-Control-Allow-Credentials: true");

// Log folder path
$log_dir = "/home/it-future/www/itf/logs/";
$error_log = $log_dir . "fetch_posts_error.log";
$success_log = $log_dir . "fetch_posts_success.log";

// Check if log directory exists and is writable
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0775, true);
    file_put_contents($error_log, "Log directory created at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
}
if (!is_writable($log_dir)) {
    file_put_contents($error_log, "Log directory is not writable at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'ログディレクトリが書き込み可能ではありません。']);
    ob_end_clean();
    exit;
}

// Log script start
file_put_contents($success_log, "Fetch posts script started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Initialize response array
$response = ['success' => false, 'message' => ''];

// Start session
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');

// Check if session_id is passed via query parameter
if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
    session_id($_GET['session_id']);
}

try {
    session_start();
    file_put_contents($success_log, "Session started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
    $error_message = "Session Start Failed: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);
    $response['message'] = "セッション開始に失敗しました: " . $e->getMessage();
    echo json_encode($response);
    ob_end_clean();
    exit;
}

// Log session data
file_put_contents($success_log, "Session data: " . json_encode($_SESSION) . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Check session
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    $response['message'] = 'ログインしてください。';
    file_put_contents($error_log, "Session check failed: ID or username not set | Session data: " . json_encode($_SESSION) . " | Cookies: " . json_encode($_COOKIE) . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode($response);
    ob_end_clean();
    exit;
}

// Log session check passed
file_put_contents($success_log, "Session check passed for user " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Include database connection
try {
    if (!file_exists(__DIR__ . '/db_connect.php')) {
        throw new Exception("db_connect.php file not found");
    }
    require_once __DIR__ . '/db_connect.php';
    if (!isset($pdo)) {
        throw new Exception("PDO object not initialized");
    }
    file_put_contents($success_log, "Database connection included successfully at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
    $error_message = "Include db_connect.php Failed: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);
    $response['message'] = "データベース接続ファイルの読み込みに失敗しました: " . $e->getMessage();
    echo json_encode($response);
    ob_end_clean();
    exit;
}

try {
    // Log before fetching
    file_put_contents($success_log, "Attempting to fetch posts at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Fetch recent 20 posts (news and jobs)
    $query = "SELECT * FROM posts ORDER BY date DESC LIMIT 20";
    file_put_contents($success_log, "Executing query: $query at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log success
    file_put_contents($success_log, "Successfully fetched " . count($posts) . " posts at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Process image paths and add short summary
    foreach ($posts as &$item) {
        if ($item['image']) {
            $item['image'] = str_replace('../uploads/', 'uploads/', $item['image']);
        }
        $item['short_summary'] = substr($item['summary'], 0, 100) . (strlen($item['summary']) > 100 ? '...' : '');
    }

    $response['success'] = true;
    $response['posts'] = $posts;
} catch (PDOException $e) {
    $error_message = "Fetch Posts PDO Error: " . $e->getMessage() . " | Query: " . (isset($query) ? $query : 'N/A') . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);
    $response['message'] = "データベースエラー: " . $e->getMessage();
} catch (Exception $e) {
    $error_message = "Fetch Posts General Error: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);
    $response['message'] = "一般エラー: " . $e->getMessage();
}

// Ensure valid JSON output
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
ob_end_clean();
exit;
?>