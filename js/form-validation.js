document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inquiryForm');
    const submitBtn = document.getElementById('submitBtn');
    const successMessage = document.getElementById('successMessage');
    const agreeCheckbox = document.getElementById('agree-checkbox');

    const errorMessages = {
        name: document.getElementById('name-error'),
        email: document.getElementById('email-error'),
        phone: document.getElementById('phone-error'),
        company: document.getElementById('company-error'),
        inquiry: document.getElementById('inquiry-error'),
        agree: document.getElementById('agree-error')
    };

    const requiredTextFields = [
        form.querySelector('input[name="name"]'),
        form.querySelector('input[type="email"]'),
        form.querySelector('input[type="tel"]'),
        form.querySelector('input[name="company"]')
    ];

    // Initialize button state
    checkFormCompleteness();
    form.addEventListener('input', checkFormCompleteness);
    form.addEventListener('change', checkFormCompleteness);

    // ── Form submission ────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!validateForm()) return;

        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '送信中...';

        const prevError = document.getElementById('formErrorMessage');
        if (prevError) prevError.remove();

        try {
            // ── Step 1: Fetch CSRF token ─────────────────────────
            // credentials:'same-origin' is REQUIRED so the browser
            // sends the PHP session cookie on both requests.
            const tokenRes = await fetch('php/get_csrf_token.php?form=inquiry', {
                credentials: 'same-origin'
            });

            if (!tokenRes.ok) {
                throw new Error('セキュリティトークンの取得に失敗しました。ページを再読み込みしてください。');
            }

            const contentTypeToken = tokenRes.headers.get('content-type') || '';
            if (!contentTypeToken.includes('application/json')) {
                throw new Error('サーバーから予期しない応答がありました。ページを再読み込みしてください。');
            }

            const tokenData = await tokenRes.json();

            // ── Step 2: Build form data ──────────────────────────
            const formData = new FormData(form);

            // Radio button — get single selected value
            const selectedInquiry = form.querySelector('input[name="inquiry"]:checked');
            formData.set('inquiry', selectedInquiry ? selectedInquiry.value : '');

            // Attach CSRF token
            formData.set('csrf_token', tokenData.token);

            // ── Step 3: POST to send_inquiry.php ─────────────────
            const response = await fetch('php/send_inquiry.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'  // must match token fetch above
            });

            // Guard against HTML error pages
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error(
                    'サーバーから予期しない応答がありました (HTTP ' + response.status + ')。' +
                    'しばらくしてから再度お試しください。'
                );
            }

            const result = await response.json();

            if (result.ok) {
                form.style.display = 'none';
                successMessage.style.display = 'block';

                // Countdown redirect to top page
                let seconds = 5;
                const redirectMsg = document.getElementById('redirectMessage');
                const timer = setInterval(function () {
                    seconds--;
                    if (redirectMsg) {
                        redirectMsg.textContent = seconds + '秒後にトップページに自動的に戻ります。';
                    }
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

            const errDiv = document.createElement('div');
            errDiv.id = 'formErrorMessage';
            errDiv.style.cssText = [
                'background:#fff0f0',
                'border:1px solid #e53e3e',
                'border-radius:6px',
                'padding:12px 16px',
                'margin-top:12px',
                'color:#c53030',
                'font-size:14px',
                'line-height:1.6'
            ].join(';');
            errDiv.innerHTML =
                '<strong>\u26a0 \u9001\u4fe1\u306b\u5931\u6557\u3057\u307e\u3057\u305f</strong><br>' +
                (error.message || '\u3057\u3070\u3089\u304f\u7d4c\u3063\u3066\u304b\u3089\u518d\u5ea6\u304a\u8a66\u3057\u304f\u3060\u3055\u3044\u3002') + '<br>' +
                '<small>\u304a\u6025\u304e\u306e\u5834\u5408\u306f ' +
                '<a href="tel:0666441800" style="color:#c53030;">06-6644-1800</a> \u307e\u305f\u306f ' +
                '<a href="mailto:info@it-future.jp" style="color:#c53030;">info@it-future.jp</a> ' +
                '\u3078\u76f4\u63a5\u3054\u9023\u7d61\u304f\u3060\u3055\u3044\u3002</small>';

            const submitArea = form.querySelector('.submit-btn');
            if (submitArea) submitArea.after(errDiv);
            else form.appendChild(errDiv);

        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            checkFormCompleteness();
        }
    });

    // ── Button enable/disable ──────────────────────────────────────
    function checkFormCompleteness() {
        let isComplete = requiredTextFields.every(f => f && f.value.trim() !== '');
        isComplete = isComplete && document.querySelector('input[name="inquiry"]:checked') !== null;
        isComplete = isComplete && agreeCheckbox.checked;

        const emailVal = form.querySelector('input[type="email"]').value;
        if (emailVal) isComplete = isComplete && validateEmail(emailVal);

        const phoneVal = form.querySelector('input[type="tel"]').value;
        if (phoneVal) isComplete = isComplete && validatePhone(phoneVal);

        submitBtn.disabled = !isComplete;
    }

    // ── Full validation on submit ──────────────────────────────────
    function validateForm() {
        let isValid = true;

        Object.values(errorMessages).forEach(el => {
            if (el) { el.style.display = 'none'; el.textContent = ''; }
        });

        requiredTextFields.forEach(field => {
            if (field && !field.value.trim()) {
                isValid = false;
                const key = field.name;
                if (errorMessages[key]) {
                    errorMessages[key].textContent = '\u3053\u306e\u9805\u76ee\u306f\u5fc5\u9808\u3067\u3059';
                    errorMessages[key].style.display = 'block';
                }
            }
        });

        if (!document.querySelector('input[name="inquiry"]:checked')) {
            isValid = false;
            if (errorMessages.inquiry) {
                errorMessages.inquiry.textContent = '\u304a\u554f\u3044\u5408\u308f\u305b\u5185\u5bb9\u3092\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044';
                errorMessages.inquiry.style.display = 'block';
            }
        }

        const email = form.querySelector('input[type="email"]').value;
        if (email && !validateEmail(email)) {
            isValid = false;
            if (errorMessages.email) {
                errorMessages.email.textContent = '\u6709\u52b9\u306a\u30e1\u30fc\u30eb\u30a2\u30c9\u30ec\u30b9\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044';
                errorMessages.email.style.display = 'block';
            }
        }

        const phone = form.querySelector('input[type="tel"]').value;
        if (phone && !validatePhone(phone)) {
            isValid = false;
            if (errorMessages.phone) {
                errorMessages.phone.textContent = '\u6709\u52b9\u306a\u96fb\u8a71\u756a\u53f7\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044 (10-11\u6841\u306e\u6570\u5b57)';
                errorMessages.phone.style.display = 'block';
            }
        }

        if (!agreeCheckbox.checked) {
            isValid = false;
            if (errorMessages.agree) {
                errorMessages.agree.textContent = '\u30d7\u30e9\u30a4\u30d0\u30b7\u30fc\u30dd\u30ea\u30b7\u30fc\u306b\u540c\u610f\u304c\u5fc5\u8981\u3067\u3059';
                errorMessages.agree.style.display = 'block';
            }
        }

        return isValid;
    }

    function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePhone(phone) {
        return /^[0-9]{10,11}$/.test(phone.replace(/[-\s]/g, ''));
    }
});