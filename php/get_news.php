<?php
header('Content-Type: application/json; charset=utf-8');

// Include database connection
require_once 'db_connect.php';

try {
    $stmt = $pdo->query("SELECT id, post_type, category, title, summary, image, DATE_FORMAT(created_at, '%Y-%m-%d') AS date, staff_name AS postedBy FROM posts ORDER BY created_at DESC");
    $newsData = $stmt->fetchAll();

    echo json_encode($newsData);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]); // Return an empty array in case of an error
}
?>