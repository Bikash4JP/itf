document.addEventListener('DOMContentLoaded', function () {
  // Passport toggle
  const passportYes = document.getElementById('passportYes');
  const passportNo = document.getElementById('passportNo');
  const passportDetails = document.getElementById('passportDetails');
  const migrationHistory = document.getElementById('migrationHistory');
  const recentMigration = document.getElementById('recentMigration');
  const residencyDetails = document.getElementById('residencyDetails');

  function togglePassportDetails() {
    if (passportYes.checked) {
      passportDetails.classList.remove('hidden');
      migrationHistory.classList.remove('hidden');
      recentMigration.classList.remove('hidden');
      residencyDetails.classList.remove('hidden');
      document.getElementById('passport_number').required = true;
      document.getElementById('passport_expiry').required = true;
    } else {
      passportDetails.classList.add('hidden');
      migrationHistory.classList.add('hidden');
      recentMigration.classList.add('hidden');
      residencyDetails.classList.add('hidden');
      // Clear values and ensure not required
      document.getElementById('passport_number').value = '';
      document.getElementById('passport_expiry').value = '';
      document.getElementById('migration_history').value = '';
      document.getElementById('recent_migration_entry').value = '';
      document.getElementById('recent_migration_exit').value = '';
      document.getElementById('residency_status').value = '';
      document.getElementById('residency_expiry').value = '';
      document.getElementById('passport_number').required = false;
      document.getElementById('passport_expiry').required = false;
      document.getElementById('migration_history').required = false;
      document.getElementById('recent_migration_entry').required = false;
      document.getElementById('recent_migration_exit').required = false;
      document.getElementById('residency_status').required = false;
      document.getElementById('residency_expiry').required = false;
    }
  }

  passportYes.addEventListener('change', togglePassportDetails);
  passportNo.addEventListener('change', togglePassportDetails);

  // Furigana validation (only katakana allowed)
  const furiganaInput = document.getElementById('furigana');
  furiganaInput.addEventListener('input', function () {
    const katakanaRegex = /^[\u30A0-\u30FF\s]*$/;
    if (!katakanaRegex.test(this.value)) {
      this.setCustomValidity('カタカナで記入してください');
    } else {
      this.setCustomValidity('');
    }
  });

  // Full name handling based on nationality
  const nationalityInput = document.getElementById('nationality');
  const fullNameInput = document.getElementById('fullname');
  nationalityInput.addEventListener('change', function () {
    if (this.value.toLowerCase() !== 'japan' && this.value.toLowerCase() !== 'japanese') {
      fullNameInput.value = fullNameInput.value.toUpperCase();
    }
  });
  fullNameInput.addEventListener('input', function () {
    if (nationalityInput.value.toLowerCase() !== 'japan' && nationalityInput.value.toLowerCase() !== 'japanese') {
      this.value = this.value.toUpperCase();
    }
  });

  // Education section
  const educationSection = document.getElementById('education-section');
  document.getElementById('add-education-btn').addEventListener('click', function () {
    const educationBlock = document.createElement('div');
    educationBlock.classList.add('dynamic-block', 'education-block');
    educationBlock.innerHTML = `
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="institution_name" class="form-label">学校名 *</label>
          <input type="text" name="institution_name[]" class="form-control" placeholder="学校名、学習レベル (例: 東京大学、大学)" required />
        </div>
        <div class="col-md-6">
          <label for="institution_address" class="form-label">学校住所</label>
          <input type="text" name="institution_address[]" class="form-control" placeholder="例: 東京都文京区本郷7-3-1" />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-3">
          <label for="edu_join_date" class="form-label">入学日 *</label>
          <input type="text" name="edu_join_date[]" class="form-control date-input" placeholder="例: 2018/04/01" required />
        </div>
        <div class="col-md-3">
          <label for="edu_leave_date" class="form-label">卒業/終了日</label>
          <input type="text" name="edu_leave_date[]" class="form-control date-input edu-leave-date" placeholder="例: 2022/03/31" />
        </div>
        <div class="col-md-3">
          <label for="faculty" class="form-label">学部</label>
          <input type="text" name="faculty[]" class="form-control" placeholder="例: 文学部" />
        </div>
        <div class="col-md-3">
          <label for="major" class="form-label">専攻</label>
          <input type="text" name="major[]" class="form-control" placeholder="例: 日本文学" />
        </div>
      </div>
      <div class="mb-3">
        <label for="edu_status" class="form-label">状態 *</label>
        <select name="edu_status[]" class="form-select edu-status" required>
          <option value="">選択してください</option>
          <option value="Graduated">卒業</option>
          <option value="Ongoing">在学中</option>
          <option value="Dropped">中退</option>
        </select>
      </div>
      <button type="button" class="btn btn-danger remove-btn">削除</button>
    `;
    educationSection.appendChild(educationBlock);
  });

  // Education status toggle
  educationSection.addEventListener('change', function (e) {
    if (e.target.classList.contains('edu-status')) {
      const educationBlock = e.target.closest('.education-block');
      const leaveDateInput = educationBlock.querySelector('.edu-leave-date');
      if (e.target.value === 'Ongoing') {
        leaveDateInput.parentElement.classList.add('hidden');
        leaveDateInput.required = false;
        leaveDateInput.value = '';
      } else {
        leaveDateInput.parentElement.classList.remove('hidden');
        leaveDateInput.required = true;
      }
    }
  });

  // Experience section
  const experienceSection = document.getElementById('experience-section');
  document.getElementById('add-experience-btn').addEventListener('click', function () {
    const experienceBlock = document.createElement('div');
    experienceBlock.classList.add('dynamic-block', 'experience-block');
    experienceBlock.innerHTML = `
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="company_name" class="form-label">会社名 *</label>
          <input type="text" name="company_name[]" class="form-control company-name" placeholder="例: 株式会社ITF" required />
        </div>
        <div class="col-md-6">
          <label for="company_address" class="form-label">会社住所</label>
          <input type="text" name="company_address[]" class="form-control" placeholder="例: 東京都渋谷区1-2-3" />
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-4">
          <label for="business_type" class="form-label">業種 *</label>
          <input type="text" name="business_type[]" class="form-control business-type" placeholder="例: ITサービス" required />
        </div>
        <div class="col-md-4">
          <label for="job_role" class="form-label">職種 *</label>
          <input type="text" name="job_role[]" class="form-control job-role" placeholder="例: ソフトウェアエンジニア" required />
        </div>
        <div class="col-md-4">
          <label for="exp_status" class="form-label">状態 *</label>
          <select name="exp_status[]" class="form-select exp-status" required>
            <option value="">選択してください</option>
            <option value="Current">現在勤務中</option>
            <option value="Past">過去</option>
          </select>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="exp_join_date" class="form-label">入社日 *</label>
          <input type="text" name="exp_join_date[]" class="form-control date-input exp-join-date" placeholder="例: 2020/04/01" required />
        </div>
        <div class="col-md-6">
          <label for="exp_leave_date" class="form-label">退社日</label>
          <input type="text" name="exp_leave_date[]" class="form-control date-input exp-leave-date" placeholder="例: 2023/03/31" />
        </div>
      </div>
      <button type="button" class="btn btn-danger remove-btn">削除</button>
    `;
    experienceSection.appendChild(experienceBlock);
  });

  // Experience status toggle
  experienceSection.addEventListener('change', function (e) {
    if (e.target.classList.contains('exp-status')) {
      const experienceBlock = e.target.closest('.experience-block');
      const leaveDateInput = experienceBlock.querySelector('.exp-leave-date');
      if (e.target.value === 'Current') {
        leaveDateInput.parentElement.classList.add('hidden');
        leaveDateInput.required = false;
        leaveDateInput.value = '';
      } else {
        leaveDateInput.parentElement.classList.remove('hidden');
        leaveDateInput.required = true;
      }
    }
  });

  // Certification section
  const certificationSection = document.getElementById('certification-section');
  document.getElementById('add-certification-btn').addEventListener('click', function () {
    const certificationBlock = document.createElement('div');
    certificationBlock.classList.add('dynamic-block', 'certification-block');
    certificationBlock.innerHTML = `
      <div class="row mb-3">
        <div class="col-md-4">
          <label for="cert_type" class="form-label">種類 *</label>
          <select name="cert_type[]" class="form-select cert-type" required>
            <option value="">選択してください</option>
            <option value="Japanese">日本語</option>
            <option value="English">英語</option>
            <option value="Other">その他</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="cert_name" class="form-label">資格名 *</label>
          <input type="text" name="cert_name[]" class="form-control cert-name" placeholder="例: JLPT N1" required />
          <input type="text" name="custom_skill[]" class="form-control custom-skill mt-2" style="display:none;" placeholder="あなたのスキルを入力 (例: プロジェクト管理)" />
        </div>
        <div class="col-md-4">
          <label for="cert_score" class="form-label">スコア/結果</label>
          <input type="text" name="cert_score[]" class="form-control" placeholder="例: 180/180" />
        </div>
      </div>
      <div class="mb-3">
        <label for="cert_date" class="form-label">取得日</label>
        <input type="text" name="cert_date[]" class="form-control date-input" placeholder="例: 2023/12/01" />
      </div>
      <button type="button" class="btn btn-danger remove-btn">削除</button>
    `;
    certificationSection.appendChild(certificationBlock);
  });

  // Certification type toggle
  certificationSection.addEventListener('change', function (e) {
    if (e.target.classList.contains('cert-type')) {
      const certificationBlock = e.target.closest('.certification-block');
      const customSkillInput = certificationBlock.querySelector('.custom-skill');
      const certNameInput = certificationBlock.querySelector('.cert-name');
      if (e.target.value === 'Other') {
        customSkillInput.style.display = 'block';
        customSkillInput.required = true;
        certNameInput.required = false;
      } else {
        customSkillInput.style.display = 'none';
        customSkillInput.required = false;
        customSkillInput.value = '';
        certNameInput.required = true;
      }
    }
  });

  // Remove blocks
  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('remove-btn')) {
      const block = e.target.parentElement;
      if (block.classList.contains('education-block')) {
        block.remove();
      } else if (block.classList.contains('experience-block')) {
        block.remove();
        const remainingBlocks = document.querySelectorAll('.experience-block');
        if (remainingBlocks.length === 0) {
          document.querySelectorAll('.company-name, .business-type, .job-role, .exp-join-date, .exp-status').forEach(input => {
            input.required = false;
          });
        }
      } else if (block.classList.contains('certification-block')) {
        block.remove();
      }
    }
  });

  // Form validation
  document.querySelector('form').addEventListener('submit', function (e) {
    const educationBlocks = document.querySelectorAll('.education-block');
    if (educationBlocks.length === 0) {
      e.preventDefault();
      alert('少なくとも1つの学歴を入力してください。');
    }
  });

  // Ensure all date inputs are in yyyy/mm/dd format
  document.querySelectorAll('.date-input').forEach(input => {
    input.addEventListener('input', function () {
      let value = this.value.replace(/[^0-9/]/g, '');
      if (value.length === 4) value += '/';
      if (value.length === 7) value += '/';
      this.value = value;
    });

    input.addEventListener('blur', function () {
      const regex = /^\d{4}\/\d{2}\/\d{2}$/;
      if (this.value && !regex.test(this.value)) {
        alert('日付を「yyyy/mm/dd」形式で入力してください。例: 1990/01/01');
        this.value = '';
      }
    });
  });
});