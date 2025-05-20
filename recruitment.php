<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ITF 新着採用応募フォーム</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/recruit.css" />
</head>
<body>
  <?php
  // Validate job_id from URL
  if (!isset($_GET['job_id']) || !filter_var($_GET['job_id'], FILTER_VALIDATE_INT) || $_GET['job_id'] <= 0) {
      die("Error: Please access this form with a valid job_id (e.g., ?job_id=13).");
  }
  $job_id = htmlspecialchars($_GET['job_id']);
  ?>

  <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
    <div class="container">
      <a class="navbar-brand" href="/">ITF</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/">ホーム</a></li>
          <li class="nav-item"><a class="nav-link active" href="recruitment.php">新着採用</a></li>
          <li class="nav-item"><a class="nav-link" href="/about">会社概要</a></li>
          <li class="nav-item"><a class="nav-link" href="/contact">お問い合わせ</a></li>
          <li class="nav-item"><a class="nav-link" href="/staff-login">スタッフログイン</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <h2 class="mb-4">ITF 新着採用応募フォーム</h2>
    <form action="php/submit_application.php" method="POST" enctype="multipart/form-data" id="recruitment-form">
      <!-- Personal Details -->
      <h4 class="section-header">個人情報</h4>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="fullname" class="form-label">氏名 *</label>
          <input type="text" id="fullname" name="fullname" class="form-control" placeholder="氏名 (例: 田中 太郎)" required />
        </div>
        <div class="col-md-6">
          <label for="furigana" class="form-label">ふりがな *</label>
          <input type="text" id="furigana" name="furigana" class="form-control" placeholder="ふりがな (カタカナで記入してください、例: タナカ タロウ)" required />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="roman_name" class="form-label">ローマ字 *</label>
          <input type="text" id="roman_name" name="roman_name" class="form-control" placeholder="ローマ字 (例: TANAKA TAROU)" required />
        </div>
        <div class="col-md-6">
          <label for="dob" class="form-label">生年月日 *</label>
          <input type="text" id="dob" name="dob" class="form-control date-input" placeholder="生年月日 (例: 1990/01/01)" required />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="birth_place" class="form-label">出生地 *</label>
          <input type="text" id="birth_place" name="birth_place" class="form-control" placeholder="出生地 (日本国外の場合は国名を記入してください、例: インドネシア)" required />
        </div>
        <div class="col-md-3">
          <label for="height_cm" class="form-label">身長 (cm)</label>
          <input type="text" id="height_cm" name="height_cm" class="form-control number-input" placeholder="例: 170" />
        </div>
        <div class="col-md-3">
          <label for="weight_kg" class="form-label">体重 (kg)</label>
          <input type="text" id="weight_kg" name="weight_kg" class="form-control number-input" placeholder="例: 70" />
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">現住所 *</label>
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="postal_code" class="form-label">郵便番号 *</label>
            <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="例: 123-4567" required />
          </div>
          <div class="col-md-2">
            <button type="button" id="search-postal-code-btn" class="btn btn-secondary mt-4">郵便番号から検索</button>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-4">
            <label for="prefecture" class="form-label">都道府県 *</label>
            <input type="text" id="prefecture" name="prefecture" class="form-control" placeholder="例: 東京都" required />
          </div>
          <div class="col-md-4">
            <label for="city_ward" class="form-label">市区町村 *</label>
            <input type="text" id="city_ward" name="city_ward" class="form-control" placeholder="例: 千代田区" required />
          </div>
          <div class="col-md-4">
            <label for="street_address" class="form-label">ストリート番号 *</label>
            <input type="text" id="street_address" name="street_address" class="form-control" placeholder="例: 3-7-19" required />
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="home_details" class="form-label">ホーム詳細</label>
            <input type="text" id="home_details" name="home_details" class="form-control" placeholder="例: 山田ビル 301" />
          </div>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="phone" class="form-label">電話番号 *</label>
          <input type="tel" id="phone" name="phone" class="form-control" placeholder="例: 090-1234-5678" required />
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label">メールアドレス *</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="例: example@itf.co.jp" required />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <label for="gender" class="form-label">性別 *</label>
          <select id="gender" name="gender" class="form-select" required>
            <option value="">選択してください</option>
            <option value="Male">男性</option>
            <option value="Female">女性</option>
            <option value="Other">その他</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="nationality" class="form-label">国籍 *</label>
          <select id="nationality" name="nationality" class="form-select" required onchange="toggleNationalityInput()">
            <option value="">選択してください</option>
            <option value="インドネシア国籍">インドネシア国籍</option>
            <option value="ベトナム国籍">ベトナム国籍</option>
            <option value="中国国籍">中国国籍</option>
            <option value="ネパール国籍">ネパール国籍</option>
            <option value="バングラデシュ国籍">バングラデシュ国籍</option>
            <option value="ミャンマー国籍">ミャンマー国籍</option>
            <option value="ペルー国籍">ペルー国籍</option>
            <option value="韓国国籍">韓国国籍</option>
            <option value="その他">その他</option>
          </select>
          <input type="text" id="custom_nationality" name="custom_nationality" class="form-control mt-2" style="display:none;" placeholder="国籍を入力してください (例: フィリピン)" />
        </div>
        <div class="col-md-4">
          <label for="religion" class="form-label">宗教</label>
          <select id="religion" name="religion" class="form-select">
            <option value="">選択してください</option>
            <option value="イスラム教">イスラム教</option>
            <option value="キリスト教">キリスト教</option>
            <option value="ヒンドゥー教">ヒンドゥー教</option>
            <option value="仏教">仏教</option>
            <option value="無宗教">無宗教</option>
          </select>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="marital_status" class="form-label">配偶者の有無 *</label>
          <select id="marital_status" name="marital_status" class="form-select" required>
            <option value="">選択してください</option>
            <option value="有り（子供あり)">有り（子供あり)</option>
            <option value="有り（子供なし)">有り（子供なし)</option>
            <option value="無し">無し</option>
          </select>
        </div>
      </div>

      <!-- Passport Details -->
      <h4 class="section-header">パスポート情報</h4>
      <div class="mb-3">
        <label class="form-label">パスポートはお持ちですか？ *</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="passport_have" id="passportYes" value="Yes" required>
          <label class="form-check-label" for="passportYes">はい</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="passport_have" id="passportNo" value="No">
          <label class="form-check-label" for="passportNo">いいえ</label>
        </div>
      </div>
      <div id="passportDetails" style="display:none;">
        <div class="row mb-3">
          <div class="col-md-6">
            <label for="passport_number" class="form-label">パスポート番号 *</label>
            <input type="text" id="passport_number" name="passport_number" class="form-control" placeholder="例: AB1234567" />
          </div>
          <div class="col-md-6">
            <label for="passport_expiry" class="form-label">有効期限 *</label>
            <input type="text" id="passport_expiry" name="passport_expiry" class="form-control date-input" placeholder="例: 2030/12/31" />
          </div>
        </div>
      </div>
      <div class="mb-3" id="migrationHistory">
        <label for="migration_history" class="form-label">過去の出入国歴 (回数)</label>
        <input type="number" id="migration_history" name="migration_history" class="form-control" min="0" value="0" placeholder="例: 5" />
      </div>
      <div class="row mb-3" id="recentMigration">
        <div class="col-md-6">
          <label for="recent_migration_entry" class="form-label">直近の入国日</label>
          <input type="text" id="recent_migration_entry" name="recent_migration_entry" class="form-control date-input" placeholder="例: 2023/05/01" />
        </div>
        <div class="col-md-6">
          <label for="recent_migration_exit" class="form-label">直近の出国日</label>
          <input type="text" id="recent_migration_exit" name="recent_migration_exit" class="form-control date-input" placeholder="例: 2023/06/01" />
        </div>
      </div>
      <div class="row mb-3" id="residencyDetails">
        <div class="col-md-6">
          <label for="residency_status" class="form-label">現在の在留資格</label>
          <input type="text" id="residency_status" name="residency_status" class="form-control" placeholder="例: 特定技能" />
        </div>
        <div class="col-md-6">
          <label for="residency_expiry" class="form-label">在留期限</label>
          <input type="text" id="residency_expiry" name="residency_expiry" class="form-control date-input" placeholder="例: 2025/12/31" />
        </div>
      </div>

      <!-- Education -->
      <h4 class="section-header">学歴</h4>
      <div id="education-section">
        <div class="dynamic-block education-block" data-block-id="edu-0">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="institution_name_0" class="form-label">学校名 *</label>
              <input type="text" id="institution_name_0" name="institution_name[]" class="form-control" placeholder="学校名、学習レベル (例: 東京大学、大学)" required />
            </div>
            <div class="col-md-6">
              <label for="institution_address_0" class="form-label">学校住所</label>
              <input type="text" id="institution_address_0" name="institution_address[]" class="form-control" placeholder="例: 東京都文京区本郷7-3-1" />
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="edu_join_date_0" class="form-label">入学日 *</label>
              <input type="text" id="edu_join_date_0" name="edu_join_date[]" class="form-control date-input" placeholder="例: 2018/04/01" required />
            </div>
            <div class="col-md-3">
              <label for="edu_leave_date_0" class="form-label">卒業/終了日</label>
              <input type="text" id="edu_leave_date_0" name="edu_leave_date[]" class="form-control date-input edu-leave-date" placeholder="例: 2022/03/31" />
            </div>
            <div class="col-md-3">
              <label for="faculty_0" class="form-label">学部</label>
              <input type="text" id="faculty_0" name="faculty[]" class="form-control" placeholder="例: 文学部" />
            </div>
            <div class="col-md-3">
              <label for="major_0" class="form-label">専攻</label>
              <input type="text" id="major_0" name="major[]" class="form-control" placeholder="例: 日本文学" />
            </div>
          </div>
          <div class="mb-3">
            <label for="edu_status_0" class="form-label">状態 *</label>
            <select id="edu_status_0" name="edu_status[]" class="form-select edu-status" required>
              <option value="">選択してください</option>
              <option value="Graduated">卒業</option>
              <option value="Ongoing">在学中</option>
              <option value="Dropped">中退</option>
            </select>
          </div>
          <button type="button" class="btn btn-danger remove-btn">削除</button>
        </div>
      </div>
      <button type="button" id="add-education-btn" class="btn btn-primary mb-3">学歴を追加</button>

      <!-- Work Experience -->
      <h4 class="section-header">職歴</h4>
      <div id="experience-section">
        <div class="dynamic-block experience-block" data-block-id="exp-0">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="company_name_0" class="form-label">会社名 *</label>
              <input type="text" id="company_name_0" name="company_name[]" class="form-control company-name" placeholder="例: 株式会社ITF" required />
            </div>
            <div class="col-md-6">
              <label for="company_address_0" class="form-label">会社住所</label>
              <input type="text" id="company_address_0" name="company_address[]" class="form-control" placeholder="例: 東京都渋谷区1-2-3" />
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label for="business_type_0" class="form-label">業種 *</label>
              <input type="text" id="business_type_0" name="business_type[]" class="form-control business-type" placeholder="例: ITサービス" required />
            </div>
            <div class="col-md-4">
              <label for="job_role_0" class="form-label">職種 *</label>
              <input type="text" id="job_role_0" name="job_role[]" class="form-control job-role" placeholder="例: ソフトウェアエンジニア" required />
            </div>
            <div class="col-md-4">
              <label for="exp_status_0" class="form-label">状態 *</label>
              <select id="exp_status_0" name="exp_status[]" class="form-select exp-status" required>
                <option value="">選択してください</option>
                <option value="Current">現在勤務中</option>
                <option value="Past">過去</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="exp_join_date_0" class="form-label">入社日 *</label>
              <input type="text" id="exp_join_date_0" name="exp_join_date[]" class="form-control date-input exp-join-date" placeholder="例: 2020/04/01" required />
            </div>
            <div class="col-md-6">
              <label for="exp_leave_date_0" class="form-label">退社日</label>
              <input type="text" id="exp_leave_date_0" name="exp_leave_date[]" class="form-control date-input exp-leave-date" placeholder="例: 2023/03/31" />
            </div>
          </div>
          <button type="button" class="btn btn-danger remove-btn">削除</button>
        </div>
      </div>
      <button type="button" id="add-experience-btn" class="btn btn-primary mb-3">職歴を追加</button>

      <!-- Certifications -->
      <h4 class="section-header">資格・スキル</h4>
      <div id="certification-section">
        <div class="dynamic-block certification-block" data-block-id="cert-0">
          <div class="row mb-3">
            <div class="col-md-4">
              <label for="cert_type_0" class="form-label">種類 *</label>
              <select id="cert_type_0" name="cert_type[]" class="form-select cert-type" required>
                <option value="">選択してください</option>
                <option value="Japanese">日本語</option>
                <option value="English">英語</option>
                <option value="Other">その他</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="cert_name_0" class="form-label">資格名 *</label>
              <input type="text" id="cert_name_0" name="cert_name[]" class="form-control cert-name" placeholder="例: JLPT N1" required />
              <label for="custom_skill_0" class="form-label mt-2 d-none">スキル</label>
              <input type="text" id="custom_skill_0" name="custom_skill[]" class="form-control custom-skill mt-2" style="display:none;" placeholder="あなたのスキルを入力 (例: プロジェクト管理)" />
            </div>
            <div class="col-md-4">
              <label for="cert_score_0" class="form-label">スコア/結果</label>
              <input type="text" id="cert_score_0" name="cert_score[]" class="form-control" placeholder="例: 180/180" />
            </div>
          </div>
          <div class="mb-3">
            <label for="cert_date_0" class="form-label">取得日</label>
            <input type="text" id="cert_date_0" name="cert_date[]" class="form-control date-input" placeholder="例: 2023/12/01" />
          </div>
          <button type="button" class="btn btn-danger remove-btn">削除</button>
        </div>
      </div>
      <button type="button" id="add-certification-btn" class="btn btn-primary mb-3">資格を追加</button>

      <!-- Self-PR and Motivation -->
      <h4 class="section-header">自己PR・志望動機</h4>
      <div class="mb-3">
        <label for="self_intro" class="form-label">自己PR *</label>
        <textarea id="self_intro" name="self_intro" class="form-control" rows="4" required placeholder="自己アピールを書いてください (例: 私は責任感が強いです)"></textarea>
      </div>
      <div class="mb-3">
        <label for="motivation" class="form-label">志望動機 *</label>
        <textarea id="motivation" name="motivation" class="form-control" rows="4" required placeholder="志望動機を書いてください (例: 介護の仕事に興味があります)"></textarea>
      </div>
      <div class="mb-3">
        <label for="job_preference" class="form-label">本人希望欄（職種、給与、勤務地など）</label>
        <textarea id="job_preference" name="job_preference" class="form-control" rows="3" placeholder="希望する職種、給与、勤務地などを記載してください (例: 介護職、月給20万円、東京)"></textarea>
      </div>

      <!-- File Uploads -->
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
      <input type="hidden" name="job_id" value="<?php echo $job_id; ?>" />

      <!-- Submit Button -->
      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary btn-lg">送信</button>
      </div>
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
                        <a href="index.html#solution_03" class="footer-link"
                            data-i18n="footer.services_for_companies">人財をお探しの企業様</a>
                        
                        <a href="index.html#service-naiyo" class="footer-link"
                            data-i18n="footer.service_introduction">サービス紹介</a>
                        <a href="index.html#merit" class="footer-link" data-i18n="footer.benefits">メリット</a>
                        <a href="index.html#work-step" class="footer-link"
                            data-i18n="footer.introduction_flow">紹介の流れ</a>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-heading" data-i18n="footer.company_info_title">会社案内</h3>
                        <a href="greeting.html" class="footer-link"
                            data-i18n="footer.president_greeting">代表者挨拶</a>
                        <a href="company_info.html" class="footer-link" data-i18n="footer.company_info">会社概要</a>
                        <a href="about.html#support-naiyou" class="footer-link"
                            data-i18n="footer.support_content">サポート内容</a>
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
    </div>
    <!-- Add this just before the closing </body> tag -->
    <a href="#" id="back-to-top" class="back-to-top" title="Back to Top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"></polyline>
        </svg>
    </a>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/recruit.js"></script>
</body>
</html>