let workers = [];
let currentStep = 0;
const steps = ['personal', 'company', 'additional', 'business'];

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('resultsContainer');
    const loading = document.getElementById('loading');
    const resultsBody = document.getElementById('resultsBody');
    const fullDetails = document.getElementById('fullDetails');

    function fetchData(query = '') {
        loading.style.display = 'block';
        resultsContainer.style.display = 'none';
        fullDetails.style.display = 'none';

        console.log('Fetching with query:', query);
        if (!query || query.trim() === '') {
            loading.style.display = 'none';
            resultsBody.innerHTML = '<tr><td colspan="5">キーワードを入力してください</td></tr>';
            return;
        }

        fetch('/php/search.php?query=' + encodeURIComponent(query))
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status + ' - ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                loading.style.display = 'none';
                if (data.error) {
                    alert('エラー: ' + data.error);
                    console.error('Error:', data.error);
                } else if (Array.isArray(data) && data.length > 0) {
                    workers = data;
                    renderResults();
                } else {
                    resultsBody.innerHTML = '<tr><td colspan="5">一致するデータがありません</td></tr>';
                    console.log('No matching data found');
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                alert('エラー: ' + error.message);
                console.error('Fetch error:', error);
            });
    }

    function renderTableHeaders() {
        const tableHead = document.getElementById('tableHead');
        tableHead.innerHTML = '';
        const headers = ['番号', '雇用者情報（アルファベット）', '雇用者情報（生年月日）', '支援者住所', '施設名（勤務先）'];
        const row = document.createElement('tr');
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            row.appendChild(th);
        });
        tableHead.appendChild(row);
        console.log('Table headers rendered');
    }

    function renderResults() {
        resultsContainer.style.display = 'block';
        renderTableHeaders();
        document.querySelector('table').style.display = 'table';
        resultsBody.innerHTML = '';
        workers.forEach((worker, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td><a href="#" onclick="showFullDetails('${worker['雇用者情報（アルファベット）']}'); return false;">${worker['雇用者情報（アルファベット）'] || ''}</a></td>
                <td>${worker['雇用者情報（生年月日）'] || ''}</td>
                <td>${worker['支援者住所'] || ''}</td>
                <td>${worker['施設名（勤務先）'] || ''}</td>
            `;
            resultsBody.appendChild(row);
        });
    }

    function showFullDetails(name) {
        const worker = workers.find(w => w['雇用者情報（アルファベット）'] === name);
        if (!worker) {
            console.error('Worker not found for name:', name);
            return;
        }

        const fullDetails = document.getElementById('fullDetails');
        fullDetails.innerHTML = `
            <div class="box">
                <div class="title">個人情報</div>
                <ul>
                    <li>名前: ${worker['雇用者情報（アルファベット）'] || ''}</li>
                    <li>カタカナ名: ${worker['雇用者情報（カタカナ）'] || ''}</li>
                    <li>生年月日: ${worker['雇用者情報（生年月日）'] || ''}</li>
                    <li>住所: ${worker['支援者住所'] || ''}</li>
                    <li>電話番号: ${worker['連絡先①'] || ''}</li>
                    <li>性別: ${worker['雇用者情報（性別）'] || ''}</li>
                    <li>国籍: ${worker['雇用者情報（国籍）'] || ''}</li>
                    <li>年齢: ${worker['年齢'] || ''}</li>
                </ul>
            </div>
            <div class="box">
                <div class="title">会社情報</div>
                <ul>
                    <li>クライアント名: ${worker['施設名（勤務先）'] || ''}</li>
                    <li>入社日: ${worker['入社日'] || ''}</li>
                    <li>退職日: ${worker['支援退職日'] || ''}</li>
                    <li>状態: ${worker['状態'] || ''}</li>
                    <li>エリア: ${worker['エリア'] || ''}</li>
                    <li>受け入れ期間: ${worker['受け入れ期間'] || ''}</li>
                    <li>受入機関（郵便番号）: ${worker['受入機関（郵便番号）'] || ''}</li>
                    <li>受入機関（電話番号）: ${worker['受入機関（電話番号）'] || ''}</li>
                </ul>
            </div>
            <div class="box">
                <div class="title">追加情報</div>
                <ul>
                    <li>在留カード番号: ${worker['雇用者在留番号'] || ''}</li>
                    <li>在留カード有効期限: ${worker['雇用者在留期限'] || ''}</li>
                    <li>更新回数: ${worker['更新回数'] || ''}</li>
                    <li>管理番号: ${worker['管理番号'] || ''}</li>
                    <li>担当責任者: ${worker['担当責任者'] || ''}</li>
                    <li>正担当者: ${worker['正担当者'] || ''}</li>
                    <li>紹介元: ${worker['紹介元'] || ''}</li>
                    <li>住居タイプ: ${worker['住居タイプ'] || ''}</li>
                </ul>
            </div>
            <div class="box">
                <div class="title">ビジネス情報</div>
                <ul>
                    <li>管理費: ${worker['管理費'] || ''}</li>
                    <li>紹介料: ${worker['紹介料'] || ''}</li>
                    <li>基本契約書: ${worker['基本契約書'] || ''}</li>
                    <li>委託契約書: ${worker['委託契約書'] || ''}</li>
                    <li>会社担当者: ${worker['担当者（企業）'] || ''}</li>
                </ul>
            </div>
            <div class="details-buttons">
                <button class="edit-btn" onclick="editWorker('${name}')">編集</button>
            </div>
        `;
        fullDetails.style.display = 'flex';
        resultsContainer.style.display = 'none';
    }

    function addWorker() {
        currentStep = 0;
        const form = document.getElementById('workerForm');
        const formTitle = document.getElementById('formTitle');
        formTitle.textContent = '人材情報を追加';
        document.getElementById('formSteps').innerHTML = generateSteps();
        showStep();
        form.style.display = 'block';
    }

    function editWorker(name) {
        const worker = workers.find(w => w['雇用者情報（アルファベット）'] === name);
        if (!worker) {
            console.error('Worker not found for name:', name);
            return;
        }

        currentStep = 0;
        const form = document.getElementById('workerForm');
        const formTitle = document.getElementById('formTitle');
        formTitle.textContent = '労働者の編集';
        document.getElementById('formSteps').innerHTML = generateSteps(worker);
        showStep();
        form.style.display = 'block';
    }

    function generateSteps(worker = null) {
        const headersByCategory = {
            personal: ['雇用者情報（アルファベット）', '雇用者情報（カタカナ）', '雇用者情報（生年月日）', '支援者住所', '連絡先①', '雇用者情報（性別）', '雇用者情報（国籍）', '年齢'],
            company: ['施設名（勤務先）', '入社日', '支援退職日', '状態', 'エリア', '受け入れ期間', '受入機関（郵便番号）', '受入機関（電話番号）'],
            additional: ['雇用者在留番号', '雇用者在留期限', '更新回数', '管理番号', '担当責任者', '正担当者', '紹介元', '住居タイプ'],
            business: ['管理費', '紹介料', '基本契約書', '委託契約書', '担当者（企業）']
        };
        let html = '';
        steps.forEach((step, index) => {
            html += `<div class="box ${index === 0 ? 'active' : ''}" id="step-${step}">
                <div class="title">${step === 'personal' ? '個人情報' : step === 'company' ? '会社情報' : step === 'additional' ? '追加情報' : 'ビジネス情報'}</div>`;
            headersByCategory[step].forEach(header => {
                const value = worker ? worker[header] || '' : '';
                html += `<label>${header}: </label>`;
                if (['施設名（勤務先）', '状態', 'エリア'].includes(header)) {
                    html += `<select name="${header}"><option value="">選択してください</option>${getUniqueValues(header).map(v => `<option value="${v}" ${value === v ? 'selected' : ''}>${v}</option>`).join('')}</select><br>`;
                } else {
                    html += `<input type="text" name="${header}" value="${value}" placeholder="${header}を入力"><br>`;
                }
            });
            html += `</div>`;
        });
        return html;
    }

    function getUniqueValues(header) {
        return [...new Set(workers.map(w => w[header] || '').filter(v => v))];
    }

    function showStep() {
        steps.forEach((step, index) => {
            const stepDiv = document.getElementById(`step-${step}`);
            stepDiv.classList.toggle('active', index === currentStep);
        });
        document.getElementById('prevStepBtn').style.display = currentStep > 0 ? 'inline-block' : 'none';
        document.getElementById('nextStepBtn').style.display = currentStep < steps.length - 1 ? 'inline-block' : 'none';
        document.getElementById('submitFormBtn').style.display = currentStep === steps.length - 1 ? 'inline-block' : 'none';
    }

    function nextStep() {
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep();
        }
    }

    function prevStep() {
        if (currentStep > 0) {
            currentStep--;
            showStep();
        }
    }

    function closeForm() {
        document.getElementById('workerForm').style.display = 'none';
    }

    function submitForm(event) {
        event.preventDefault();
        const form = document.getElementById('workerFormData');
        const formData = new FormData(form);
        const values = [];
        const headers = workers.length > 0 ? Object.keys(workers[0]) : [];
        headers.forEach(header => values.push(formData.get(header) || ''));
        const name = formData.get('雇用者情報（アルファベット）');
        const method = document.getElementById('formTitle').textContent === '人材情報を追加' ? 'POST' : 'PUT';

        fetch('/php/search.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                [method === 'POST' ? 'add' : 'update']: '1',
                name: name,
                values: JSON.stringify(values)
            }).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('エラー: ' + data.error);
                console.error('Error:', data.error);
            } else {
                console.log(data.message);
                closeForm();
                fetchData(); // Refresh data
            }
        })
        .catch(error => {
            alert('エラー: ' + error.message);
            console.error('Fetch error:', error);
        });
    }

    // Event listener for search input
    searchInput.addEventListener('keyup', () => {
        const query = searchInput.value.trim();
        console.log('Input value:', query);
        if (query) {
            fetchData(query);
        } else {
            const resultsBody = document.getElementById('resultsBody');
            resultsBody.innerHTML = '<tr><td colspan="5">キーワードを入力してください</td></tr>';
        }
    });

    // Initial data fetch (optional)
    fetchData();
});