document.addEventListener('DOMContentLoaded', function () {
  // Passport toggle
  const passportYes = document.getElementById('passportYes');
  const passportNo = document.getElementById('passportNo');
  const passportDetails = document.getElementById('passportDetails');

  function togglePassportDetails() {
    if (passportYes.checked) {
      passportDetails.style.display = 'block';
      document.getElementById('passport_number').required = true;
      document.getElementById('passport_expiry').required = true;
    } else {
      passportDetails.style.display = 'none';
      document.getElementById('passport_number').required = false;
      document.getElementById('passport_expiry').required = false;
    }
  }

  passportYes.addEventListener('change', togglePassportDetails);
  passportNo.addEventListener('change', togglePassportDetails);

  // Education section
  const educationSection = document.getElementById('education-section');
  document.getElementById('add-education-btn').addEventListener('click', function () {
    const educationBlock = document.createElement('div');
    educationBlock.classList.add('dynamic-block', 'education-block');
    educationBlock.innerHTML = `
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
    `;
    educationSection.appendChild(educationBlock);
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
    `;
    experienceSection.appendChild(experienceBlock);
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
    `;
    certificationSection.appendChild(certificationBlock);
  });

  // Remove blocks
  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('remove-btn')) {
      e.target.parentElement.remove();
    }
  });

  // Form validation
  document.querySelector('form').addEventListener('submit', function (e) {
    const educationBlocks = document.querySelectorAll('.education-block');
    const experienceBlocks = document.querySelectorAll('.experience-block');
    if (educationBlocks.length === 0) {
      e.preventDefault();
      alert('少なくとも1つの学歴を入力してください。');
    }
    if (experienceBlocks.length === 0) {
      e.preventDefault();
      alert('少なくとも1つの職歴を入力してください。');
    }
  });
});