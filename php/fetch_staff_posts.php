<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Allow all origins (for testing)

// Include database connection
require_once 'db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_type = 'news' ORDER BY date DESC");
    $stmt->execute();
    $newsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($newsData as &$item) {
        if ($item['image']) {
            $item['image'] = str_replace('../uploads/', 'uploads/', $item['image']);
        }
    }

    echo json_encode($newsData);
} catch (PDOException $e) {
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
}
?>