<?php
session_start();
header("Content-Type: application/json");
try {
    $pdo = new PDO("mysql:host=localhost;dbname=itf_db", "username", "password", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $type = $_GET['type'] ?? '';
    $query = "SELECT p.*, s.name as staff_name FROM posts p JOIN staff s ON p.staff_id = s.id";
    if ($type) {
        $query .= " WHERE p.post_type = :type";
    }
    $query .= " ORDER BY p.date DESC";
    $stmt = $pdo->prepare($query);
    if ($type) {
        $stmt->bindValue(':type', $type);
    }
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($posts);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>