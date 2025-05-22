<?php
ini_set('session.cookie_path', '/itf');
session_start();

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Check if post ID is provided and valid
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    header("Location: error.php?message=" . urlencode("Invalid post ID."));
    exit;
}

// Include database connection
require_once 'php/db_connect.php';

try {
    // Fetch the post by ID and staff_id
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: error.php?message=" . urlencode("Post not found or you do not have permission to edit this post."));
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if (empty($title) || empty($content)) {
            echo "Title and content cannot be empty.";
            exit;
        }

        $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND staff_id = ?");
        $updateStmt->execute([$title, $content, $post_id, $_SESSION['id']]);
        header("Location: staffdb.php");
        exit;
    }
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; height: 200px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Post</h2>
        <form method="POST">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea class="form-control" id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Post</button>
        </form>
    </div>
</body>
</html>