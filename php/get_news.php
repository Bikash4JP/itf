<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=localhost;dbname=itf", "username", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, post_type, category, title, summary, image, DATE_FORMAT(created_at, '%Y-%m-%d') AS date, staff_name AS postedBy FROM posts ORDER BY created_at DESC");
    $newsData = $stmt->fetchAll();

    echo json_encode($newsData);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([]); // Return an empty array in case of an error
}
?>