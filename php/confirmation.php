<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>応募完了</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-container { display: none; margin-top: 20px; max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container my-5">
        <h2>応募が完了しました</h2>
        <p>履歴書をダウンロードしてください:</p>
        <?php
        // Include database connection
        require_once 'php/db_connect.php';

        // Load the Excel file for preview
        require dirname(__DIR__) . '/vendor/autoload.php';
        use PhpOffice\PhpSpreadsheet\IOFactory;

        // Sanitize applicant_id to prevent path traversal
        $applicant_id = basename(htmlspecialchars($_GET['applicant_id'], ENT_QUOTES, 'UTF-8'));
        $resume_path = "../resumes/{$applicant_id}_resume.xlsx";

        // Check if file exists and is readable
        if (!file_exists($resume_path) || !is_readable($resume_path)) {
            echo "履歴書ファイルが見つかりません。";
            exit();
        }
        ?>
        <a href="../resumes/<?php echo htmlspecialchars($applicant_id, ENT_QUOTES, 'UTF-8'); ?>_resume.xlsx" class="btn btn-primary">履歴書をダウンロード</a>
        <button class="btn btn-secondary mt-2" onclick="showPreview()">プレビューを表示</button>

        <!-- Preview Container -->
        <div id="previewContainer" class="preview-container">
            <?php
            if (file_exists($resume_path)) {
                try {
                    $spreadsheet = IOFactory::load($resume_path);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $highestRow = $worksheet->getHighestRow();
                    $highestColumn = $worksheet->getHighestColumn();
                    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

                    echo "<table>";
                    for ($row = 1; $row <= $highestRow; $row++) {
                        echo "<tr>";
                        for ($col = 1; $col <= $highestColumnIndex; $col++) {
                            $cell = $worksheet->getCellByColumnAndRow($col, $row);
                            $value = $cell->getValue();
                            // Check if the cell is part of a merged range
                            $isMerged = false;
                            foreach ($worksheet->getMergeCells() as $mergeRange) {
                                if ($cell->isInRange($mergeRange)) {
                                    $isMerged = true;
                                    $range = explode(':', $mergeRange);
                                    $topLeftCell = $range[0];
                                    if ($topLeftCell !== $cell->getCoordinate()) {
                                        continue 2;
                                    }
                                    break;
                                }
                            }
                            echo "<td>" . ($value ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '&nbsp;') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } catch (Exception $e) {
                    echo "プレビューの読み込みに失敗しました: " . $e->getMessage();
                }
            }
            ?>
        </div>
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