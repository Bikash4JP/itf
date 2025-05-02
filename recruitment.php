<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ITF 新着採用応募フォーム</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .section-header { border-bottom: 2px solid #007BFF; margin-bottom: 20px; padding-bottom: 10px; }
    .dynamic-block { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
    .remove-btn { margin-top: 10px; }
  </style>
</head>
<body>
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
    <form action="php/submit_application.php" method="POST" enctype="multipart/form-data">
      <!-- Personal Details -->
      <h4 class="section-header">個人情報</h4>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="fullname" class="form-label">氏名 *</label>
          <input type="text" id="fullname" name="fullname" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label for="furigana" class="form-label">ふりがな *</label>
          <input type="text" id="furigana" name="furigana" class="form-control" required />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="roman_name" class="form-label">ローマ字 *</label>
          <input type="text" id="roman_name" name="roman_name" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label for="dob" class="form-label">生年月日 *</label>
          <input type="date" id="dob" name="dob" class="form-control" required />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="birth_place" class="form-label">出生地</label>
          <input type="text" id="birth_place" name="birth_place" class="form-control" />
        </div>
        <div class="col-md-3">
          <label for="height_cm" class="form-label">身長 (cm)</label>
          <input type="number" id="height_cm" name="height_cm" class="form-control" />
        </div>
        <div class="col-md-3">
          <label for="weight_kg" class="form-label">体重 (kg)</label>
          <input type="number" id="weight_kg" name="weight_kg" class="form-control" />
        </div>
      </div>
      <div class="mb-3">
        <label for="address" class="form-label">現住所 *</label>
        <textarea id="address" name="address" class="form-control" rows="2" required></textarea>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="phone" class="form-label">電話番号 *</label>
          <input type="tel" id="phone" name="phone" class="form-control" required />
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label">メールアドレス *</label>
          <input type="email" id="email" name="email" class="form-control" required />
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
          <input type="text" id="nationality" name="nationality" class="form-control" required />
        </div>
        <div class="col-md-4">
          <label for="religion" class="form-label">宗教</label>
          <input type="text" id="religion" name="religion" class="form-control" />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="marital_status" class="form-label">配偶者の有無 *</label>
          <select id="marital_status" name="marital_status" class="form-select" required>
            <option value="">選択してください</option>
            <option value="Single">無し</option>
            <option value="Married">有り</option>
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
            <input type="text" id="passport_number" name="passport_number" class="form-control" />
          </div>
          <div class="col-md-6">
            <label for="passport_expiry" class="form-label">有効期限 *</label>
            <input type="date" id="passport_expiry" name="passport_expiry" class="form-control" />
          </div>
        </div>
      </div>
      <div class="mb-3">
        <label for="migration_history" class="form-label">過去の出入国歴 (回数)</label>
        <input type="number" id="migration_history" name="migration_history" class="form-control" min="0" value="0" />
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="recent_migration_entry" class="form-label">直近の入国日</label>
          <input type="date" id="recent_migration_entry" name="recent_migration_entry" class="form-control" />
        </div>
        <div class="col-md-6">
          <label for="recent_migration_exit" class="form-label">直近の出国日</label>
          <input type="date" id="recent_migration_exit" name="recent_migration_exit" class="form-control" />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="residency_status" class="form-label">現在の在留資格</label>
          <input type="text" id="residency_status" name="residency_status" class="form-control" />
        </div>
        <div class="col-md-6">
          <label for="residency_expiry" class="form-label">在留期限</label>
          <input type="date" id="residency_expiry" name="residency_expiry" class="form-control" />
        </div>
      </div>

      <!-- Education -->
      <h4 class="section-header">学歴</h4>
      <div id="education-section">
        <div class="dynamic-block education-block">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="institution_name" class="form-label">学校名 *</label>
              <input type="text" name="institution_name[]" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label for="institution_address" class="form-label">学校住所</label>
              <input type="text" name="institution_address[]" class="form-control" />
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-3">
              <label for="edu_join_date" class="form-label">入学日 *</label>
              <input type="date" name="edu_join_date[]" class="form-control" required />
            </div>
            <div class="col-md-3">
              <label for="edu_leave_date" class="form-label">卒業/終了日</label>
              <input type="date" name="edu_leave_date[]" class="form-control" />
            </div>
            <div class="col-md-3">
              <label for="faculty" class="form-label">学部</label>
              <input type="text" name="faculty[]" class="form-control" />
            </div>
            <div class="col-md-3">
              <label for="major" class="form-label">専攻</label>
              <input type="text" name="major[]" class="form-control" />
            </div>
          </div>
          <div class="mb-3">
            <label for="edu_status" class="form-label">状態 *</label>
            <select name="edu_status[]" class="form-select" required>
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
        <div class="dynamic-block experience-block">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="company_name" class="form-label">会社名 *</label>
              <input type="text" name="company_name[]" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label for="company_address" class="form-label">会社住所</label>
              <input type="text" name="company_address[]" class="form-control" />
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label for="business_type" class="form-label">業種 *</label>
              <input type="text" name="business_type[]" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label for="job_role" class="form-label">職種 *</label>
              <input type="text" name="job_role[]" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label for="exp_status" class="form-label">状態 *</label>
              <select name="exp_status[]" class="form-select" required>
                <option value="">選択してください</option>
                <option value="Current">現在勤務中</option>
                <option value="Past">過去</option>
              </select>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="exp_join_date" class="form-label">入社日 *</label>
              <input type="date" name="exp_join_date[]" class="form-control" required />
            </div>
            <div class="col-md-6">
              <label for="exp_leave_date" class="form-label">退社日</label>
              <input type="date" name="exp_leave_date[]" class="form-control" />
            </div>
          </div>
          <button type="button" class="btn btn-danger remove-btn">削除</button>
        </div>
      </div>
      <button type="button" id="add-experience-btn" class="btn btn-primary mb-3">職歴を追加</button>

      <!-- Certifications -->
      <h4 class="section-header">資格・スキル</h4>
      <div id="certification-section">
        <div class="dynamic-block certification-block">
          <div class="row mb-3">
            <div class="col-md-4">
              <label for="cert_type" class="form-label">種類 *</label>
              <select name="cert_type[]" class="form-select" required>
                <option value="">選択してください</option>
                <option value="Japanese">日本語</option>
                <option value="English">英語</option>
                <option value="Other">その他</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="cert_name" class="form-label">資格名 *</label>
              <input type="text" name="cert_name[]" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label for="cert_score" class="form-label">スコア/結果</label>
              <input type="text" name="cert_score[]" class="form-control" />
            </div>
          </div>
          <div class="mb-3">
            <label for="cert_date" class="form-label">取得日</label>
            <input type="date" name="cert_date[]" class="form-control" />
          </div>
          <button type="button" class="btn btn-danger remove-btn">削除</button>
        </div>
      </div>
      <button type="button" id="add-certification-btn" class="btn btn-primary mb-3">資格を追加</button>

      <!-- Self-PR and Motivation -->
      <h4 class="section-header">自己PR・志望動機</h4>
      <div class="mb-3">
        <label for="self_intro" class="form-label">自己PR *</label>
        <textarea id="self_intro" name="self_intro" class="form-control" rows="4" required placeholder="自己アピールを書いてください"></textarea>
      </div>
      <div class="mb-3">
        <label for="motivation" class="form-label">志望動機 *</label>
        <textarea id="motivation" name="motivation" class="form-control" rows="4" required placeholder="志望動機を書いてください"></textarea>
      </div>
      <div class="mb-3">
        <label for="job_preference" class="form-label">本人希望欄（職種、給与、勤務地など）</label>
        <textarea id="job_preference" name="job_preference" class="form-control" rows="3" placeholder="希望する職種、給与、勤務地などを記載してください"></textarea>
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
      <input type="hidden" name="job_id" value="<?php echo isset($_GET['job_id']) ? htmlspecialchars($_GET['job_id']) : ''; ?>" />

      <!-- Submit Button -->
      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary btn-lg">送信</button>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/recruit.js"></script>
</body>
</html>