<?php
// Start session to store form data across steps
session_start();

// Include database connection
try {
    require_once __DIR__ . '/php/db_connect.php';
    if (!isset($pdo)) {
        throw new Exception("PDO object not initialized");
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Validate job_id from URL
if (!isset($_GET['job_id']) || !filter_var($_GET['job_id'], FILTER_VALIDATE_INT) || $_GET['job_id'] <= 0) {
    die("Error: Please access this page with a valid job_id (e.g., ?job_id=18).");
}
$job_id = (int)$_GET['job_id'];

// Fetch job post details (only for the title)
try {
    $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ? AND post_type = 'job'");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        die("Error: Job post not found.");
    }
} catch (PDOException $e) {
    die("Failed to fetch job post: " . $e->getMessage());
}

// Determine current step
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 5) {
    $step = 1;
}

// Handle form data for each step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // Step 1: Personal Details
        $_SESSION['form_data']['personal'] = [
            'fullname' => $_POST['fullname'] ?? '',
            'furigana' => $_POST['furigana'] ?? '',
            'roman_name' => $_POST['roman_name'] ?? '',
            'dob' => $_POST['dob'] ?? '',
            'birth_place' => $_POST['birth_place'] ?? '',
            'height_cm' => $_POST['height_cm'] ?? '',
            'weight_kg' => $_POST['weight_kg'] ?? '',
            'postal_code' => $_POST['postal_code'] ?? '',
            'prefecture' => $_POST['prefecture'] ?? '',
            'city_ward' => $_POST['city_ward'] ?? '',
            'street_address' => $_POST['street_address'] ?? '',
            'home_details' => $_POST['home_details'] ?? '',
            'address' => $_POST['address'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'email' => $_POST['email'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'nationality' => $_POST['nationality'] ?? '',
            'custom_nationality' => $_POST['custom_nationality'] ?? '',
            'religion' => $_POST['religion'] ?? '',
            'marital_status' => $_POST['marital_status'] ?? ''
        ];
        header("Location: recruitment.php?job_id=$job_id&step=2");
        exit;
    } elseif ($step === 2) {
        // Step 2: Passport Details & Education
        $_SESSION['form_data']['passport'] = [
            'passport_have' => $_POST['passport_have'] ?? '',
            'passport_number' => $_POST['passport_number'] ?? '',
            'passport_expiry' => $_POST['passport_expiry'] ?? '',
            'migration_history' => $_POST['migration_history'] ?? '',
            'recent_migration_entry' => $_POST['recent_migration_entry'] ?? '',
            'recent_migration_exit' => $_POST['recent_migration_exit'] ?? '',
            'residency_status' => $_POST['residency_status'] ?? '',
            'residency_expiry' => $_POST['residency_expiry'] ?? ''
        ];
        $_SESSION['form_data']['education'] = [
            'institution_name' => $_POST['institution_name'] ?? [],
            'institution_address' => $_POST['institution_address'] ?? [],
            'edu_join_date' => $_POST['edu_join_date'] ?? [],
            'edu_leave_date' => $_POST['edu_leave_date'] ?? [],
            'faculty' => $_POST['faculty'] ?? [],
            'major' => $_POST['major'] ?? [],
            'edu_status' => $_POST['edu_status'] ?? []
        ];
        header("Location: recruitment.php?job_id=$job_id&step=3");
        exit;
    } elseif ($step === 3) {
        // Step 3: Work Experience & Certifications
        $_SESSION['form_data']['experience'] = [
            'company_name' => $_POST['company_name'] ?? [],
            'company_address' => $_POST['company_address'] ?? [],
            'business_type' => $_POST['business_type'] ?? [],
            'job_role' => $_POST['job_role'] ?? [],
            'exp_status' => $_POST['exp_status'] ?? [],
            'exp_join_date' => $_POST['exp_join_date'] ?? [],
            'exp_leave_date' => $_POST['exp_leave_date'] ?? []
        ];
        $_SESSION['form_data']['certifications'] = [
            'cert_type' => $_POST['cert_type'] ?? [],
            'cert_name' => $_POST['cert_name'] ?? [],
            'custom_skill' => $_POST['custom_skill'] ?? [],
            'cert_score' => $_POST['cert_score'] ?? [],
            'cert_date' => $_POST['cert_date'] ?? []
        ];
        header("Location: recruitment.php?job_id=$job_id&step=4");
        exit;
    } elseif ($step === 4) {
        // Step 4: Self PR & Motivation
        $_SESSION['form_data']['motivation'] = [
            'self_intro' => $_POST['self_intro'] ?? '',
            'motivation' => $_POST['motivation'] ?? '',
            'job_preference' => $_POST['job_preference'] ?? ''
        ];
        header("Location: recruitment.php?job_id=$job_id&step=5");
        exit;
    }
    // Step 5 submission is handled by formaction in the form below
}

// Retrieve session data for pre-filling forms
$personal_data = $_SESSION['form_data']['personal'] ?? [];
$passport_data = $_SESSION['form_data']['passport'] ?? [];
$education_data = $_SESSION['form_data']['education'] ?? [];
$experience_data = $_SESSION['form_data']['experience'] ?? [];
$certifications_data = $_SESSION['form_data']['certifications'] ?? [];
$motivation_data = $_SESSION['form_data']['motivation'] ?? [];
?>

<!DOCTYPE html>
<html lang="ja" itemscope="" itemtype="https://schema.org/WebPage">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Optimized SEO Meta Tags -->
    <title>株式会社アイティーエフ | 外国人財紹介サービス</title>
    <meta name="description" content="株式会社アイティーエフ『Illuminate The Future』ITF とは、外国人財の採用支援を通じて、日本で活躍する外国人をサポートする企業です。企業と外国人求職者の架け橋となり、双方のニーズに合った的確で信頼性の高いマッチングを提供しています。「一人でも多くの外国人、そして一社でも多くの企業の役に立ちたい」という想いのもと、ITFは外国人財紹介サービスのリーディングカンパニーを目指しています。">
    <meta name="keywords" content="株式会社アイティーエフ, 株式会社ITF,外国人財紹介サービス,外国人材採用支援,特定技能人材紹介サービス,外国人材紹介サービス, 外国人採用, 介護人財紹介, 全国対応, 東京, 大阪, ホテル, 外食, 特定技能人財, 登録支援機関">
    <meta name="robots" content="index, follow">
    <meta name="google-site-verification" content="k_3fZa-kgJBb0rH_1kGbjSxeXUppZQblciHPAP9yyag">
    <meta name="author" content="株式会社アイティーエフ">
    <link rel="canonical" href="https://it-future.jp/">

    <!-- Open Graph and Twitter Card -->
    <meta property="og:locale" content="ja_JP">
    <meta property="og:site_name" content="株式会社アイティーエフ">
    <meta property="og:type" content="website">
    <meta property="og:title" content="株式会社アイティーエフ | 外国人財紹介サービス">
    <meta property="og:description" content="株式会社アイティーエフ『Illuminate The Future』ITF とは、外国人財の採用支援を通じて、日本で活躍する外国人をサポートする企業です。企業と外国人求職者の架け橋となり、双方のニーズに合った的確で信頼性の高いマッチングを提供しています。「一人でも多くの外国人、そして一社でも多くの企業の役に立ちたい」という想いのもと、ITFは外国人財紹介サービスのリーディングカンパニーを目指しています。">
    <meta property="og:url" content="https://it-future.jp/">
    <meta property="og:image" content="https://it-future.jp/images/share-image.png">
    <meta property="og:image:alt" content="株式会社アイティーエフ ロゴ">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="株式会社アイティーエフ | 外国人財紹介サービス">
    <meta name="twitter:description" content="株式会社アイティーエフ『Illuminate The Future』ITF とは、外国人財の採用支援を通じて、日本で活躍する外国人をサポートする企業です。企業と外国人求職者の架け橋となり、双方のニーズに合った的確で信頼性の高いマッチングを提供しています。「一人でも多くの外国人、そして一社でも多くの企業の役に立ちたい」という想いのもと、ITFは外国人財紹介サービスのリーディングカンパニーを目指しています。">
    <meta name="twitter:image" content="https://it-future.jp/images/share-image.png">

    <!-- Schema Markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "株式会社アイティーエフ",
        "alternateName": "ITF株式会社",
        "url": "https://it-future.jp/",
        "logo": "https://it-future.jp/images/apple-touch-icon.png",
        "description": "株式会社アイティーエフは、日本全国で介護・ホテル・外食向け外国人採用サービスを提供。介護業界を中心に、外国人材の採用をサポートします。",
        "address": [
            {
                "@type": "PostalAddress",
                "addressLocality": "大阪市",
                "addressRegion": "大阪府",
                "postalCode": "556-0017",
                "streetAddress": "浪速区湊町1-4-38 近鉄新難波ビル10F",
                "addressCountry": "JP"
            },
            {
                "@type": "PostalAddress",
                "addressLocality": "大田区",
                "addressRegion": "東京都",
                "postalCode": "144-0052",
                "streetAddress": "蒲田5丁目21-13 ペガサスステーションプラザ蒲田B2F-03",
                "addressCountry": "JP"
            }
        ],
        "contactPoint": [
            {
                "@type": "ContactPoint",
                "telephone": "+81-6-6644-1800",
                "contactType": "customer service",
                "areaServed": "JP",
                "availableLanguage": ["Japanese", "English", "Indonesian", "Vietnamese", "Chinese"]
            },
            {
                "@type": "ContactPoint",
                "telephone": "+81-3-6424-7747",
                "contactType": "customer service",
                "areaServed": "JP",
                "availableLanguage": ["Japanese", "English", "Indonesian", "Vietnamese", "Chinese"]
            }
        ],
        "offers": {
            "@type": "Service",
            "name": "外国人財紹介サービス",
            "description": "日本全国で介護・ホテル・外食向け外国人採用サービスを提供。介護業界を中心に、外国人材の採用をサポートします。",
            "areaServed": "Japan",
            "serviceType": ["外国人財紹介サービス", "介護人財紹介", "ホテル向け外国人採用", "外食向け外国人採用"]
        }
    }
    </script>

    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "株式会社アイティーエフ",
                "item": "https://it-future.jp/"
            }
        ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link rel="stylesheet" type="text/css" media="all" href="css/common.css">
    <link rel="stylesheet" id="wp-block-library-css" href="css/style.min.css" type="text/css" media="all">
    <link rel="stylesheet" id="toc-screen-css" href="css/screen.min.css" type="text/css" media="all">
    <link rel="stylesheet" id="wp-pagenavi-css" href="css/pagenavi-css.css" type="text/css" media="all">
    <link rel="stylesheet" href="css/footer.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/css/recruit.css" />
    <script src="js/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
    <meta name="msapplication-TileImage" content="images/url_logo.PNG">
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
                                    <a href="/itf/Recruitment" class="cta-item-link flex hcenter vcenter">資料請求</a>
                                </li>
                                <li class="cta-item inquiry flex vcenter">
                                    <a href="inquiry.html" class="cta-item-link flex hcenter vcenter">お問い合わせ</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container my-5">
        <!-- Job Title as a Reminder -->
        <h2 class="mb-4"><?php echo htmlspecialchars($job['title']); ?> - 応募フォーム</h2>

        <!-- Progress Bar -->
        <div class="progress mb-4">
            <div class="progress-bar" role="progressbar" style="width: <?php echo ($step / 5) * 100; ?>%;" aria-valuenow="<?php echo ($step / 5) * 100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div class="step-labels d-flex justify-content-between mb-4">
            <span class="<?php echo $step >= 1 ? 'active-step' : ''; ?>">個人情報</span>
            <span class="<?php echo $step >= 2 ? 'active-step' : ''; ?>">パスポート情報・学歴</span>
            <span class="<?php echo $step >= 3 ? 'active-step' : ''; ?>">職歴・資格</span>
            <span class="<?php echo $step >= 4 ? 'active-step' : ''; ?>">自己PR</span>
            <span class="<?php echo $step >= 5 ? 'active-step' : ''; ?>">ファイル</span>
        </div>

        <!-- Multi-Step Form -->
        <form action="recruitment.php?job_id=<?php echo $job_id; ?>&step=<?php echo $step; ?>" method="POST" enctype="multipart/form-data" id="recruitment-form">
            <!-- Hidden input to pass the current step to JavaScript -->
            <input type="hidden" id="current-step" name="current_step" value="<?php echo $step; ?>" />
            <?php if ($step === 1): ?>
                <!-- Step 1: Personal Details -->
                <h4 class="section-header">個人情報</h4>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fullname" class="form-label">氏名 *</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" placeholder="氏名 (例: 田中 太郎)" value="<?php echo htmlspecialchars($personal_data['fullname'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-6">
                        <label for="furigana" class="form-label">ふりがな *</label>
                        <input type="text" id="furigana" name="furigana" class="form-control" placeholder="ふりがな (カタカナで記入してください、例: タナカ タロウ)" value="<?php echo htmlspecialchars($personal_data['furigana'] ?? ''); ?>" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="roman_name" class="form-label">ローマ字 *</label>
                        <input type="text" id="roman_name" name="roman_name" class="form-control" placeholder="ローマ字 (例: TANAKA TAROU)" value="<?php echo htmlspecialchars($personal_data['roman_name'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-6">
                        <label for="dob" class="form-label">生年月日 *</label>
                        <input type="text" id="dob" name="dob" class="form-control date-input" placeholder="生年月日 (例: 1990/01/01)" value="<?php echo htmlspecialchars($personal_data['dob'] ?? ''); ?>" required pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 1990/01/01)" />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birth_place" class="form-label">出生地 *</label>
                        <input type="text" id="birth_place" name="birth_place" class="form-control" placeholder="出生地 (日本国外の場合は国名を記入してください、例: インドネシア)" value="<?php echo htmlspecialchars($personal_data['birth_place'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-3">
                        <label for="height_cm" class="form-label">身長 (cm)</label>
                        <input type="text" id="height_cm" name="height_cm" class="form-control number-input" placeholder="例: 170" value="<?php echo htmlspecialchars($personal_data['height_cm'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label for="weight_kg" class="form-label">体重 (kg)</label>
                        <input type="text" id="weight_kg" name="weight_kg" class="form-control number-input" placeholder="例: 70" value="<?php echo htmlspecialchars($personal_data['weight_kg'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">現住所 *</label>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="postal_code" class="form-label">郵便番号 *</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="例: 123-4567" value="<?php echo htmlspecialchars($personal_data['postal_code'] ?? ''); ?>" required />
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="search-postal-code-btn" class="btn btn-secondary mt-4">郵便番号から検索</button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="prefecture" class="form-label">都道府県 *</label>
                            <input type="text" id="prefecture" name="prefecture" class="form-control" placeholder="例: 東京都" value="<?php echo htmlspecialchars($personal_data['prefecture'] ?? ''); ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label for="city_ward" class="form-label">市区町村 *</label>
                            <input type="text" id="city_ward" name="city_ward" class="form-control" placeholder="例: 千代田区" value="<?php echo htmlspecialchars($personal_data['city_ward'] ?? ''); ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label for="street_address" class="form-label">ストリート番号 *</label>
                            <input type="text" id="street_address" name="street_address" class="form-control" placeholder="例: 3-7-19" value="<?php echo htmlspecialchars($personal_data['street_address'] ?? ''); ?>" required />
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="home_details" class="form-label">ホーム詳細</label>
                            <input type="text" id="home_details" name="home_details" class="form-control" placeholder="例: 山田ビル 301" value="<?php echo htmlspecialchars($personal_data['home_details'] ?? ''); ?>" />
                        </div>
                    </div>
                    <input type="hidden" name="address" id="address" value="<?php echo htmlspecialchars($personal_data['address'] ?? ''); ?>" />
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">電話番号 *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="例: 090-1234-5678" value="<?php echo htmlspecialchars($personal_data['phone'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">メールアドレス *</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="例: example@itf.co.jp" value="<?php echo htmlspecialchars($personal_data['email'] ?? ''); ?>" required />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="gender" class="form-label">性別 *</label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="">選択してください</option>
                            <option value="Male" <?php echo ($personal_data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>男性</option>
                            <option value="Female" <?php echo ($personal_data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>女性</option>
                            <option value="Other" <?php echo ($personal_data['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>その他</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="nationality" class="form-label">国籍 *</label>
                        <select id="nationality" name="nationality" class="form-select" required>
                            <option value="">選択してください</option>
                            <option value="インドネシア国籍" <?php echo ($personal_data['nationality'] ?? '') === 'インドネシア国籍' ? 'selected' : ''; ?>>インドネシア国籍</option>
                            <option value="ベトナム国籍" <?php echo ($personal_data['nationality'] ?? '') === 'ベトナム国籍' ? 'selected' : ''; ?>>ベトナム国籍</option>
                            <option value="中国国籍" <?php echo ($personal_data['nationality'] ?? '') === '中国国籍' ? 'selected' : ''; ?>>中国国籍</option>
                            <option value="ネパール国籍" <?php echo ($personal_data['nationality'] ?? '') === 'ネパール国籍' ? 'selected' : ''; ?>>ネパール国籍</option>
                            <option value="バングラデシュ国籍" <?php echo ($personal_data['nationality'] ?? '') === 'バングラデシュ国籍' ? 'selected' : ''; ?>>バングラデシュ国籍</option>
                            <option value="ミャンマー国籍" <?php echo ($personal_data['nationality'] ?? '') === 'ミャンマー国籍' ? 'selected' : ''; ?>>ミャンマー国籍</option>
                            <option value="ペルー国籍" <?php echo ($personal_data['nationality'] ?? '') === 'ペルー国籍' ? 'selected' : ''; ?>>ペルー国籍</option>
                            <option value="韓国国籍" <?php echo ($personal_data['nationality'] ?? '') === '韓国国籍' ? 'selected' : ''; ?>>韓国国籍</option>
                            <option value="その他" <?php echo ($personal_data['nationality'] ?? '') === 'その他' ? 'selected' : ''; ?>>その他</option>
                        </select>
                        <input type="text" id="custom_nationality" name="custom_nationality" class="form-control mt-2" style="display:none;" placeholder="国籍を入力してください (例: フィリピン)" value="<?php echo htmlspecialchars($personal_data['custom_nationality'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-4">
                        <label for="religion" class="form-label">宗教</label>
                        <select id="religion" name="religion" class="form-select">
                            <option value="">選択してください</option>
                            <option value="イスラム教" <?php echo ($personal_data['religion'] ?? '') === 'イスラム教' ? 'selected' : ''; ?>>イスラム教</option>
                            <option value="キリスト教" <?php echo ($personal_data['religion'] ?? '') === 'キリスト教' ? 'selected' : ''; ?>>キリスト教</option>
                            <option value="ヒンドゥー教" <?php echo ($personal_data['religion'] ?? '') === 'ヒンドゥー教' ? 'selected' : ''; ?>>ヒンドゥー教</option>
                            <option value="仏教" <?php echo ($personal_data['religion'] ?? '') === '仏教' ? 'selected' : ''; ?>>仏教</option>
                            <option value="無宗教" <?php echo ($personal_data['religion'] ?? '') === '無宗教' ? 'selected' : ''; ?>>無宗教</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="marital_status" class="form-label">配偶者の有無 *</label>
                        <select id="marital_status" name="marital_status" class="form-select" required>
                            <option value="">選択してください</option>
                            <option value="有り（子供あり)" <?php echo ($personal_data['marital_status'] ?? '') === '有り（子供あり)' ? 'selected' : ''; ?>>有り（子供あり)</option>
                            <option value="有り（子供なし)" <?php echo ($personal_data['marital_status'] ?? '') === '有り（子供なし)' ? 'selected' : ''; ?>>有り（子供なし)</option>
                            <option value="無し" <?php echo ($personal_data['marital_status'] ?? '') === '無し' ? 'selected' : ''; ?>>無し</option>
                        </select>
                    </div>
                </div>
                <!-- Navigation Buttons -->
                <div class="form-navigation mt-4">
                    <button type="submit" class="btn btn-primary">次へ</button>
                </div>
            <?php elseif ($step === 2): ?>
                <!-- Step 2: Passport Details & Education -->
                <h4 class="section-header">パスポート情報</h4>
                <div class="mb-3">
                    <label class="form-label">パスポートはお持ちですか？ *</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="passport_have" id="passportYes" value="Yes" required <?php echo ($passport_data['passport_have'] ?? '') === 'Yes' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="passportYes">はい</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="passport_have" id="passportNo" value="No" <?php echo ($passport_data['passport_have'] ?? '') === 'No' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="passportNo">いいえ</label>
                    </div>
                </div>
                <div id="passportDetails" style="display:<?php echo ($passport_data['passport_have'] ?? '') === 'Yes' ? 'block' : 'none'; ?>;">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="passport_number" class="form-label">パスポート番号 *</label>
                            <input type="text" id="passport_number" name="passport_number" class="form-control" placeholder="例: AB1234567" value="<?php echo htmlspecialchars($passport_data['passport_number'] ?? ''); ?>" />
                        </div>
                        <div class="col-md-6">
                            <label for="passport_expiry" class="form-label">有効期限 *</label>
                            <input type="text" id="passport_expiry" name="passport_expiry" class="form-control date-input" placeholder="例: 2030/12/31" value="<?php echo htmlspecialchars($passport_data['passport_expiry'] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2030/12/31)" />
                        </div>
                    </div>
                </div>
                <div class="mb-3" id="migrationHistory" style="display:<?php echo ($passport_data['passport_have'] ?? '') === 'Yes' ? 'block' : 'none'; ?>;">
                    <label for="migration_history" class="form-label">過去の出入国歴 (回数)</label>
                    <input type="number" id="migration_history" name="migration_history" class="form-control" min="0" value="<?php echo htmlspecialchars($passport_data['migration_history'] ?? '0'); ?>" placeholder="例: 5" />
                </div>
                <div class="row mb-3" id="recentMigration" style="display:<?php echo ($passport_data['passport_have'] ?? '') === 'Yes' ? 'block' : 'none'; ?>;">
                    <div class="col-md-6">
                        <label for="recent_migration_entry" class="form-label">直近の入国日</label>
                        <input type="text" id="recent_migration_entry" name="recent_migration_entry" class="form-control date-input" placeholder="例: 2023/05/01" value="<?php echo htmlspecialchars($passport_data['recent_migration_entry'] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2023/05/01)" />
                    </div>
                    <div class="col-md-6">
                        <label for="recent_migration_exit" class="form-label">直近の出国日</label>
                        <input type="text" id="recent_migration_exit" name="recent_migration_exit" class="form-control date-input" placeholder="例: 2023/06/01" value="<?php echo htmlspecialchars($passport_data['recent_migration_exit'] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2023/06/01)" />
                    </div>
                </div>
                <div class="row mb-3" id="residencyDetails" style="display:<?php echo ($passport_data['passport_have'] ?? '') === 'Yes' ? 'block' : 'none'; ?>;">
                    <div class="col-md-6">
                        <label for="residency_status" class="form-label">現在の在留資格</label>
                        <input type="text" id="residency_status" name="residency_status" class="form-control" placeholder="例: 特定技能" value="<?php echo htmlspecialchars($passport_data['residency_status'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-6">
                        <label for="residency_expiry" class="form-label">在留期限</label>
                        <input type="text" id="residency_expiry" name="residency_expiry" class="form-control date-input" placeholder="例: 2025/12/31" value="<?php echo htmlspecialchars($passport_data['residency_expiry'] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2025/12/31)" />
                    </div>
                </div>

                <!-- Education -->
                <h4 class="section-header">学歴</h4>
                <div id="education-section">
                    <?php
                    $edu_count = count($education_data['institution_name'] ?? []);
                    if ($edu_count === 0) {
                        $edu_count = 1;
                    }
                    for ($i = 0; $i < $edu_count; $i++):
                    ?>
                        <div class="dynamic-block education-block" data-block-id="edu-<?php echo $i; ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="institution_name_<?php echo $i; ?>" class="form-label">学校名 *</label>
                                    <input type="text" id="institution_name_<?php echo $i; ?>" name="institution_name[]" class="form-control" placeholder="学校名、学習レベル (例: 東京大学、大学)" value="<?php echo htmlspecialchars($education_data['institution_name'][$i] ?? ''); ?>" required />
                                </div>
                                <div class="col-md-6">
                                    <label for="institution_address_<?php echo $i; ?>" class="form-label">学校住所</label>
                                    <input type="text" id="institution_address_<?php echo $i; ?>" name="institution_address[]" class="form-control" placeholder="例: 東京都文京区本郷7-3-1" value="<?php echo htmlspecialchars($education_data['institution_address'][$i] ?? ''); ?>" />
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="edu_join_date_<?php echo $i; ?>" class="form-label">入学日 *</label>
                                    <input type="text" id="edu_join_date_<?php echo $i; ?>" name="edu_join_date[]" class="form-control date-input" placeholder="例: 2018/04/01" value="<?php echo htmlspecialchars($education_data['edu_join_date'][$i] ?? ''); ?>" required pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2018/04/01)" />
                                </div>
                                <div class="col-md-3">
                                    <label for="edu_leave_date_<?php echo $i; ?>" class="form-label">卒業/終了日</label>
                                    <input type="text" id="edu_leave_date_<?php echo $i; ?>" name="edu_leave_date[]" class="form-control date-input edu-leave-date" placeholder="例: 2022/03/31" value="<?php echo htmlspecialchars($education_data['edu_leave_date'][$i] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2022/03/31)" />
                                </div>
                                <div class="col-md-3">
                                    <label for="faculty_<?php echo $i; ?>" class="form-label">学部</label>
                                    <input type="text" id="faculty_<?php echo $i; ?>" name="faculty[]" class="form-control" placeholder="例: 文学部" value="<?php echo htmlspecialchars($education_data['faculty'][$i] ?? ''); ?>" />
                                </div>
                                <div class="col-md-3">
                                    <label for="major_<?php echo $i; ?>" class="form-label">専攻</label>
                                    <input type="text" id="major_<?php echo $i; ?>" name="major[]" class="form-control" placeholder="例: 日本文学" value="<?php echo htmlspecialchars($education_data['major'][$i] ?? ''); ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="edu_status_<?php echo $i; ?>" class="form-label">状態 *</label>
                                <select id="edu_status_<?php echo $i; ?>" name="edu_status[]" class="form-select edu-status" required>
                                    <option value="">選択してください</option>
                                    <option value="Graduated" <?php echo ($education_data['edu_status'][$i] ?? '') === 'Graduated' ? 'selected' : ''; ?>>卒業</option>
                                    <option value="Ongoing" <?php echo ($education_data['edu_status'][$i] ?? '') === 'Ongoing' ? 'selected' : ''; ?>>在学中</option>
                                    <option value="Dropped" <?php echo ($education_data['edu_status'][$i] ?? '') === 'Dropped' ? 'selected' : ''; ?>>中退</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-danger remove-btn">削除</button>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" id="add-education-btn" class="btn btn-primary mb-3">学歴を追加</button>
                <!-- Navigation Buttons -->
                <div class="form-navigation mt-4">
                    <a href="recruitment.php?job_id=<?php echo $job_id; ?>&step=1" class="btn btn-secondary">前へ</a>
                    <button type="submit" class="btn btn-primary">次へ</button>
                </div>
            <?php elseif ($step === 3): ?>
                <!-- Step 3: Work Experience & Certifications -->
                <h4 class="section-header">職歴</h4>
                <div id="experience-section">
                    <?php
                    $exp_count = count($experience_data['company_name'] ?? []);
                    if ($exp_count === 0) {
                        $exp_count = 1;
                    }
                    for ($i = 0; $i < $exp_count; $i++):
                    ?>
                        <div class="dynamic-block experience-block" data-block-id="exp-<?php echo $i; ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_name_<?php echo $i; ?>" class="form-label">会社名 *</label>
                                    <input type="text" id="company_name_<?php echo $i; ?>" name="company_name[]" class="form-control company-name" placeholder="例: 株式会社ITF" value="<?php echo htmlspecialchars($experience_data['company_name'][$i] ?? ''); ?>" required />
                                </div>
                                <div class="col-md-6">
                                    <label for="company_address_<?php echo $i; ?>" class="form-label">会社住所</label>
                                    <input type="text" id="company_address_<?php echo $i; ?>" name="company_address[]" class="form-control" placeholder="例: 東京都渋谷区1-2-3" value="<?php echo htmlspecialchars($experience_data['company_address'][$i] ?? ''); ?>" />
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="business_type_<?php echo $i; ?>" class="form-label">業種 *</label>
                                    <input type="text" id="business_type_<?php echo $i; ?>" name="business_type[]" class="form-control business-type" placeholder="例: ITサービス" value="<?php echo htmlspecialchars($experience_data['business_type'][$i] ?? ''); ?>" required />
                                </div>
                                <div class="col-md-4">
                                    <label for="job_role_<?php echo $i; ?>" class="form-label">職種 *</label>
                                    <input type="text" id="job_role_<?php echo $i; ?>" name="job_role[]" class="form-control job-role" placeholder="例: ソフトウェアエンジニア" value="<?php echo htmlspecialchars($experience_data['job_role'][$i] ?? ''); ?>" required />
                                </div>
                                <div class="col-md-4">
                                    <label for="exp_status_<?php echo $i; ?>" class="form-label">状態 *</label>
                                    <select id="exp_status_<?php echo $i; ?>" name="exp_status[]" class="form-select exp-status" required>
                                        <option value="">選択してください</option>
                                        <option value="Current" <?php echo ($experience_data['exp_status'][$i] ?? '') === 'Current' ? 'selected' : ''; ?>>現在勤務中</option>
                                        <option value="Past" <?php echo ($experience_data['exp_status'][$i] ?? '') === 'Past' ? 'selected' : ''; ?>>過去</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="exp_join_date_<?php echo $i; ?>" class="form-label">入社日 *</label>
                                    <input type="text" id="exp_join_date_<?php echo $i; ?>" name="exp_join_date[]" class="form-control date-input exp-join-date" placeholder="例: 2020/04/01" value="<?php echo htmlspecialchars($experience_data['exp_join_date'][$i] ?? ''); ?>" required pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2020/04/01)" />
                                </div>
                                <div class="col-md-6">
                                    <label for="exp_leave_date_<?php echo $i; ?>" class="form-label">退社日</label>
                                    <input type="text" id="exp_leave_date_<?php echo $i; ?>" name="exp_leave_date[]" class="form-control date-input exp-leave-date" placeholder="例: 2023/03/31" value="<?php echo htmlspecialchars($experience_data['exp_leave_date'][$i] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2023/03/31)" />
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger remove-btn">削除</button>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" id="add-experience-btn" class="btn btn-primary mb-3">職歴を追加</button>

                <h4 class="section-header">資格・スキル</h4>
                <div id="certification-section">
                    <?php
                    $cert_count = count($certifications_data['cert_type'] ?? []);
                    if ($cert_count === 0) {
                        $cert_count = 1;
                    }
                    for ($i = 0; $i < $cert_count; $i++):
                    ?>
                        <div class="dynamic-block certification-block" data-block-id="cert-<?php echo $i; ?>">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="cert_type_<?php echo $i; ?>" class="form-label">種類 *</label>
                                    <select id="cert_type_<?php echo $i; ?>" name="cert_type[]" class="form-select cert-type" required>
                                        <option value="">選択してください</option>
                                        <option value="Japanese" <?php echo ($certifications_data['cert_type'][$i] ?? '') === 'Japanese' ? 'selected' : ''; ?>>日本語</option>
                                        <option value="English" <?php echo ($certifications_data['cert_type'][$i] ?? '') === 'English' ? 'selected' : ''; ?>>英語</option>
                                        <option value="Other" <?php echo ($certifications_data['cert_type'][$i] ?? '') === 'Other' ? 'selected' : ''; ?>>その他</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="cert_name_<?php echo $i; ?>" class="form-label">資格名 *</label>
                                    <input type="text" id="cert_name_<?php echo $i; ?>" name="cert_name[]" class="form-control cert-name" placeholder="例: JLPT N1" value="<?php echo htmlspecialchars($certifications_data['cert_name'][$i] ?? ''); ?>" required />
                                    <label for="custom_skill_<?php echo $i; ?>" class="form-label mt-2 d-none">スキル</label>
                                    <input type="text" id="custom_skill_<?php echo $i; ?>" name="custom_skill[]" class="form-control custom-skill mt-2" style="display:none;" placeholder="あなたのスキルを入力 (例: プロジェクト管理)" value="<?php echo htmlspecialchars($certifications_data['custom_skill'][$i] ?? ''); ?>" />
                                </div>
                                <div class="col-md-4">
                                    <label for="cert_score_<?php echo $i; ?>" class="form-label">スコア/結果</label>
                                    <input type="text" id="cert_score_<?php echo $i; ?>" name="cert_score[]" class="form-control" placeholder="例: 180/180" value="<?php echo htmlspecialchars($certifications_data['cert_score'][$i] ?? ''); ?>" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cert_date_<?php echo $i; ?>" class="form-label">取得日</label>
                                <input type="text" id="cert_date_<?php echo $i; ?>" name="cert_date[]" class="form-control date-input" placeholder="例: 2023/12/01" value="<?php echo htmlspecialchars($certifications_data['cert_date'][$i] ?? ''); ?>" pattern="\d{4}/\d{2}/\d{2}" title="日付はYYYY/MM/DD形式で入力してください (例: 2023/12/01)" />
                            </div>
                            <button type="button" class="btn btn-danger remove-btn">削除</button>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="button" id="add-certification-btn" class="btn btn-primary mb-3">資格を追加</button>
                <!-- Navigation Buttons -->
                <div class="form-navigation mt-4">
                    <a href="recruitment.php?job_id=<?php echo $job_id; ?>&step=2" class="btn btn-secondary">前へ</a>
                    <button type="submit" class="btn btn-primary">次へ</button>
                </div>
            <?php elseif ($step === 4): ?>
                <!-- Step 4: Self PR & Motivation -->
                <h4 class="section-header">自己PR・志望動機</h4>
                <div class="mb-3">
                    <label for="self_intro" class="form-label">自己PR *</label>
                    <textarea id="self_intro" name="self_intro" class="form-control" rows="4" required placeholder="自己アピールを書いてください (例: 私は責任感が強いです)"><?php echo htmlspecialchars($motivation_data['self_intro'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="motivation" class="form-label">志望動機 *</label>
                    <textarea id="motivation" name="motivation" class="form-control" rows="4" required placeholder="志望動機を書いてください (例: 介護の仕事に興味があります)"><?php echo htmlspecialchars($motivation_data['motivation'] ?? ''); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="job_preference" class="form-label">本人希望欄（職種、給与、勤務地など）</label>
                    <textarea id="job_preference" name="job_preference" class="form-control" rows="3" placeholder="希望する職種、給与、勤務地などを記載してください (例: 介護職、月給20万円、東京)"><?php echo htmlspecialchars($motivation_data['job_preference'] ?? ''); ?></textarea>
                </div>
                <!-- Navigation Buttons -->
                <div class="form-navigation mt-4">
                    <a href="recruitment.php?job_id=<?php echo $job_id; ?>&step=3" class="btn btn-secondary">前へ</a>
                    <button type="submit" class="btn btn-primary">次へ</button>
                </div>
            <?php elseif ($step === 5): ?>
                <!-- Step 5: File Uploads -->
                <h4 class="section-header">ファイルアップロード</h4>
                <div class="mb-3">
                    <label for="photo" class="form-label">証明写真 *</label>
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*" required />
                </div>
                <div class="mb-3">
                    <label for="passport_file" class="form-label">パスポート</label>
                    <input type="file" id="passport_file" name="passport_file" class="form-control" accept="image/*,application/pdf" />
                </div>
                <div class="mb-3">
                    <label for="residence_card" class="form-label">在留カード</label>
                    <input type="file" id="residence_card" name="residence_card" class="form-control" accept="image/*,application/pdf" />
                </div>
                <div class="mb-3">
                    <label for="certificates" class="form-label">資格証明書</label>
                    <input type="file" id="certificates" name="certificates[]" class="form-control" accept="image/*,application/pdf" multiple />
                </div>
                <div class="mb-3">
                    <label for="skills_certificate" class="form-label">技能実習修了証明書</label>
                    <input type="file" id="skills_certificate" name="skills_certificate" class="form-control" accept="image/*,application/pdf" />
                </div>
                <!-- Hidden Job ID -->
                <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>" />
                <!-- Hidden fields for session data -->
                <?php
                // Flatten session data into hidden inputs
                foreach ($_SESSION['form_data']['personal'] as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $index => $val) {
                            echo "<input type='hidden' name='{$key}[]' value='" . htmlspecialchars($val) . "' />";
                        }
                    } else {
                        echo "<input type='hidden' name='{$key}' value='" . htmlspecialchars($value) . "' />";
                    }
                }
                foreach ($_SESSION['form_data']['passport'] as $key => $value) {
                    echo "<input type='hidden' name='{$key}' value='" . htmlspecialchars($value) . "' />";
                }
                foreach ($_SESSION['form_data']['education'] as $key => $value) {
                    foreach ($value as $index => $val) {
                        echo "<input type='hidden' name='{$key}[]' value='" . htmlspecialchars($val) . "' />";
                    }
                }
                foreach ($_SESSION['form_data']['experience'] as $key => $value) {
                    foreach ($value as $index => $val) {
                        echo "<input type='hidden' name='{$key}[]' value='" . htmlspecialchars($val) . "' />";
                    }
                }
                foreach ($_SESSION['form_data']['certifications'] as $key => $value) {
                    foreach ($value as $index => $val) {
                        echo "<input type='hidden' name='{$key}[]' value='" . htmlspecialchars($val) . "' />";
                    }
                }
                foreach ($_SESSION['form_data']['motivation'] as $key => $value) {
                    echo "<input type='hidden' name='{$key}' value='" . htmlspecialchars($value) . "' />";
                }
                ?>
                <!-- Navigation Buttons -->
                <div class="form-navigation mt-4">
                    <a href="recruitment.php?job_id=<?php echo $job_id; ?>&step=4" class="btn btn-secondary">前へ</a>
                    <button type="submit" formaction="php/submit_application.php" class="btn btn-success">送信</button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-row">
                <div class="footer-col">
                    <h3 class="footer-heading" data-i18n="footer.location_title">所在地</h3>
                    <div class="footer-link">
                        <a href="index.html" style="color: white;" data-i18n="footer.company_name">株式会社アイティーエフ</a>
                    </div>
                    <p class="footer-text" data-i18n="footer.location_details">
                        〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F<br>
                        06-6644-1800<br>
                        〒144-0052 東京都大田区蒲田5丁目21-13<br>
                        03-6424-7747<br>
                        info@it-future.jp
                    </p>
                </div>
                <div class="footer-col">
                    <h3 class="footer-heading" data-i18n="footer.services_title">サービス案内</h3>
                    <a href="index.html#solution_03" class="footer-link" data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
                    <a href="index.html#service-naiyo" class="footer-link" data-i18n="footer.service_introduction">サービス紹介</a>
                    <a href="index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
                    <a href="index.html#work-step" class="footer-link" data-i18n="footer.introduction_flow">紹介の流れ</a>
                    <a href="about.html#support-naiyou" class="footer-link" data-i18n="footer.support_content">サポート内容</a>
                </div>
                <div class="footer-col">
                    <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
                    <a href="greeting.html" class="footer-link" data-i18n="footer.president_greeting">代表者挨拶</a>
                    <a href="company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
                </div>
                <div class="footer-col">
                    <a href="privacy.html" class="footer-btn" data-i18n="footer.privacy_policy">プライバシーポリシー</a>
                </div>
            </div>
            <div class="footer-copyright">
                © ITF co. Ltd. ALL Rights Reserved
            </div>
        </div>
    </footer>

    <a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </a>

    <!-- Scripts -->
    <script src="https://unpkg.com/i18next@23.11.5/dist/umd/i18next.min.js"></script>
    <script src="/js/i18nextHttpBackend.min.js"></script>
    <script src="https://unpkg.com/i18next-browser-languagedetector@7.1.0/dist/umd/i18nextBrowserLanguageDetector.min.js"></script>
    <script src="/js/i18n.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/form.min.js"></script>
    <script src="/js/main.min.js"></script>
    <script defer src="js/recruit.js"></script>
    <script type="text/javascript" src="/js/front.min.js"></script>
    <script type="text/javascript" src="/js/wp-embed.min.js"></script>
</body>
</html>