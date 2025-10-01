<?php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

function getUniqueValues($pdo, $column) {
    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM talents WHERE $column IS NOT NULL AND $column != ''");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$facilityOptions = getUniqueValues($pdo, '施設名（勤務先）');
$referrerOptions = getUniqueValues($pdo, '紹介元');
$nationalityOptions = getUniqueValues($pdo, '雇用者情報（国籍）');
$jlptOptions = getUniqueValues($pdo, 'JLPT状況');
$genderOptions = getUniqueValues($pdo, '雇用者情報（性別）');
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>労働者検索</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link rel="stylesheet" href="../css/search.css">
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
                <li><a href="addjobs.php" class="menu-btn">✙求人情報</a></li✙>
                <li><a href="addnews.php" class="menu-btn">✙お知らせ</a></li>
                <li><a href="t.html" class="menu-btn">請求書発行</a></li>
                <li><a href="t.html" class="menu-btn">今月の入社</a></li>
                <li><a href="rireki_list.php" class="menu-btn">履歴書一覧</a></li>
                <li><a href="manage_posts.php" class="menu-btn">投稿を管理</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Search box -->
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="スペースで区切って検索できます..." onkeyup="searchWorkers()" onfocus="showSuggestions()" onblur="hideSuggestions()" onkeypress="handleEnter(event)">
            </div>
            <div id="suggestions" class="suggestions"></div>

            <!-- Filters -->
            <div class="filter-container">
                <select id="filterFacility">
                    <option value="">施設名（勤務先）</option>
                    <?php foreach ($facilityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterReferrer">
                    <option value="">紹介元</option>
                    <?php foreach ($referrerOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterNationality">
                    <option value="">雇用者情報（国籍）</option>
                    <?php foreach ($nationalityOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterJLPT">
                    <option value="">JLPT状況</option>
                    <?php foreach ($jlptOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterGender">
                    <option value="">雇用者情報（性別）</option>
                    <?php foreach ($genderOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Info text -->
            <div class="info-text">
                人財検索へようこそ！このページは、人財のデータをITFのサーバーに保存し、キーワードで素早く検索できるように作りました。Googleドライブにあったデータはここに更新されています。近日中に、データを直接入力・編集できる機能を追加予定です。将来的には、数クリックでクライアントの月次請求書を作成できるように目指しています。
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
                <div class="button-group" style="display:flex; justify-content: center; gap: 10px; margin-top: 20px;">
                    <button id="editDetailsBtn" class="edit-btn" onclick="editWorker()">編集</button>
                    <button id="printDetailsBtn" class="print-btn" onclick="printDetails()">情報印刷</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/search.js"></script>
</body>
</html>