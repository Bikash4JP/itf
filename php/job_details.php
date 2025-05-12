<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>求人詳細 - 株式会社アイティーエフ</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/style.min.css">
    <link rel="stylesheet" href="../css/screen.min.css">
    <link rel="stylesheet" href="../css/pagenavi-css.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/main_intro.css">
    <link rel="stylesheet" href="../css/saiyou.css">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .job-details-section {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f0fcfd;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .job-details-section h2 {
            color: #0577c5;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .job-details-section p, .job-details-section ul {
            margin: 10px 0;
            font-size: 0.95rem;
        }
        .job-details-section ul {
            list-style-type: disc;
            padding-left: 20px;
        }
        .button-container {
            text-align: right;
            margin-top: 20px;
        }
        .apply-button, .back-button {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.95rem;
            margin-left: 10px;
        }
        .apply-button {
            background: #2a7de1;
        }
        .apply-button:hover {
            background: #1a5cb8;
        }
        .back-button {
            background: #555;
        }
        .back-button:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <header id="header" class="l-header header" itemscope="" itemtype="https://schema.org/WPHeader">
        <div class="header-frame">
            <div class="header-top">
                <div class="wrap pc-flex bet">
                    <div class="header-top-in flex bet vcenter">
                        <h1 class="sp-2 logo"><a href="../index.html" class="logo-link flex vcenter"><img src="../images/logo.png" alt=""></a></h1>
                        <div id="sp-menu-open" class="sp l-animebtn sp-3">
                            <a onclick="document.getElementById('sp-menu-acc').classList.toggle('active')">
                                <div class="bar"><span></span><span></span><span></span></div>
                            </a>
                        </div>
                    </div>
                    <div class="header-menu sp-md-acc">
                        <div id="sp-menu-acc" class="pc-flex hend acc-body">
                            <ul class="contents pc-flex str hend max">
                                <li class="contents-item"><a href="../about.html">事業紹介</a></li>
                                <li class="contents-item"><a href="../company_info.html">企業情報</a></li>
                                <li class="contents-item"><a href="../saiyou.php">新着採用</a></li>
                                <li class="contents-item"><a href="../news.html">新着情報</a></li>
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
                                <li class="cta-item inquiry flex vcenter">
                                    <a href="../inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header><br>

    <!-- Job Details Section -->
    <section class="job-details-section">
        <?php
        // Database connection
        try {
            $db = new PDO('mysql:host=localhost;dbname=itf', 'root', '');
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        // Get job ID from URL
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($job_id <= 0) {
            echo '<p>無効な求人IDです。</p>';
            exit;
        }

        // Fetch job details
        $stmt = $db->prepare("SELECT * FROM posts WHERE id = ? AND post_type = 'job'");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($job) {
            echo '<h2>' . htmlspecialchars($job['title']) . '</h2>';
            echo '<p><strong>投稿日:</strong> ' . htmlspecialchars($job['date']) . '</p>';
            echo '<p><strong>会社名:</strong> ' . htmlspecialchars($job['company_name']) . '</p>';
            echo '<p><strong>概要:</strong> ' . htmlspecialchars($job['summary']) . '</p>';
            echo '<p><strong>詳細内容:</strong></p>';
            echo '<div>' . nl2br(htmlspecialchars($job['content'])) . '</div>';
            echo '<p><strong>勤務地:</strong> ' . htmlspecialchars($job['job_location']) . '</p>';
            echo '<p><strong>職種カテゴリ:</strong> ' . htmlspecialchars($job['job_category']) . '</p>';
            echo '<p><strong>雇用形態:</strong> ' . htmlspecialchars($job['job_type']) . '</p>';
            echo '<p><strong>給与:</strong> ' . htmlspecialchars($job['salary']) . '</p>';
            echo '<p><strong>賞与:</strong> ' . ($job['bonuses'] ? 'あり (金額: ' . htmlspecialchars($job['bonus_amount']) . ')' : 'なし') . '</p>';
            echo '<p><strong>住宅手当:</strong> ' . ($job['living_support'] ? 'あり (金額: ' . htmlspecialchars($job['rent_support']) . ')' : 'なし') . '</p>';
            echo '<p><strong>保険:</strong> ' . ($job['insurance'] ? 'あり' : 'なし') . '</p>';
            echo '<p><strong>交通費:</strong> ' . ($job['transportation_charges'] ? 'あり (月額上限: ' . htmlspecialchars($job['transport_amount_limit']) . ')' : 'なし') . '</p>';
            echo '<p><strong>昇給:</strong> ' . ($job['salary_increment'] ? 'あり (条件: ' . htmlspecialchars($job['increment_condition']) . ')' : 'なし') . '</p>';
            echo '<p><strong>必要日本語レベル:</strong> ' . htmlspecialchars($job['japanese_level']) . '</p>';
            echo '<p><strong>経験:</strong> ' . htmlspecialchars($job['experience']) . '</p>';
            echo '<p><strong>年間最低休暇日数:</strong> ' . htmlspecialchars($job['minimum_leave_per_year']) . ' 日</p>';
            echo '<p><strong>現在の従業員数:</strong> ' . htmlspecialchars($job['employee_size']) . '</p>';
            echo '<p><strong>募集人数:</strong> ' . htmlspecialchars($job['required_vacancy']) . '</p>';
            echo '<p><strong>投稿者:</strong> ' . htmlspecialchars($job['posted_by']) . '</p>';
            echo '<div class="button-container">';
            echo '<a href="../recruitment.php?job_id=' . htmlspecialchars($job['id']) . '" class="apply-button">この求人に応募する</a>';
            echo '<a href="../saiyou.php" class="back-button">求人一覧に戻る</a>';
            echo '</div>';
        } else {
            echo '<p>求人が見つかりませんでした。</p>';
        }
        ?>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-row">
                <!-- Column 1: Location -->
                <div class="footer-col">
                    <h3 class="footer-heading">所在地</h3>
                    <div class="footer-link">
                        <a href="../index.html" style="color: white;">株式会社アイティエフ</a>
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
                    <a href="../index.html#solution_03" class="footer-link">人財をお探しの企業様</a>
                    <a href="../about.html#support-naiyou" class="footer-link">サポート内容</a>
                    <a href="#" class="footer-link">サービス紹介</a>
                    <a href="../index.html#merit" class="footer-link">メリット</a>
                    <a href="#" class="footer-link">ギャラリー</a>
                </div>
                <!-- Column 3: Company -->
                <div class="footer-col">
                    <h3 class="footer-heading">会社案内</h3>
                    <a href="../inquiry.html" class="footer-link">代表者挨拶</a>
                    <a href="../company_info.html" class="footer-link">会社概要</a>
                    <a href="../index.html#kigyo-rinen" class="footer-link">企業理念</a>
                </div>
                <!-- Column 4: Consumer Info -->
                <div class="footer-col">
                    <h3 class="footer-heading">消費者情報</h3>
                    <p class="footer-text">よくあるご質問</p>
                    <a href="#" class="footer-link">お客様リスト</a>
                    <a href="../inquiry.html" class="footer-link">お問い合わせ</a>
                    <a href="#" class="footer-btn">プライバシーポリシー</a>
                </div>
            </div>
            <!-- Copyright -->
            <div class="footer-copyright">
                © ITF co. Ltd. ALL Rights Reserved
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/ajaxzip3.js" charset="UTF-8"></script>
    <script src="../js/form.min.js"></script>
    <script src="../js/main.min.js"></script>
    <script src="../js/scripts.js"></script>
    <script src="../js/login.js"></script>
    <script src="../js/form-validation.js"></script>
    <script type="text/javascript" src="../js/front.min.js"></script>
    <script type="text/javascript" src="../js/wp-embed.min.js"></script>
</body>
</html>