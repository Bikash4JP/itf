document.addEventListener('DOMContentLoaded', function () {
    // Toggle nationality input
    function toggleNationalityInput() {
        const nationalitySelect = document.getElementById('nationality');
        const customNationalityInput = document.getElementById('custom_nationality');
        if (nationalitySelect.value === 'その他') {
            customNationalityInput.style.display = 'block';
            customNationalityInput.required = true;
        } else {
            customNationalityInput.style.display = 'none';
            customNationalityInput.required = false;
            customNationalityInput.value = '';
        }
    }

    const nationalitySelect = document.getElementById('nationality');
    if (nationalitySelect) {
        nationalitySelect.addEventListener('change', toggleNationalityInput);
        toggleNationalityInput(); // Initial check
    }

    // Passport toggle
    const passportYes = document.getElementById('passportYes');
    const passportNo = document.getElementById('passportNo');
    const passportDetails = document.getElementById('passportDetails');
    const migrationHistory = document.getElementById('migrationHistory');
    const recentMigration = document.getElementById('recentMigration');
    const residencyDetails = document.getElementById('residencyDetails');

    function togglePassportDetails() {
        if (passportYes && passportYes.checked) {
            passportDetails.style.display = 'block';
            migrationHistory.style.display = 'block';
            recentMigration.style.display = 'block';
            residencyDetails.style.display = 'block';
            document.getElementById('passport_number').required = true;
            document.getElementById('passport_expiry').required = true;
        } else {
            passportDetails.style.display = 'none';
            migrationHistory.style.display = 'none';
            recentMigration.style.display = 'none';
            residencyDetails.style.display = 'none';
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

    if (passportYes && passportNo) {
        passportYes.addEventListener('change', togglePassportDetails);
        passportNo.addEventListener('change', togglePassportDetails);
        togglePassportDetails(); // Initial check
    }

    // Furigana validation (only katakana allowed)
    const furiganaInput = document.getElementById('furigana');
    if (furiganaInput) {
        furiganaInput.addEventListener('input', function () {
            const katakanaRegex = /^[\u30A0-\u30FF\s]*$/;
            if (!katakanaRegex.test(this.value)) {
                this.setCustomValidity('カタカナで記入してください');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Full name handling based on nationality
    const fullNameInput = document.getElementById('fullname');
    if (nationalitySelect && fullNameInput) {
        nationalitySelect.addEventListener('change', function () {
            if (this.value.toLowerCase() !== 'japan' && this.value.toLowerCase() !== 'japanese') {
                fullNameInput.value = fullNameInput.value.toUpperCase();
            }
        });

        fullNameInput.addEventListener('input', function () {
            if (nationalitySelect.value.toLowerCase() !== 'japan' && nationalitySelect.value.toLowerCase() !== 'japanese') {
                this.value = this.value.toUpperCase();
            }
        });
    }

    // Counter for unique IDs
    let eduCounter = document.querySelectorAll('.education-block').length || 1;
    let expCounter = document.querySelectorAll('.experience-block').length || 1;
    let certCounter = document.querySelectorAll('.certification-block').length || 1;

    // Education section
    const educationSection = document.getElementById('education-section');
    const addEducationBtn = document.getElementById('add-education-btn');
    if (educationSection && addEducationBtn) {
        addEducationBtn.addEventListener('click', function () {
            const educationBlock = document.createElement('div');
            educationBlock.classList.add('dynamic-block', 'education-block');
            educationBlock.setAttribute('data-block-id', `edu-${eduCounter}`);
            educationBlock.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="institution_name_${eduCounter}" class="form-label">学校名 *</label>
                        <input type="text" id="institution_name_${eduCounter}" name="institution_name[]" class="form-control" placeholder="学校名、学習レベル (例: 東京大学、大学)" required />
                    </div>
                    <div class="col-md-6">
                        <label for="institution_address_${eduCounter}" class="form-label">学校住所</label>
                        <input type="text" id="institution_address_${eduCounter}" name="institution_address[]" class="form-control" placeholder="例: 東京都文京区本郷7-3-1" />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="edu_join_date_${eduCounter}" class="form-label">入学日 *</label>
                        <input type="text" id="edu_join_date_${eduCounter}" name="edu_join_date[]" class="form-control date-input" placeholder="例: 2018/04/01" required />
                    </div>
                    <div class="col-md-3">
                        <label for="edu_leave_date_${eduCounter}" class="form-label">卒業/終了日</label>
                        <input type="text" id="edu_leave_date_${eduCounter}" name="edu_leave_date[]" class="form-control date-input edu-leave-date" placeholder="例: 2022/03/31" />
                    </div>
                    <div class="col-md-3">
                        <label for="faculty_${eduCounter}" class="form-label">学部</label>
                        <input type="text" id="faculty_${eduCounter}" name="faculty[]" class="form-control" placeholder="例: 文学部" />
                    </div>
                    <div class="col-md-3">
                        <label for="major_${eduCounter}" class="form-label">専攻</label>
                        <input type="text" id="major_${eduCounter}" name="major[]" class="form-control" placeholder="例: 日本文学" />
                    </div>
                </div>
                <div class="mb-3">
                    <label for="edu_status_${eduCounter}" class="form-label">状態 *</label>
                    <select id="edu_status_${eduCounter}" name="edu_status[]" class="form-select edu-status" required>
                        <option value="">選択してください</option>
                        <option value="Graduated">卒業</option>
                        <option value="Ongoing">在学中</option>
                        <option value="Dropped">中退</option>
                    </select>
                </div>
                <button type="button" class="btn btn-danger remove-btn">削除</button>
            `;
            educationSection.appendChild(educationBlock);
            addAutoSlashToDateInputs(educationBlock.querySelectorAll('.date-input'));
            eduCounter++;
        });

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
    }

    // Experience section
    const experienceSection = document.getElementById('experience-section');
    const addExperienceBtn = document.getElementById('add-experience-btn');
    if (experienceSection && addExperienceBtn) {
        addExperienceBtn.addEventListener('click', function () {
            const experienceBlock = document.createElement('div');
            experienceBlock.classList.add('dynamic-block', 'experience-block');
            experienceBlock.setAttribute('data-block-id', `exp-${expCounter}`);
            experienceBlock.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="company_name_${expCounter}" class="form-label">会社名 *</label>
                        <input type="text" id="company_name_${expCounter}" name="company_name[]" class="form-control company-name" placeholder="例: 株式会社ITF" required />
                    </div>
                    <div class="col-md-6">
                        <label for="company_address_${expCounter}" class="form-label">会社住所</label>
                        <input type="text" id="company_address_${expCounter}" name="company_address[]" class="form-control" placeholder="例: 東京都渋谷区1-2-3" />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="business_type_${expCounter}" class="form-label">業種 *</label>
                        <input type="text" id="business_type_${expCounter}" name="business_type[]" class="form-control business-type" placeholder="例: ITサービス" required />
                    </div>
                    <div class="col-md-4">
                        <label for="job_role_${expCounter}" class="form-label">職種 *</label>
                        <input type="text" id="job_role_${expCounter}" name="job_role[]" class="form-control job-role" placeholder="例: ソフトウェアエンジニア" required />
                    </div>
                    <div class="col-md-4">
                        <label for="exp_status_${expCounter}" class="form-label">状態 *</label>
                        <select id="exp_status_${expCounter}" name="exp_status[]" class="form-select exp-status" required>
                            <option value="">選択してください</option>
                            <option value="Current">現在勤務中</option>
                            <option value="Past">過去</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="exp_join_date_${expCounter}" class="form-label">入社日 *</label>
                        <input type="text" id="exp_join_date_${expCounter}" name="exp_join_date[]" class="form-control date-input exp-join-date" placeholder="例: 2020/04/01" required />
                    </div>
                    <div class="col-md-6">
                        <label for="exp_leave_date_${expCounter}" class="form-label">退社日</label>
                        <input type="text" id="exp_leave_date_${expCounter}" name="exp_leave_date[]" class="form-control date-input exp-leave-date" placeholder="例: 2023/03/31" />
                    </div>
                </div>
                <button type="button" class="btn btn-danger remove-btn">削除</button>
            `;
            experienceSection.appendChild(experienceBlock);
            addAutoSlashToDateInputs(experienceBlock.querySelectorAll('.date-input'));
            expCounter++;
        });

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
    }

    // Certification section
    const certificationSection = document.getElementById('certification-section');
    const addCertificationBtn = document.getElementById('add-certification-btn');
    if (certificationSection && addCertificationBtn) {
        addCertificationBtn.addEventListener('click', function () {
            const certificationBlock = document.createElement('div');
            certificationBlock.classList.add('dynamic-block', 'certification-block');
            certificationBlock.setAttribute('data-block-id', `cert-${certCounter}`);
            certificationBlock.innerHTML = `
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="cert_type_${certCounter}" class="form-label">種類 *</label>
                        <select id="cert_type_${certCounter}" name="cert_type[]" class="form-select cert-type" required>
                            <option value="">選択してください</option>
                            <option value="Japanese">日本語</option>
                            <option value="English">英語</option>
                            <option value="Other">その他</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="cert_name_${certCounter}" class="form-label">資格名 *</label>
                        <input type="text" id="cert_name_${certCounter}" name="cert_name[]" class="form-control cert-name" placeholder="例: JLPT N1" required />
                        <label for="custom_skill_${certCounter}" class="form-label mt-2 d-none">スキル</label>
                        <input type="text" id="custom_skill_${certCounter}" name="custom_skill[]" class="form-control custom-skill mt-2" style="display:none;" placeholder="あなたのスキルを入力 (例: プロジェクト管理)" />
                    </div>
                    <div class="col-md-4">
                        <label for="cert_score_${certCounter}" class="form-label">スコア/結果</label>
                        <input type="text" id="cert_score_${certCounter}" name="cert_score[]" class="form-control" placeholder="例: 180/180" />
                    </div>
                </div>
                <div class="mb-3">
                    <label for="cert_date_${certCounter}" class="form-label">取得日</label>
                    <input type="text" id="cert_date_${certCounter}" name="cert_date[]" class="form-control date-input" placeholder="例: 2023/12/01" />
                </div>
                <button type="button" class="btn btn-danger remove-btn">削除</button>
            `;
            certificationSection.appendChild(certificationBlock);
            addAutoSlashToDateInputs(certificationBlock.querySelectorAll('.date-input'));
            certCounter++;
        });

        certificationSection.addEventListener('change', function (e) {
            if (e.target.classList.contains('cert-type')) {
                const certificationBlock = e.target.closest('.certification-block');
                const customSkillInput = certificationBlock.querySelector('.custom-skill');
                const customSkillLabel = certificationBlock.querySelector('label[for^="custom_skill_"]');
                const certNameInput = certificationBlock.querySelector('.cert-name');
                if (e.target.value === 'Other') {
                    customSkillInput.style.display = 'block';
                    customSkillLabel.classList.remove('d-none');
                    customSkillInput.required = true;
                    certNameInput.required = false;
                } else {
                    customSkillInput.style.display = 'none';
                    customSkillLabel.classList.add('d-none');
                    customSkillInput.required = false;
                    customSkillInput.value = '';
                    certNameInput.required = true;
                }
            }
        });
    }

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

    // Form validation with scroll to missing field
    document.getElementById('recruitment-form').addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent form submission to handle validation client-side
        console.log('Form submit event triggered');

        const currentStep = parseInt(document.getElementById('current-step').value);
        console.log('Current Step:', currentStep);

        // Validate education only in Step 2
        if (currentStep === 2) {
            const educationBlocks = document.querySelectorAll('.education-block');
            if (educationBlocks.length === 0) {
                alert('少なくとも1つの学歴を入力してください。');
                educationSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return;
            }
        }

        // Simplified validation: Only check fields for the current step
        let allFieldsValid = true;
        let firstMissingField = null;
        let missingFieldLabel = '';

        if (currentStep === 5) {
            // Step 5: File uploads (only check visible and relevant fields)
            const requiredFields = ['photo']; // List of required field IDs for Step 5
            console.log('Checking Step 5 required fields:', requiredFields);

            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field) {
                    console.log(`Field ${fieldId} not found`);
                    return;
                }

                const isVisible = field.offsetParent !== null && field.style.display !== 'none';
                let isValid = false;
                const label = document.querySelector(`label[for="${field.id}"]`);
                const labelText = label ? label.textContent : field.name;

                if (isVisible) {
                    if (field.type === 'file') {
                        isValid = field.files.length > 0;
                        console.log(`File field ${field.id}: ${field.files.length} files, Visible: ${isVisible}, Required: ${field.required}`);
                    } else {
                        const value = field.value.trim();
                        isValid = value !== '' && !(field.type === 'select-one' && value === '');
                        console.log(`Field ${field.id}: ${value}, Visible: ${isVisible}, Required: ${field.required}`);
                    }

                    if (field.required && !isValid) {
                        if (!firstMissingField) {
                            firstMissingField = field;
                            missingFieldLabel = labelText;
                        }
                        allFieldsValid = false;
                    }
                }
            });
        } else {
            // For other steps, validate visible required fields
            const requiredFields = document.querySelectorAll(`#recruitment-form [required]:not([style*="display: none"])`);
            console.log('Checking required fields for step', currentStep);

            requiredFields.forEach(field => {
                const isVisible = field.offsetParent !== null && field.style.display !== 'none';
                let isValid = false;
                const label = document.querySelector(`label[for="${field.id}"]`);
                const labelText = label ? label.textContent : field.name;

                if (isVisible) {
                    if (field.type === 'file') {
                        isValid = field.files.length > 0;
                        console.log(`File field ${field.id}: ${field.files.length} files, Visible: ${isVisible}, Required: ${field.required}`);
                    } else {
                        const value = field.value.trim();
                        isValid = value !== '' && !(field.type === 'select-one' && value === '');
                        console.log(`Field ${field.id}: ${value}, Visible: ${isVisible}, Required: ${field.required}`);
                    }

                    if (field.required && !isValid) {
                        if (!firstMissingField) {
                            firstMissingField = field;
                            missingFieldLabel = labelText;
                        }
                        allFieldsValid = false;
                    }
                }
            });
        }

        if (!allFieldsValid) {
            alert(`"${missingFieldLabel}" が入力されていません。`);
            firstMissingField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstMissingField.focus();
            console.log('Validation failed');
            return;
        }

        // Concatenate address fields before submission (if in Step 1)
        const prefecture = document.getElementById('prefecture');
        if (prefecture) {
            const cityWard = document.getElementById('city_ward').value.trim();
            const streetAddress = document.getElementById('street_address').value.trim();
            const homeDetails = document.getElementById('home_details').value.trim();
            const fullAddress = `${prefecture.value.trim()}${cityWard}${streetAddress}${homeDetails}`;

            let addressInput = document.querySelector('input[name="address"]');
            if (!addressInput) {
                addressInput = document.createElement('input');
                addressInput.type = 'hidden';
                addressInput.name = 'address';
                this.appendChild(addressInput);
            }
            addressInput.value = fullAddress;

            console.log('Concatenated Address:', fullAddress);
        }

        console.log('All fields are valid. Submitting form to php/submit_application.php');
        // Manually set the form action and submit
        this.action = 'php/submit_application.php';
        this.submit();
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

    // Validate height and weight inputs to allow only numbers
    document.querySelectorAll('.number-input').forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

    // Function to add auto-slash functionality to date inputs
    function addAutoSlashToDateInputs(inputs) {
        inputs.forEach(input => {
            input.removeEventListener('input', handleDateInput);
            input.addEventListener('input', handleDateInput);
        });
    }

    function handleDateInput(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 4 && value.length <= 6) {
            value = value.slice(0, 4) + '/' + value.slice(4);
        } else if (value.length > 6) {
            value = value.slice(0, 4) + '/' + value.slice(4, 6) + '/' + value.slice(6, 8);
        }
        e.target.value = value;
    }

    // Apply auto-slash to existing date inputs on page load
    addAutoSlashToDateInputs(document.querySelectorAll('.date-input'));

    // Format postal code input as 000-0000
    const postalCodeInput = document.getElementById('postal_code');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 3) {
                value = value.slice(0, 3) + '-' + value.slice(3, 7);
            }
            e.target.value = value;
        });
    }

    // Postal code search using ZipCloud API
    const searchPostalCodeBtn = document.getElementById('search-postal-code-btn');
    if (searchPostalCodeBtn) {
        searchPostalCodeBtn.addEventListener('click', function() {
            const postalCode = document.getElementById('postal_code').value.replace(/\D/g, '');
            if (postalCode.length !== 7) {
                alert('正しい郵便番号を入力してください (例: 123-4567)');
                return;
            }

            const url = `http://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200 && data.results && data.results.length > 0) {
                        const address = data.results[0];
                        console.log(address); // Debug: Check API response
                        document.getElementById('prefecture').value = address.address1 || '';
                        document.getElementById('city_ward').value = address.address2 || '';
                        document.getElementById('street_address').value = '';
                        document.getElementById('home_details').value = '';
                    } else {
                        alert('該当する住所が見つかりませんでした。');
                    }
                })
                .catch(error => {
                    console.error('ZipCloud API Error:', error);
                    alert('住所検索中にエラーが発生しました。インターネット接続を確認してください。');
                });
        });
    }
});