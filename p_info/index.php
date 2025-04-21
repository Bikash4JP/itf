<?php
session_start();
if (isset($_SESSION['application_id'])) {
    $application_id = $_SESSION['application_id'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITF Recruitment - Apply Now</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-itf-red">ITF Recruitment - Apply Now</h1>
        <div class="progress mb-4">
            <div class="progress-bar bg-itf-red" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">Step 1 of 5</div>
        </div>
        <div class="card">
            <div class="card-header bg-itf-red text-white">
                <h3>個人情報 (Personal Information)</h3>
            </div>
            <div class="card-body">
                <form id="part1-form" action="php/save_form.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">氏名 (Name)</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label">生年月日 (Date of Birth)</label>
                        <input type="date" class="form-control" id="dob" name="dob" required>
                    </div>
                    <div class="mb-3">
                        <label for="nationality" class="form-label">国籍 (Nationality)</label>
                        <input type="text" class="form-control" id="nationality" name="nationality" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">性別 (Gender)</label>
                        <select class="form-control" id="gender" name="gender">
                            <option value="">選択してください</option>
                            <option value="男性">男性 (Male)</option>
                            <option value="女性">女性 (Female)</option>
                            <option value="その他">その他 (Other)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-itf-red">次へ (Next)</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>