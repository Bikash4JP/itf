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
        if (!empty($item['image'])) {
            // Check if uploads folder exists
            $uploads_dir = __DIR__ . '/../uploads/';
            if (!file_exists($uploads_dir)) {
                mkdir($uploads_dir, 0775, true);
            }
            // Adjust image path
            $item['image'] = str_replace('../uploads/', 'uploads/', $item['image']);
            // Verify if the image file exists
            $image_path = __DIR__ . '/../' . $item['image'];
            if (!file_exists($image_path)) {
                $item['image'] = 'images/default-news.jpg'; // Fallback to default image
            }
        } else {
            $item['image'] = 'images/default-news.jpg'; // Fallback if image is empty
        }
    }
    unset($item); // Unset reference to avoid issues

    echo json_encode($newsData);
} catch (PDOException $e) {
    // Log the error
    file_put_contents("/home/it-future/www/itf/logs/db_error.log", "Fetch News Error: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
}
?>