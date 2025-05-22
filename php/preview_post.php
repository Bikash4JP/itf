<?php
ini_set('session.cookie_path', '/itf');
session_start();

// Retrieve form data from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['preview_title'] = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
    $_SESSION['preview_content'] = isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '';
    $_SESSION['preview_summary'] = isset($_POST['summary']) ? htmlspecialchars($_POST['summary']) : '';
    $_SESSION['preview_category'] = isset($_POST['category']) ? htmlspecialchars($_POST['category']) : '';
    $_SESSION['preview_form_type'] = isset($_POST['form_type']) ? htmlspecialchars($_POST['form_type']) : '';

    // Handle file upload for preview
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if ($file['size'] <= 2 * 1024 * 1024 && in_array($file['type'], ['image/jpeg', 'image/png'])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $temp_path = $upload_dir . uniqid() . '_' . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $temp_path)) {
                $_SESSION['preview_image'] = $temp_path;
            }
        }
    } else {
        $_SESSION['preview_image'] = '';
    }
}

// Retrieve data from session for display
$title = isset($_SESSION['preview_title']) ? $_SESSION['preview_title'] : '';
$content = isset($_SESSION['preview_content']) ? $_SESSION['preview_content'] : '';
$summary = isset($_SESSION['preview_summary']) ? $_SESSION['preview_summary'] : '';
$category = isset($_SESSION['preview_category']) ? $_SESSION['preview_category'] : '';
$image = isset($_SESSION['preview_image']) ? $_SESSION['preview_image'] : '';
$form_type = isset($_SESSION['preview_form_type']) ? $_SESSION['preview_form_type'] : '';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プレビュー</title>
    <link rel="stylesheet" href="../css/test.css">
</head>
<body>
    <div class="news-item">
        <?php if ($image): ?>
            <img src="<?php echo htmlspecialchars($image); ?>" alt="News Image" class="news-image">
        <?php endif; ?>
        <h2 class="news-title"><?php echo htmlspecialchars($title); ?></h2>
        <p class="news-category"><?php echo htmlspecialchars($category); ?></p>
        <p class="news-summary"><?php echo nl2br(htmlspecialchars($summary)); ?></p>
        <p class="news-content"><?php echo nl2br(htmlspecialchars($content)); ?></p>
        <?php if ($form_type === 'jobs'): ?>
            <p class="job-details">
                給与: <?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : 'N/A'; ?><br>
                雇用形態: <?php echo isset($_POST['job_type']) ? htmlspecialchars($_POST['job_type']) : 'N/A'; ?>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>