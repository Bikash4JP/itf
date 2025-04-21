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
    <title>ITF Recruitment - Work Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-itf-red">ITF Recruitment - Apply Now</h1>
        <div class="progress mb-4">
            <div class="progress-bar bg-itf-red" role="progressbar" style="width: 80%;" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">Step 4 of 5</div>
        </div>
        <div class="card">
            <div class="card-header bg-itf-red text-white">
                <h3>職務経歴 (Work Experience)</h3>
            </div>
            <div class="card-body">
                <form id="part4-form" action="php/save_part4.php" method="POST">
                    <div class="mb-3">
                        <label for="work_experience" class="form-label">職務経歴 (Work Experience)</label>
                        <textarea class="form-control" id="work_experience" name="work_experience" rows="3" placeholder="例: 〇〇会社、〇〇職、〇年"></textarea>
                    </div>
                    <button type="submit" class="btn btn-itf-red">次へ (Next)</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>