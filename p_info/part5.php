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
    <title>ITF Recruitment - Self-PR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-itf-red">ITF Recruitment - Apply Now</h1>
        <div class="progress mb-4">
            <div class="progress-bar bg-itf-red" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">Step 5 of 5</div>
        </div>
        <div class="card">
            <div class="card-header bg-itf-red text-white">
                <h3>自己PR (Self-PR)</h3>
            </div>
            <div class="card-body">
                <form id="part5-form" action="php/save_part5.php" method="POST">
                    <div class="mb-3">
                        <label for="self_pr" class="form-label">自己PR (Self-PR)</label>
                        <textarea class="form-control" id="self_pr" name="self_pr" rows="3" placeholder="あなたの強みやアピールポイント"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="motivation" class="form-label">志望動機 (Motivation)</label>
                        <textarea class="form-control" id="motivation" name="motivation" rows="3" placeholder="なぜこのポジションに応募したか"></textarea>
                    </div>
                    <button type="submit" class="btn btn-itf-red">送信 (Submit)</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>