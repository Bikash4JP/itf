document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inquiryForm');
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('successMessage');
    const requiredTextFields = Array.from(form.querySelectorAll('input[required]:not([type="checkbox"]):not([name="inquiry"])'));
    const agreeCheckbox = document.getElementById('agree-checkbox');

    // Error message elements
    const errorMessages = {
        name: document.getElementById('name-error'),
        email: document.getElementById('email-error'),
        phone: document.getElementById('phone-error'),
        company: document.getElementById('company-error'),
        inquiry: document.getElementById('inquiry-error'),
        agree: document.getElementById('agree-error')
    };

    let hasAttemptedSubmit = false;

    // Convert radio group to checkboxes for multiple selection
    const radioGroup = document.getElementById('inquiry-group');
    if (radioGroup) {
        // Convert all radio inputs to checkboxes
        const radioInputs = radioGroup.querySelectorAll('input[type="radio"]');
        radioInputs.forEach(input => {
            input.type = 'checkbox';
        });
    }

    // Initialize form validation
    checkFormCompleteness();

    // Real-time validation
    form.addEventListener('input', checkFormCompleteness);
    form.addEventListener('change', checkFormCompleteness);

    // Form submission
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        hasAttemptedSubmit = true;

        if (!validateForm()) {
            alert('必須項目をすべて正しく入力してください');
            return;
        }

        // Set loading state
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '送信中...';

        // Hide any previous error
        const prevError = document.getElementById('formErrorMessage');
        if (prevError) prevError.remove();

        try {
            // Prepare form data
            const formData = new FormData(form);

            // inquiry チェックボックス群をまとめる
            const selectedInquiries = Array.from(form.querySelectorAll('input[name="inquiry"]:checked'))
                .map(cb => cb.value)
                .join('、');
            formData.set('inquiry', selectedInquiries);

            // PHPハンドラーへ送信（自社サーバー・第三者不要）
            const response = await fetch('php/send_inquiry.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.ok) {
                // 成功：フォームを非表示にして完了メッセージを表示
                form.style.display = 'none';
                successMessage.style.display = 'block';

                // 5秒後にトップへリダイレクト
                let seconds = 5;
                const redirectMsg = document.getElementById('redirectMessage');
                const timer = setInterval(() => {
                    seconds--;
                    redirectMsg.textContent = `${seconds}秒後にトップページに自動的に戻ります。`;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = 'index.html';
                    }
                }, 1000);
            } else {
                throw new Error(result.error || 'サーバーエラーが発生しました');
            }
        } catch (error) {
            console.error('Submission error:', error);

            // インラインエラーメッセージ（メールアプリは不要）
            const errDiv = document.createElement('div');
            errDiv.id = 'formErrorMessage';
            errDiv.style.cssText = 'background:#fff0f0;border:1px solid #e53e3e;border-radius:6px;padding:12px 16px;margin-top:12px;color:#c53030;font-size:14px;line-height:1.6;';
            errDiv.innerHTML = `
                <strong>⚠ 送信に失敗しました</strong><br>
                ${error.message || 'しばらく経ってから再度お試しください。'}<br>
                <small>お急ぎの場合は <a href="tel:0666441800" style="color:#c53030;">06-6644-1800</a>
                または <a href="mailto:info@it-future.jp" style="color:#c53030;">info@it-future.jp</a> へ直接ご連絡ください。</small>
            `;
            form.querySelector('.submit-btn').after(errDiv);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });


    // Check if form is complete (for button state)
    function checkFormCompleteness() {
        let isComplete = true;

        // Check required text fields
        isComplete = requiredTextFields.every(field => field.value.trim() !== '');

        // Check at least one inquiry is selected
        isComplete = isComplete && document.querySelector('input[name="inquiry"]:checked') !== null;

        // Check agreement checkbox
        isComplete = isComplete && agreeCheckbox.checked;

        // Check email format if email field has content
        const email = form.querySelector('input[type="email"]').value;
        if (email) {
            isComplete = isComplete && validateEmail(email);
        }

        // Check phone format if phone field has content
        const phone = form.querySelector('input[type="tel"]').value;
        if (phone) {
            isComplete = isComplete && validatePhone(phone);
        }

        submitBtn.disabled = !isComplete;
    }

    // Full validation (for submission)
    function validateForm() {
        let isValid = true;

        // Reset error messages
        Object.values(errorMessages).forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });

        // Validate required fields
        requiredTextFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                const fieldName = field.name;
                if (errorMessages[fieldName]) {
                    errorMessages[fieldName].textContent = 'この項目は必須です';
                    errorMessages[fieldName].style.display = 'block';
                }
            }
        });

        // Validate at least one inquiry is selected
        const inquiryChecked = document.querySelector('input[name="inquiry"]:checked');
        if (!inquiryChecked) {
            isValid = false;
            errorMessages.inquiry.textContent = '少なくとも1つのお問い合わせ内容を選択してください';
            errorMessages.inquiry.style.display = 'block';
        }

        // Validate email format
        const email = form.querySelector('input[type="email"]').value;
        if (email && !validateEmail(email)) {
            isValid = false;
            errorMessages.email.textContent = '有効なメールアドレスを入力してください';
            errorMessages.email.style.display = 'block';
        }

        // Validate phone format
        const phone = form.querySelector('input[type="tel"]').value;
        if (phone && !validatePhone(phone)) {
            isValid = false;
            errorMessages.phone.textContent = '有効な電話番号を入力してください (10-11桁の数字)';
            errorMessages.phone.style.display = 'block';
        }

        // Validate agreement checkbox
        if (!agreeCheckbox.checked) {
            isValid = false;
            errorMessages.agree.textContent = 'プライバシーポリシーに同意が必要です';
            errorMessages.agree.style.display = 'block';
        }

        return isValid;
    }

    // Helper functions
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validatePhone(phone) {
        const re = /^[0-9]{10,11}$/;
        return re.test(phone);
    }
});