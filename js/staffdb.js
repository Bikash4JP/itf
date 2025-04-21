let activeForm = null;

function showForm(formType) {
    const form = document.getElementById(`${formType}Form`);
    form.style.display = 'flex';
    document.body.classList.add('blur');
    activeForm = formType;

    // Word counter for posts form
    if (formType === 'posts') {
        const summary = document.getElementById('post-summary');
        const wordCount = document.getElementById('post-word-count');
        summary.addEventListener('input', () => {
            const words = summary.value.trim().split(/\s+/).filter(w => w.length > 0).length;
            wordCount.textContent = words;
            if (words > 100) {
                wordCount.style.color = 'red';
                summary.setCustomValidity('概要は100語以内にしてください。');
            } else {
                wordCount.style.color = '#555';
                summary.setCustomValidity('');
            }
        });
    }

    // Conditional fields for jobs form
    if (formType === 'jobs') {
        const bonuses = document.getElementsByName('bonuses');
        const bonusGroup = document.getElementById('bonus-amount-group');
        const livingSupport = document.getElementsByName('living_support');
        const rentGroup = document.getElementById('rent-support-group');
        const transport = document.getElementsByName('transportation_charges');
        const transportGroup = document.getElementById('transport-amount-group');
        const increment = document.getElementsByName('salary_increment');
        const incrementGroup = document.getElementById('increment-condition-group');

        bonuses.forEach(radio => radio.addEventListener('change', () => {
            bonusGroup.style.display = radio.value === '1' ? 'block' : 'none';
        }));
        livingSupport.forEach(radio => radio.addEventListener('change', () => {
            rentGroup.style.display = radio.value === '1' ? 'block' : 'none';
        }));
        transport.forEach(radio => radio.addEventListener('change', () => {
            transportGroup.style.display = radio.value === '1' ? 'block' : 'none';
        }));
        increment.forEach(radio => radio.addEventListener('change', () => {
            incrementGroup.style.display = radio.value === '1' ? 'block' : 'none';
        }));
    }
}

function hideForm(formType) {
    const form = document.getElementById(`${formType}Form`);
    form.style.display = 'none';
    document.body.classList.remove('blur');
    activeForm = null;
    form.querySelector('form').reset();
}

function previewForm(formType) {
    const form = document.getElementById(`${formType}FormSubmit`);
    const formData = new FormData(form);
    const imageInput = document.getElementById(`${formType}-image`);
    if (imageInput.files.length > 0) {
        const file = imageInput.files[0];
        if (file.size > 2 * 1024 * 1024) {
            alert('画像は2MB以下にしてください。');
            return;
        }
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            alert('画像はJPEGまたはPNG形式でアップロードしてください。');
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            formData.set('image_preview', reader.result);
            sendPreviewRequest(formData);
        };
        reader.readAsDataURL(file);
    } else {
        formData.set('image_preview', '');
        sendPreviewRequest(formData);
    }
}

function sendPreviewRequest(formData) {
    fetch('php/preview_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        const previewContent = document.getElementById('previewContent');
        previewContent.innerHTML = html;
        document.getElementById('previewPopup').style.display = 'flex';
        document.body.classList.add('blur');
    })
    .catch(error => {
        console.error('Preview error:', error);
        alert('プレビュー生成中にエラーが発生しました。');
    });
}

function hidePreview() {
    document.getElementById('previewPopup').style.display = 'none';
    document.body.classList.remove('blur');
}

function editForm() {
    hidePreview();
    document.getElementById(`${activeForm}Form`).style.display = 'flex';
}

function submitForm() {
    const form = document.getElementById(`${activeForm}FormSubmit`);
    const formData = new FormData(form);
    fetch('php/submit_post.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hidePreview();
            const successPopup = document.getElementById('successPopup');
            successPopup.style.display = 'flex';
            setTimeout(() => {
                successPopup.style.display = 'none';
                hideForm(activeForm);
            }, 3000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Submit error:', error);
        alert('投稿中にエラーが発生しました。');
    });
}