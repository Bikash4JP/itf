document.addEventListener('DOMContentLoaded', function() {
    const postsForm = document.getElementById('postsFormSubmit');
    const jobsForm = document.getElementById('jobsFormSubmit');
    const previewPopup = document.getElementById('previewPopup');
    const previewContent = document.getElementById('previewContent');
    const closePreviewButton = previewPopup.querySelector('.close-btn');
    const editButton = previewPopup.querySelector('.preview-actions button:first-child');
    const submitButton = previewPopup.querySelector('.preview-actions button:last-child');

    function showPreview(formData, formType) {
        const previewUrl = 'php/preview_post.php';
        console.log('Sending fetch request to:', previewUrl);
        console.log('Full URL:', new URL(previewUrl, window.location.origin).href);
        fetch(previewUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Preview response status:', response.status);
            console.log('Preview response ok:', response.ok);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
            }
            return response.text();
        })
        .then(data => {
            console.log('Preview data received:', data);
            previewContent.innerHTML = data;
            previewPopup.style.display = 'block';
            previewPopup.dataset.formType = formType;
        })
        .catch(error => {
            console.error('Error fetching preview:', error);
            alert('プレビューの取得に失敗しました。詳細: ' + error.message);
        });
    }

    window.previewForm = function(formName) {
        let formData;
        let formType;
        if (formName === 'posts') {
            formData = new FormData(postsForm);
            formType = 'posts';
        } else if (formName === 'jobs') {
            formData = new FormData(jobsForm);
            formType = 'jobs';
        }
        showPreview(formData, formType);
    };

    window.hidePreview = function() {
        previewPopup.style.display = 'none';
    };

    window.editForm = function() {
        previewPopup.style.display = 'none';
    };

    window.submitForm = function() {
        const formType = previewPopup.dataset.formType;
        let formToSubmit;
        if (formType === 'posts') {
            formToSubmit = postsForm;
        } else if (formType === 'jobs') {
            formToSubmit = jobsForm;
        }

        const formData = new FormData(formToSubmit);
        const submitUrl = 'php/submit_post.php';
        console.log('Sending fetch request to:', submitUrl);
        console.log('Full URL:', new URL(submitUrl, window.location.origin).href);
        fetch(submitUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Submit response status:', response.status);
            console.log('Submit response ok:', response.ok);
            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Submit response data:', data);
            if (data.success) {
                alert(data.message);
                previewPopup.style.display = 'none';
                if (formToSubmit) {
                    formToSubmit.reset();
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            alert('投稿に失敗しました。詳細: ' + error.message);
        });
    };

    // Functions for conditional form fields
    function toggleSalaryInput() {
        const salaryAmountGroup = document.getElementById('salary-amount-group');
        const salaryInput = document.getElementById('salary');
        if (document.querySelector('input[name="salary_type"]:checked').value === 'amount') {
            salaryAmountGroup.style.display = 'block';
            salaryInput.required = true;
        } else {
            salaryAmountGroup.style.display = 'none';
            salaryInput.required = false;
        }
    }

    function toggleBonusAmount() {
        const bonusAmountGroup = document.getElementById('bonus-amount-group');
        bonusAmountGroup.style.display = document.querySelector('input[name="bonuses"]:checked').value === '1' ? 'block' : 'none';
    }

    function toggleRentSupport() {
        const rentSupportGroup = document.getElementById('rent-support-group');
        rentSupportGroup.style.display = document.querySelector('input[name="living_support"]:checked').value === '1' ? 'block' : 'none';
        toggleRentSupportInput();
    }

    function toggleRentSupportInput() {
        const rentSupportAmountGroup = document.getElementById('rent-support-amount-group');
        const rentSupportInput = document.getElementById('rent_support_amount');
        if (document.querySelector('input[name="living_support"]:checked')?.value === '1') {
            rentSupportAmountGroup.style.display = 'block';
            rentSupportInput.required = document.querySelector('input[name="rent_support_type"]:checked').value === 'amount';
        } else {
            rentSupportAmountGroup.style.display = 'none';
            rentSupportInput.required = false;
        }
    }

    function toggleTransportAmount() {
        const transportAmountGroup = document.getElementById('transport-amount-group');
        transportAmountGroup.style.display = document.querySelector('input[name="transportation_charges"]:checked').value === '1' ? 'block' : 'none';
    }

    function toggleIncrementCondition() {
        const incrementConditionGroup = document.getElementById('increment-condition-group');
        incrementConditionGroup.style.display = document.querySelector('input[name="salary_increment"]:checked').value === '1' ? 'block' : 'none';
    }

    // Add event listeners for conditional fields
    const salaryTypeRadios = document.querySelectorAll('input[name="salary_type"]');
    const bonusesRadios = document.querySelectorAll('input[name="bonuses"]');
    const livingSupportRadios = document.querySelectorAll('input[name="living_support"]');
    const rentSupportTypeRadios = document.querySelectorAll('input[name="rent_support_type"]');
    const transportationRadios = document.querySelectorAll('input[name="transportation_charges"]');
    const salaryIncrementRadios = document.querySelectorAll('input[name="salary_increment"]');

    salaryTypeRadios.forEach(radio => radio.addEventListener('change', toggleSalaryInput));
    bonusesRadios.forEach(radio => radio.addEventListener('change', toggleBonusAmount));
    livingSupportRadios.forEach(radio => radio.addEventListener('change', toggleRentSupport));
    rentSupportTypeRadios.forEach(radio => radio.addEventListener('change', toggleRentSupportInput));
    transportationRadios.forEach(radio => radio.addEventListener('change', toggleTransportAmount));
    salaryIncrementRadios.forEach(radio => radio.addEventListener('change', toggleIncrementCondition));

    // Initial state
    toggleSalaryInput();
    toggleBonusAmount();
    toggleRentSupport();
    toggleTransportAmount();
    toggleIncrementCondition();

    if (closePreviewButton) {
        closePreviewButton.addEventListener('click', hidePreview);
    }
    if (editButton) {
        editButton.addEventListener('click', editForm);
    }
    if (submitButton) {
        submitButton.addEventListener('click', submitForm);
    }
});

window.showForm = function(formId) {
    document.getElementById(formId + 'Form').style.display = 'block';
};

window.hideForm = function(formId) {
    document.getElementById(formId + 'Form').style.display = 'none';
};