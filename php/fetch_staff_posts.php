<?php
ini_set('session.cookie_path', '/IDT');
session_start();
header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "message" => "ログインしてください。"]);
    exit;
}

try {
    // Database connection (local)
    $pdo = new PDO("mysql:host=localhost;dbname=itf", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Fetch all recent posts (both news and jobs), ordered by created_at DESC
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process posts
    foreach ($posts as &$post) {
        // Prepare short summary (first 100 words)
        $words = explode(' ', $post['summary'] ?? $post['content']);
        $post['short_summary'] = implode(' ', array_slice($words, 0, 100));
        if (count($words) > 100) {
            $post['short_summary'] .= '...';
        }

        // Adjust image path
        if ($post['image']) {
            $post['image'] = str_replace('../uploads/', 'uploads/', $post['image']);
        }
    }

    echo json_encode(["success" => true, "posts" => $posts]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "エラー: " . $e->getMessage()]);
}
?>