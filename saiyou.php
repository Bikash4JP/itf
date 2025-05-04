<!DOCTYPE html>
<html lang="ja" itemscope="" itemtype="https://schema.org/WebPage">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link href="https://fonts.googleapis.com/css?family=Lato:400,700,900|Noto+Sans+JP:400,500,700&subset=japanese&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" media="all" href="css/common.css">
    <title>株式会社アイティーエフ</title>
    <meta name="description" content="株式会社アイティーエフは、大阪と東京に拠点を持ち、外国人人材紹介サービスと外貨両替サービスを提供しています。外国人と企業の架け橋となり、信頼されるサポートを提供することを使命としています。">
    <meta name="robots" content="max-image-preview:large">
    <meta property="og:locale" content="ja_JP">
    <meta property="og:site_name" content="外国人材の採用活動をトータルサポートするITF 株式会社のホームページBIKASH">
    <meta property="og:type" content="website">
    <meta property="og:title" content="外国人材の採用活動をトータルサポートするITF 株式会社のホームページBIKASHフ">
    <meta property="og:description" content="株式会社アイティーエフは、大阪府大阪市に本社を構え、外国人人材紹介サービスと外貨両替サービスを提供しています。外国人と企業の架け橋となり、双方にとって最適なソリューションを提供することを使命としています。">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="株式会社アイティーエフ | 外国人人材紹介と外貨両替サービスのリーディングカンパニー">
    <meta name="twitter:description" content="株式会社アイティーエフは、大阪府大阪市に本社を構え、外国人人材紹介サービスと外貨両替サービスを提供しています。外国人と企業の架け橋となり、双方にとって最適なソリューションを提供することを使命としています。">
    <link rel="stylesheet" id="wp-block-library-css" href="css/style.min.css" type="text/css" media="all">
    <link rel="stylesheet" id="toc-screen-css" href="css/screen.min.css" type="text/css" media="all">
    <link rel="stylesheet" id="wp-pagenavi-css" href="css/pagenavi-css.css" type="text/css" media="all">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/main_intro.css">
    <link rel="stylesheet" href="css/saiyou.css">
    <link rel="stylesheet" href="css/login.css">
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/jquery-migrate.min.js"></script>
    <link rel="icon" href="images/titlle logo.PNG" sizes="32x32">
    <link rel="icon" href="images/titlle logo.PNG" sizes="192x192">
    <link rel="apple-touch-icon-precomposed" href="images/titlle logo.PNG">
    <meta name="msapplication-TileImage" content="images/titlle logo.PNG">
    <meta name="theme-color" content="#000000">
</head>

<body class="home blog">
    <div id="overlay" class="md-overlay"></div>
    <header id="header" class="l-header header" itemscope="" itemtype="https://schema.org/WPHeader">
        <div class="header-frame">
            <div class="header-top">
                <div class="wrap pc-flex bet">
                    <div class="header-top-in flex bet vcenter">
                        <h1 class="sp-2 logo"><a href="index.html" class="logo-link flex vcenter"><img src="images/logo.png" alt=""></a></h1>
                        <div id="sp-menu-open" class="sp l-animebtn sp-3">
                            <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')">
                                <div class="bar"><span></span><span></span><span></span></div>
                            </a>
                        </div>
                    </div>
                    <div class="header-menu sp-md-acc">
                        <div id="sp-menu-acc" class="pc-flex hend acc-body">
                            <ul class="contents pc-flex str hend max">
                                <li class="contents-item"><a href="about.html">事業紹介</a></li>
                                <li class="contents-item"><a href="company_info.html">企業情報</a></li>
                                <li class="contents-item"><a href="saiyou.php">新着採用</a></li>
                                <li class="contents-item"><a href="news.html">新着情報</a></li>
                            </ul>
                            <ul class="cta pc-flex max str">
                                <li class="cta-item tel sp">
                                    <a href="tel:06-6644-1800" class="sp-flex hcenter vcenter">
                                        <i class="icon icon-phone"></i>
                                        <span class="text">電話でのお問い合わせ<br><span class="note">09:00～19:00(土日祝除く)</span></span>
                                    </a>
                                </li>
                                <li class="cta-item document flex vcenter">
                                    <a href="/ITF/Recruitment" class="cta-item-link flex hcenter vcenter">資料請求</a>
                                </li>
                                <li class="cta-item estimation flex vcenter"><a href="#" class="cta-item-link flex hcenter vcenter staff-login-link" onclick="showLoginPopup()">社員ログイン</a></li>
                                <li class="cta-item inquiry flex vcenter">
                                    <a href="inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header><br>

    <!-- Main Background with Overlay -->
    <section class="bg">
        <div class="overlay">
            <form class="search-form" action="saiyou.php" method="GET">
                <input type="text" name="q" placeholder="場所、会社、カテゴリ、キーワードで検索" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit"><i class="icon-search"><img src="images/icons8-search-30.png" alt=""></i></button>
            </form>
        </div>
    </section>

    <!-- Saiyou Main Section -->
    <section class="saiyou-section">
        <h2 class="section-title"><?php echo isset($_GET['q']) && !empty($_GET['q']) ? 'マッチング求人' : '最近追加された求人'; ?></h2>
        <div class="content-wrapper">
            <div class="results">
                <?php
                // Database connection
                try {
                    $db = new PDO('mysql:host=localhost;dbname=itf', 'root', '');
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die("Database connection failed: " . $e->getMessage());
                }

                // Build query with filters
                $query = "SELECT * FROM posts WHERE post_type = 'job'";
                $params = [];

                // Search filter
                if (isset($_GET['q']) && !empty($_GET['q'])) {
                    $search = "%" . $_GET['q'] . "%";
                    $query .= " AND (title LIKE :search OR summary LIKE :search OR company_name LIKE :search OR job_location LIKE :search OR job_category LIKE :search)";
                    $params[':search'] = $search;
                }

                // Location filter
                if (isset($_GET['location']) && !empty($_GET['location'])) {
                    $query .= " AND job_location = :location";
                    $params[':location'] = $_GET['location'];
                }

                // Job type filter
                if (isset($_GET['job_type']) && !empty($_GET['job_type'])) {
                    $query .= " AND job_type = :job_type";
                    $params[':job_type'] = $_GET['job_type'];
                }

                // Japanese level filter
                if (isset($_GET['japanese_level']) && !empty($_GET['japanese_level'])) {
                    $query .= " AND japanese_level = :japanese_level";
                    $params[':japanese_level'] = $_GET['japanese_level'];
                }

                // Job category filter
                if (isset($_GET['job_category']) && !empty($_GET['job_category'])) {
                    $query .= " AND job_category = :job_category";
                    $params[':job_category'] = $_GET['job_category'];
                }

                $query .= " ORDER BY date DESC";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($jobs) > 0) {
                    foreach ($jobs as $index => $job) {
                        echo '<div class="job-item">';
                        echo '<div class="job-meta">' . htmlspecialchars($job['date']) . ' | 採用情報</div>';
                        echo '<h3 class="job-title"><a href="php/job_details.php?job_id=' . htmlspecialchars($job['id']) . '">' . htmlspecialchars($job['title']) . '</a></h3>';
                        echo '<div class="job-content">';
                        echo '<div class="job-image">';
                        if ($job['image']) {
                            // Convert database path to browser-accessible path (same as staffdb.php)
                            $image_path = str_replace('../uploads/', 'uploads/', $job['image']);
                            // Since staffdb.php doesn't use file_exists(), let's try without it
                            echo '<img src="' . htmlspecialchars($image_path) . '" alt="' . htmlspecialchars($job['title']) . '">';
                        } else {
                            echo '<img src="images/default_job_image.jpg" alt="Default Image">';
                        }
                        echo '</div>';
                        echo '<div class="job-summary">';
                        echo '<p>' . htmlspecialchars($job['summary']) . '</p>';
                        echo '<p>会社名 – ' . htmlspecialchars($job['company_name']) . '</p>';
                        echo '<p>給与 – ' . htmlspecialchars($job['salary']) . '</p>';
                        echo '<p>職種 – ' . htmlspecialchars($job['job_type']) . '</p>';
                        echo '<p>勤務地 – ' . htmlspecialchars($job['job_location']) . '</p>';
                        echo '<p>日本語レベル – ' . htmlspecialchars($job['japanese_level']) . '</p>';
                        echo '<p>年間最低休暇 – ' . htmlspecialchars($job['minimum_leave_per_year']) . ' 日</p>';
                        echo '</div>';
                        echo '</div>';
                        // Add black line between jobs, except for the last one
                        if ($index < count($jobs) - 1) {
                            echo '<hr class="job-divider">';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<p>求人が見つかりませんでした。</p>';
                }
                ?>
            </div>
            <aside class="filters">
                <h2>フィルターを設定</h2>
                <form action="saiyou.php" method="GET">
                    <label>
                        勤務地:
                        <select name="location">
                            <option value="">全て</option>
                            <?php
                            $locations = $db->query("SELECT DISTINCT job_location FROM posts WHERE post_type = 'job'")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($locations as $location) {
                                $selected = isset($_GET['location']) && $_GET['location'] === $location ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($location) . "\" $selected>" . htmlspecialchars($location) . "</option>";
                            }
                            ?>
                        </select>
                    </label>
                    <label>
                        職種:
                        <select name="job_type">
                            <option value="">全て</option>
                            <?php
                            $job_types = $db->query("SELECT DISTINCT job_type FROM posts WHERE post_type = 'job'")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($job_types as $job_type) {
                                $selected = isset($_GET['job_type']) && $_GET['job_type'] === $job_type ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($job_type) . "\" $selected>" . htmlspecialchars($job_type) . "</option>";
                            }
                            ?>
                        </select>
                    </label>
                    <label>
                        日本語レベル:
                        <select name="japanese_level">
                            <option value="">全て</option>
                            <?php
                            $japanese_levels = $db->query("SELECT DISTINCT japanese_level FROM posts WHERE post_type = 'job'")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($japanese_levels as $japanese_level) {
                                $selected = isset($_GET['japanese_level']) && $_GET['japanese_level'] === $japanese_level ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($japanese_level) . "\" $selected>" . htmlspecialchars($japanese_level) . "</option>";
                            }
                            ?>
                        </select>
                    </label>
                    <label>
                        カテゴリ:
                        <select name="job_category">
                            <option value="">全て</option>
                            <?php
                            $categories = $db->query("SELECT DISTINCT job_category FROM posts WHERE post_type = 'job'")->fetchAll(PDO::FETCH_COLUMN);
                            foreach ($categories as $category) {
                                $selected = isset($_GET['job_category']) && $_GET['job_category'] === $category ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($category) . "\" $selected>" . htmlspecialchars($category) . "</option>";
                            }
                            ?>
                        </select>
                    </label>
                    <button type="submit">適用</button>
                </form>
            </aside>
        </div>
    </section>

    <aside class="l-side"></aside>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-row">
                <!-- Column 1: Location -->
                <div class="footer-col">
                    <h3 class="footer-heading">所在地</h3>
                    <div class="footer-link">
                        <a href="index.html" style="color: white;">株式会社アイティエフ</a>
                    </div>
                    <p class="footer-text">
                        〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
                        06-6644-1800 <br>
                        〒144-0052 東京都大田区蒲田5丁目21-13<br>
                        03-6424-7747<br>
                        ueda@it-future.jp
                    </p>
                </div>
                <!-- Column 2: Services -->
                <div class="footer-col">
                    <h3 class="footer-heading">サービス案内</h3>
                    <a href="index.html#solution_03" class="footer-link">人財をお探しの企業様</a>
                    <a href="about.html#support-naiyou" class="footer-link">サポート内容</a>
                    <a href="#" class="footer-link">サービス紹介</a>
                    <a href="index.html#merit" class="footer-link">メリット</a>
                    <a href="#" class="footer-link">ギャラリー</a>
                </div>
                <!-- Column 3: Company -->
                <div class="footer-col">
                    <h3 class="footer-heading">会社案内</h3>
                    <a href="inquiry.html" class="footer-link">代表者挨拶</a>
                    <a href="company_info.html" class="footer-link">会社概要</a>
                    <a href="index.html#kigyo-rinen" class="footer-link">企業理念</a>
                </div>
                <!-- Column 4: Consumer Info -->
                <div class="footer-col">
                    <h3 class="footer-heading">消費者情報</h3>
                    <p class="footer-text">よくあるご質問</p>
                    <a href="#" class="footer-link">お客様リスト</a>
                    <a href="inquiry.html" class="footer-link">お問い合わせ</a>
                    <a href="#" class="footer-btn">プライバシーポリシー</a>
                </div>
            </div>
            <!-- Copyright -->
            <div class="footer-copyright">
                © ITF co. Ltd. ALL Rights Reserved
            </div>
        </div>
    </footer>

    <div class="login-popup" id="loginPopup" role="dialog" aria-labelledby="login-message" aria-modal="true">
        <div class="login-content">
            <span class="close-btn" onclick="hideLoginPopup()">×</span>
            <form id="loginForm" method="POST" action="php/login.php">
                <input type="hidden" name="csrf_token" value="">
                <p class="login-message">管理者から提供されたユーザーIDとパスワードを入力してください。3回以上間違えるとブロックされますのでご注意ください。</p>
                <input type="text" name="username" placeholder="ユーザー名" required>
                <input type="password" name="password" id="passwordField" placeholder="パスワード" required>
                <label class="view-password-label">
                    <input type="checkbox" id="viewPasswordCheckbox" onclick="togglePasswordVisibility()">
                    <span>パスワードを表示</span>
                </label>
                <button type="submit">サインイン</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/ajaxzip3.js" charset="UTF-8"></script>
    <script src="js/form.min.js"></script>
    <script src="js/main.min.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/login.js"></script>
    <script src="js/form-validation.js"></script>
    <script type="text/javascript" src="js/front.min.js"></script>
    <script type="text/javascript" src="js/wp-embed.min.js"></script>
</body>
</html>