<?php
// /home/it-future/www/itf/php/staffdb.php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

// ✅ show times in Japan
date_default_timezone_set('Asia/Tokyo');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// ✅ Only these usernames can see "求人管理"
$JOB_ADMIN_USERS = ['osaka_ueda', 'bikash', 'kimura'];
$canManageJobs = in_array((string) $_SESSION['username'], $JOB_ADMIN_USERS, true);

// Database connection
require_once __DIR__ . '/db_connect.php';

function getUniqueValues(PDO $pdo, string $column): array
{
    // NOTE: $column is controlled by server code (not user input)
    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM talents WHERE $column IS NOT NULL AND $column != ''");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$facilityOptions = getUniqueValues($pdo, '施設名（勤務先）');
$referrerOptions = getUniqueValues($pdo, '紹介元');
$nationalityOptions = getUniqueValues($pdo, '雇用者情報（国籍）');
$jlptOptions = getUniqueValues($pdo, 'JLPT状況');
$genderOptions = getUniqueValues($pdo, '雇用者情報（性別）');

// ✅ Recent Activities (activity_logs table)
$recentActivities = [];
try {
    $actStmt = $pdo->prepare("
        SELECT id, message_ja, created_at
        FROM activity_logs
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $actStmt->execute();
    $recentActivities = $actStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recentActivities = [];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ホーム | スタッフ</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link rel="stylesheet" href="../css/search.css">

    <!-- ✅ Recent Activities styles -->
    <style>
        /* IMPORTANT:
           Keep .info-text in DOM because search.js expects it and toggles it.
           This wrapper will be hidden when searching, and shown when search empty.
        */
        .info-text {
            margin-top: 14px;
        }

        .recent-activities {
            background: #fff;
            border: 2px solid black;
            /* requested 2px thickness */
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
            /* important for inner scroll */
        }

        .ra-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            border-bottom: 2px solid #1e90ff;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .ra-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            color: #1e90ff;
            letter-spacing: .2px;
        }

        .ra-sub {
            color: #666;
            font-size: 12px;
        }

        .ra-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 360px;
            overflow: auto;
        }

        .ra-item {
            display: flex;
            gap: 16px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .ra-item:last-child {
            border-bottom: none;
        }

        .ra-time {
            min-width: 140px;
            font-size: 12px;
            color: #666;
            white-space: nowrap;
        }

        .ra-msg {
            font-size: 14px;
            color: #111;
            line-height: 1.5;
            word-break: break-word;
        }

        .ra-empty {
            padding: 14px 0;
            color: #666;
            font-size: 14px;
        }

        .ra-note {
            margin-top: 8px;
            font-size: 12px;
            color: #667085;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo"><a href="https://it-future.jp/"><img src="../images/logo.png" alt="ITF Logo"></a></div>
        <nav>
            <ul>
                <li><a href="staffdb.php">ホーム</a></li>
                <li><a href="profile.php">プロフィール</a></li>
                <li><a href="logout.php">ログアウト</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-container">
        <!-- Sidebar Menu -->
        <div class="menu-bar">
            <div class="menu-icon"><img src="../images/searcch.png" alt="Search Icon"></div>
            <div class="menu-title">Menus</div>
            <ul>
                <li><a href="addstaff.php" class="menu-btn">✙雇用者情報</a></li>

                <?php if ($canManageJobs): ?>
                    <li><a href="manage_jobs.php" class="menu-btn">求人管理</a></li>
                    <li><a href="manage_staff.php" class="menu-btn">スタッフ管理</a></li>
                <?php endif; ?>

                <li><a href="addnews.php" class="menu-btn">✙お知らせ</a></li>
                <!-- <li><a href="addjobs.php" class="menu-btn">✙求人報</a></li> -->
                <li><a href="manage_posts.php" class="menu-btn">お知らせ管理</a></li>
                <li><a href="rireki_list.php" class="menu-btn">履歴書一覧</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content-area">

            <!-- Search box (restore old behavior) -->
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="雇用者情報氏名、施設、住所、担当者、電話番号など何でもキーワード..."
                    onkeyup="searchWorkers()" onfocus="showSuggestions()" onblur="hideSuggestions()"
                    onkeypress="handleEnter(event)">
            </div>
            <div id="suggestions" class="suggestions"></div>

            <!-- Filters -->
            <div class="filter-container">
                <select id="filterFacility">
                    <option value="">施設名（勤務先）</option>
                    <?php foreach ($facilityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filterReferrer">
                    <option value="">紹介元</option>
                    <?php foreach ($referrerOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filterNationality">
                    <option value="">雇用者情報（国籍）</option>
                    <?php foreach ($nationalityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filterJLPT">
                    <option value="">JLPT状況</option>
                    <?php foreach ($jlptOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="filterGender">
                    <option value="">雇用者情報（性別）</option>
                    <?php foreach ($genderOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ✅ IMPORTANT: Keep .info-text wrapper for search.js -->
            <div class="info-text" id="infoText">
                <div class="recent-activities" id="recentActivities">
                    <div class="ra-header">
                        <h3>最近の更新情報</h3>
                        <span class="ra-sub">最新50件</span>
                    </div>

                    <?php if (empty($recentActivities)): ?>
                        <div class="ra-empty">
                            現在、表示できるアクティビティはありません。
                            <div class="ra-note">※ 追加・更新・削除・アップロード等の操作が発生すると、ここに履歴が表示されます。</div>
                        </div>
                    <?php else: ?>
                        <ul class="ra-list">
                            <?php foreach ($recentActivities as $a): ?>
                                <?php
                                $dt = $a['created_at'] ?? '';
                                $timeText = $dt ? date('Y-m-d H:i', strtotime($dt)) : '-';
                                ?>
                                <li class="ra-item">
                                    <span class="ra-time"><?php echo htmlspecialchars($timeText, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span
                                        class="ra-msg"><?php echo htmlspecialchars($a['message_ja'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Results -->
            <div id="resultsContainer" style="display:none;">
                <div id="loading" style="display:none;">読み込み中...</div>
                <table>
                    <thead id="tableHead"></thead>
                    <tbody id="resultsBody"></tbody>
                </table>

                <div id="fullDetails" class="details-grid" style="display:none;"></div>
                <div class="details-border"></div>

                <!-- keep hidden by default; search.js usually toggles -->
                <div class="button-group" style="display:none; text-align:center;">
                    <button id="editDetailsBtn" class="edit-btn" onclick="editWorker()">編集</button>
                    <button id="printDetailsBtn" class="print-btn" onclick="printDetails()">情報印刷</button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://it-future.jp/js/search.js"></script>
</body>

</html>