<?php
// --------------------------------------------------------------------
// fetch_staff_posts.php
// Returns the 20 most recent posts as JSON for staffdb.php.
// --------------------------------------------------------------------

// Enable error reporting (for debugging; remove or lower in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Paths for logs
$log_dir     = "/home/it-future/www/itf/logs/";
$error_log   = $log_dir . "fetch_posts_error.log";
$success_log = $log_dir . "fetch_posts_success.log";

// Ensure log directory exists and is writable
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0775, true);
    file_put_contents($error_log, "Log directory created at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
}
if (!is_writable($log_dir)) {
    file_put_contents($error_log, "Log directory is not writable at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'ログディレクトリが書き込み可能ではありません。'
    ]);
    exit;
}
file_put_contents($success_log, "Script started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Set response headers
header("Content-Type: application/json; charset=UTF-8");
// Since this is same‐domain (“it-future.jp”) fetch, you can safely omit CORS in most cases.
// If you do need CORS, ensure it matches exactly:
header("Access-Control-Allow-Origin: https://it-future.jp");
header("Access-Control-Allow-Credentials: true");

// Initialize the default response
$response = [
    'success' => false,
    'message' => '',
    'posts'   => []
];

// ─── Session Setup ────────────────────────────────────────────────────
ini_set('session.cookie_path',    '/');
ini_set('session.cookie_domain',  '.it-future.jp');
ini_set('session.cookie_lifetime','86400');
ini_set('session.cookie_secure',  true);
ini_set('session.cookie_httponly','true');
ini_set('session.cookie_samesite','Lax');

try {
    session_start();
    file_put_contents($success_log, "Session started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents($success_log, "Cookies received: " . json_encode($_COOKIE) . "\n", FILE_APPEND);
    file_put_contents($success_log, "Session data: " . json_encode($_SESSION) . "\n", FILE_APPEND);
} catch (Exception $e) {
    $error_message = "Session Start Failed: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);
    $response['message'] = "セッション開始に失敗しました: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

// ─── Session Check ────────────────────────────────────────────────────
file_put_contents($success_log, "Before session check at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    $response['message'] = 'ログインしてください。';
    file_put_contents($error_log, 
        "Session check failed: ID or username not set | Session: " . json_encode($_SESSION) 
        . " | Cookies: " . json_encode($_COOKIE) . " | Time: " . date('Y-m-d H:i:s') . "\n", 
        FILE_APPEND
    );
    echo json_encode($response);
    exit;
}
file_put_contents($success_log, "Session check passed for user " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// ─── Include Database Connection ───────────────────────────────────────
try {
    file_put_contents($success_log, "Attempting to include db_connect.php at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    $db_connect_path = __DIR__ . '/db_connect.php';
    if (!file_exists($db_connect_path)) {
        throw new Exception("db_connect.php file not found at $db_connect_path");
    }
    require_once $db_connect_path;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("PDO object is not initialized in db_connect.php");
    }

    file_put_contents($success_log, "Database connection included successfully at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
    $error_message = "Include db_connect.php Failed: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);

    $response['message'] = "データベース接続ファイルの読み込みに失敗しました: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

// ─── Fetch 20 Most Recent Posts ─────────────────────────────────────────
try {
    file_put_contents($success_log, "Attempting to fetch posts at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Adjust “date” column name if your schema differs. Here we assume your posts table has a “date” field.
    $query = "SELECT * FROM posts ORDER BY date DESC LIMIT 20";
    file_put_contents($success_log, "Executing query: $query at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    file_put_contents($success_log, "Successfully fetched " . count($posts) . " posts at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Process image paths and generate a short summary (first 100 chars of summary)
    foreach ($posts as &$item) {
        if (!empty($item['image'])) {
            // If your stored path includes “../Uploads/…”, fix it here:
            $item['image'] = str_replace('../Uploads/', 'Uploads/', $item['image']);
        }
        $item['short_summary'] = mb_substr($item['summary'], 0, 100, 'UTF-8') 
                                 . (mb_strlen($item['summary'], 'UTF-8') > 100 ? '…' : '');
    }
    unset($item);

    $response['success'] = true;
    $response['posts']   = $posts;
} catch (PDOException $e) {
    $error_message = "Fetch Posts PDO Error: " . $e->getMessage() 
                   . " | Query: " . (isset($query) ? $query : 'N/A') 
                   . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);

    $response['message'] = "データベースエラー: " . $e->getMessage();
} catch (Exception $e) {
    $error_message = "Fetch Posts General Error: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s');
    file_put_contents($error_log, $error_message . "\n", FILE_APPEND);

    $response['message'] = "一般エラー: " . $e->getMessage();
}

// ─── Always send valid JSON (never an empty body) ───────────────────────
file_put_contents($success_log, "Outputting JSON response at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
