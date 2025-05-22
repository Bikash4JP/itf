<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

// Check if post ID is provided and valid
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    header("Location: manage_posts.php");
    exit;
}

try {
    // Fetch the post to verify ownership and get the image path
    $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: error.php?message=" . urlencode("Post not found or you do not have permission to delete this post."));
        exit;
    }

    // Delete the image file if it exists
    if ($post['image'] && file_exists($post['image'])) {
        unlink($post['image']);
    }

    // Delete the post from the database
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);

    // Redirect back to manage posts
    header("Location: manage_posts.php");
    exit;
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit;
}
?>