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
    <title>ITF Recruitment - Immigration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-itf-red">ITF Recruitment - Apply Now</h1>
        <div class="progress mb-4">
            <div class="progress-bar bg-itf-red" role="progressbar" style="width: 40%;" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">Step 2 of 5</div>
        </div>
        <div class="card">
            <div class="card-header bg-itf-red text-white">
                <h3>在留資格 (Immigration Details)</h3>
            </div>
            <div class="card-body">
                <form id="part2-form" action="php/save_part2.php" method="POST">
                    <div class="mb-3">
                        <label for="visa_status" class="form-label">在留資格 (Visa Status)</label>
                        <select class="form-control" id="visa_status" name="visa_status" required>
                            <option value="">選択してください</option>
                            <option value="特定技能">特定技能</option>
                            <option value="技能実習">技能実習</option>
                            <option value="その他">その他</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="visa_expiry" class="form-label">在留期限 (Visa Expiry)</label>
                        <input type="date" class="form-control" id="visa_expiry" name="visa_expiry">
                    </div>
                    <div class="mb-3">
                        <label for="passport_number" class="form-label">パスポート番号 (Passport Number)</label>
                        <input type="text" class="form-control" id="passport_number" name="passport_number">
                    </div>
                    <button type="submit" class="btn btn-itf-red">次へ (Next)</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>