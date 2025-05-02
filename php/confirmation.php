<!-- confirmation.php -->
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>応募完了</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h2>応募が完了しました</h2>
        <p>履歴書をダウンロードしてください:</p>
        <a href="<?php echo htmlspecialchars($_GET['applicant_id'] . '_resume.xlsx', ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">履歴書をダウンロード</a>
    </div>
</body>
</html>