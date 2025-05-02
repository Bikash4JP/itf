<?php
ini_set('session.cookie_path', '/IDT');
session_start();

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Check if post ID is provided
if (!isset($_GET['id'])) {
    echo "Invalid post ID.";
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=itf", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Fetch the post by ID and staff_id
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "Post not found or you do not have permission to edit this post.";
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $updateStmt = $pdo->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ? AND staff_id = ?");
        $updateStmt->execute([$_POST['title'], $_POST['content'], $_GET['id'], $_SESSION['id']]);
        header("Location: staffdb.php"); // Redirect back to dashboard
        exit;
    }
} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Post</title>
</head>
<body>
    <h2>Edit Post</h2>
    <form method="POST">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
        <label for="content">Content</label>
        <textarea id="content" name="content" required><?php echo htmlspecialchars($post['content']); ?></textarea>
        <button type="submit">Update Post</button>
    </form>
</body>
</html>