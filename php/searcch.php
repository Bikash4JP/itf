<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';
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
                <li><a href="../staffdb.php">ホーム</a></li>
                <li><a href="#" onclick="showForm('posts')">投稿を追加</a></li>
                <li><a href="#" onclick="showForm('jobs')">求人を追加</a></li>
                <li><a href="manage_posts.php">投稿を管理</a></li>
                <li><a href="searcch.php" class="active">検索</a></li>
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
                <li><a href="addstaff.php" class="menu-btn">人材を追加</a></li>
                <li><a href="t.html" class="menu-btn">人材を編集</a></li>
                <li><a href="t.html" class="menu-btn">請求書リクエスト</a></li>
                <li><a href="t.html" class="menu-btn">今月の入社</a></li>
                <li><a href="t.html" class="menu-btn">未定1</a></li>
                <li><a href="t.html" class="menu-btn">未定2</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Search box -->
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="スペースで区切って検索できます..." onkeyup="searchWorkers()" onfocus="showSuggestions()" onblur="hideSuggestions()" onkeypress="handleEnter(event)">
            </div>
            <div id="suggestions" class="suggestions"></div>

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
                <button id="printDetailsBtn" class="print-btn" style="display:none;" onclick="printDetails()">情報えんさつ</button>
            </div>
        </div>
    </div>

    <script src="../js/search.js"></script>
</body>
</html>