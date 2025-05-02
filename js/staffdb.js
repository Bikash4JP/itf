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
// js/staff.js
// Fetch posts when the page loads
document.addEventListener('DOMContentLoaded', () => {
    fetch('php/fetch_staff_posts.php', {
        method: 'GET',
        credentials: 'include' // Ensure session cookies are sent
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderStaffPosts(data.posts);
        } else {
            console.error(data.message);
            document.getElementById('postsList').innerHTML = `<p>${data.message}</p>`;
        }
    })
    .catch(error => {
        console.error('Error fetching posts:', error);
        document.getElementById('postsList').innerHTML = '<p>投稿の取得中にエラーが発生しました。</p>';
    });
});

// Render posts to the page
function renderStaffPosts(posts) {
    const postsList = document.getElementById('postsList');
    if (!postsList) return;

    postsList.innerHTML = '';

    if (posts.length === 0) {
        postsList.innerHTML = '<p>投稿がありません。</p>';
        return;
    }

    posts.forEach(post => {
        const postItem = document.createElement('div');
        postItem.className = `post-item ${post.post_type === 'job' ? 'post-item--job' : 'post-item--news'}`;

        // Post Meta
        const meta = document.createElement('div');
        meta.className = 'post-meta';
        meta.innerHTML = `
            <span class="date">Posted Date: ${post.date}</span>
            <span class="category">${post.category || post.job_category || 'その他'}</span>
            <span class="posted-by">Posted By: ${post.posted_by}</span>
        `;

        // Post Title
        const title = document.createElement('div');
        title.className = 'post-title';
        title.innerHTML = `<a href="post_view.php?id=${post.id}">${post.title}</a>`;

        // Post Image or Placeholder
        let image = '';
        if (post.image) {
            image = `
                <div class="post-image">
                    <img src="${post.image}" alt="${post.title}">
                </div>
            `;
        } else {
            image = `
                <div class="post-image">
                    <div class="image-placeholder">IMAGE (If Attached)</div>
                </div>
            `;
        }

        // Post Summary
        const summary = document.createElement('div');
        summary.className = 'post-summary';
        summary.textContent = post.short_summary;

        // Job Details (if applicable)
        let jobDetails = '';
        if (post.post_type === 'job') {
            jobDetails = `
                <div class="job-details">
                    ${post.company_name ? `Company: ${post.company_name}<br>` : ''}
                    ${post.job_location ? `Location: ${post.job_location}<br>` : ''}
                    ${post.job_type ? `Job Type: ${post.job_type}<br>` : ''}
                    ${post.salary ? `Salary: ${post.salary}<br>` : ''}
                    ${post.japanese_level ? `Japanese Level: ${post.japanese_level}<br>` : ''}
                </div>
            `;
        }

        // Construct the post item
        postItem.innerHTML = `
            ${image}
            <div class="post-content">
                ${meta.outerHTML}
                ${title.outerHTML}
                ${summary.outerHTML}
                ${jobDetails}
            </div>
        `;

        postsList.appendChild(postItem);
    });
}
// for staff control ---------------------------------------------------------------------------
