<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>応募完了 - ITF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/css/recruit.css" />
    <style>
        .preview-container { display: none; margin-top: 20px; max-height: 600px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; }
        embed { width: 100%; height: 500px; }
    </style>
</head>
<body>
    <div class="container my-5 text-center">
        <h2>応募が完了しました！</h2>
        <p>ご応募ありがとうございます。担当者よりご連絡いたしますので、しばらくお待ちください。</p>
        <?php
        $application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($application_id > 0) {
            echo '<p><a href="/php/download_rireksyo.php?application_id=' . htmlspecialchars($application_id) . '" class="btn btn-primary mt-3">履歴書をダウンロード</a></p>';
            echo '<button class="btn btn-secondary mt-2" onclick="showPreview()">プレビューを表示</button>';
        } else {
            echo '<p>履歴書のダウンロードリンクが生成できませんでした。管理者にお問い合わせください。</p>';
        }

        // Include database connection
        require_once 'php/db_connect.php';

        // Fetch the resume path
        try {
            $stmt = $pdo->prepare("SELECT resume_path FROM applicant WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$application || !$application['resume_path']) {
                $resume_path = null;
                echo "<p>履歴書ファイルが見つかりません。</p>";
            } else {
                $resume_path = $application['resume_path'];
            }
        } catch (PDOException $e) {
            echo "<p>履歴書の取得に失敗しました: " . htmlspecialchars($e->getMessage()) . "</p>";
            $resume_path = null;
        }
        ?>

        <!-- Preview Container -->
        <div id="previewContainer" class="preview-container">
            <?php
            if ($resume_path && file_exists($resume_path)) {
                // Embed the PDF for preview
                $relative_path = str_replace('/home/it-future/www/itf/', '/', $resume_path);
                echo "<embed src=\"" . htmlspecialchars($relative_path) . "\" type=\"application/pdf\" />";
            } else {
                echo "<p>プレビューを表示できません。履歴書ファイルが存在しません。</p>";
            }
            ?>
        </div>

        <a href="/saiyou.php" class="btn btn-secondary mt-3">求人一覧に戻る</a>
    </div>

    <script>
        function showPreview() {
            const previewContainer = document.getElementById('previewContainer');
            if (previewContainer.style.display === 'none' || previewContainer.style.display === '') {
                previewContainer.style.display = 'block';
            } else {
                previewContainer.style.display = 'none';
            }
        }
    </script>
</body>
</html>