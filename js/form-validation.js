document.addEventListener('DOMContentLoaded', function() {
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
    form.addEventListener('submit', async function(e) {
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
        
        try {
            // Prepare form data
            const formData = new FormData(form);
            
            // Get all selected inquiry types
            const selectedInquiries = Array.from(form.querySelectorAll('input[name="inquiry"]:checked'))
                .map(checkbox => checkbox.value)
                .join(', ');
            
            // Add to form data
            formData.append('inquiry_types', selectedInquiries);
            formData.append('_cc', 'ueda@it-future.jp');
            formData.append('_subject', `【ITF問い合わせ】${formData.get('company')} - ${formData.get('name')}`);
            
            // Send to Formspree
            const response = await fetch('https://formspree.io/f/xrbprkko', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }   
            });
            
            if (response.ok) {
                // Show success message
                form.style.display = 'none';
                successMessage.style.display = 'block';
                
                // Redirect after 5 seconds
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
                throw new Error(`サーバーエラー: ${response.status}`);
            }
        } catch (error) {
            console.error('Submission error:', error);
            
            // Fallback to mailto if Formspree fails
            if (confirm('フォーム送信に失敗しました。メールで直接送信しますか？')) {
                const formData = new FormData(form);
                const selectedInquiries = Array.from(form.querySelectorAll('input[name="inquiry"]:checked'))
                    .map(checkbox => checkbox.value)
                    .join(', ');
                
                const mailtoBody = [
                    `氏名: ${formData.get('name')}`,
                    `メール: ${formData.get('email')}`,
                    `電話: ${formData.get('phone')}`,
                    `会社名: ${formData.get('company')}`,
                    `問い合わせ種類: ${selectedInquiries}`,
                    `メッセージ: ${formData.get('message') || '未記入'}`,
                    '',
                    '※このメールはフォーム送信失敗時に代替手段として送信されました'
                ].join('%0A');
                
                window.location.href = `mailto:bikash4jp@gmail.com?subject=【ITF問い合わせ】${encodeURIComponent(formData.get('company'))}&body=${mailtoBody}`;
            }
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