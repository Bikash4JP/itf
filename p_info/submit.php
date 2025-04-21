<?php
session_start();
if (!isset($_SESSION['application_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITF Recruitment - Application Submitted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-itf-red">ITF Recruitment - Application Submitted</h1>
        <div class="card">
            <div class="card-body text-center">
                <h3>応募ありがとうございます！</h3>
                <p>応募が完了しました。近日中のご連絡をお待ちください。</p>
                <a href="../index.php" class="btn btn-itf-red">ホームに戻る (Back to Home)</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>