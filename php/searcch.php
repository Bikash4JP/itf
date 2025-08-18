<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>労働者検索</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 20px auto;
            text-align: center;
        }

        #searchInput {
            padding: 10px;
            font-size: 16px;
            border: 2px solid #09b2e6;
            border-radius: 5px;
            width: 50%;
        }

        .suggestions {
            position: absolute;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 50%;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 5px;
            display: none;
        }

        .suggestions div {
            padding: 10px;
            cursor: pointer;
        }

        .suggestions div:hover {
            background-color: #f0f0f0;
        }

        @media (max-width: 768px) {
            .suggestions {
                width: 80%;
            }
        }

        #resultsContainer {
            display: none;
            max-width: 1200px;
            margin: 20px auto;
        }

        #loading {
            text-align: center;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            display: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: rgb(6, 173, 240);
            color: white;
            font-weight: bold;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .details-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .box {
            flex: 1;
            min-width: 45%;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #fff;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            color: rgb(11, 180, 231);
            padding: 5px;
            border-radius: 5px;
        }

        .details-buttons {
            text-align: center;
            margin-top: 20px;
            border-top: 2px solid #09b2e6;
            padding-top: 10px;
        }

        .details-buttons .edit-btn {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .details-buttons .edit-btn:hover {
            background-color: #218838;
        }

        @media (max-width: 768px) {
            #searchInput {
                width: 80%;
            }
        }
    </style>
</head>

<body>
    <header>
        <div class="logo"><a href="https://it-future.jp/"><img src="../images/logo.png" alt="ITF Logo"></a></div>
        <nav>
            <ul>
                <li><a href="../staffdb.php">ホーム</a></li>
                <li><a href="#" onclick="showForm('posts')">投稿を追加</a></li>
                <li><a href="#" onclick="showForm('jobs')">求人を追加</a></li>
                <li><a href="php/manage_posts.php">投稿を管理</a></li>
                <li><a href="php/search.html" class="active">検索</a></li>
                <li><a href="php/logout.php">ログアウト</a></li>
            </ul>
        </nav>
    </header>

    <div class="search-container">
        <input type="text" id="searchInput" placeholder="複数のキーワードで検索 (スペースで区切って検索)..." onkeyup="searchWorkers()"
            onfocus="showSuggestions()" onblur="hideSuggestions()">
        <div id="suggestions" class="suggestions"></div>
        <button id="addWorkerBtn">人材情報を追加</button>
    </div>
    <div id="resultsContainer" style="display:none;">
        <div id="loading" style="display:none;">読み込み中...</div>
        <table>
            <thead id="tableHead"></thead>
            <tbody id="resultsBody"></tbody>
        </table>
        <div id="fullDetails" class="details-grid" style="display:none;"></div>
    </div>
    <div id="workerForm" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close-btn" onclick="closeForm()">×</span>
            <h2 id="formTitle"></h2>
            <div id="formSteps"></div>
            <div id="stepButtons">
                <button id="prevStepBtn" style="display:none;" onclick="prevStep()">前へ</button>
                <button id="nextStepBtn" onclick="nextStep()">次へ</button>
                <button id="submitFormBtn" style="display:none;" onclick="submitForm(event)">保存</button>
            </div>
        </div>
    </div>

    <script>
        let workers = [];
        let currentStep = 0;
        const steps = ['personal', 'company', 'additional', 'business'];

        function fetchData() {
            const loading = document.getElementById('loading');
            loading.style.display = 'block';
            console.log('Fetching initial data from https://it-future.jp/php/search.php...');
            fetch('https://it-future.jp/php/search.php?query=')
                .then(response => {
                    if (!response.ok) throw new Error(`Network error! Status: ${response.status}`);
                    console.log('Response received:', response);
                    return response.json();
                })
                .then(data => {
                    loading.style.display = 'none';
                    if (Array.isArray(data)) {
                        workers = data; // Populate workers with all data
                        console.log('Workers array populated:', workers.length, 'workers');
                    } else {
                        console.log('No data in response or error:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error.message);
                    loading.textContent = 'エラー: データの読み込みに失敗しました - ' + error.message;
                    loading.style.display = 'block';
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

        function searchWorkers() {
            const input = document.getElementById('searchInput').value.toLowerCase().trim();
            const resultsBody = document.getElementById('resultsBody');
            const resultsContainer = document.getElementById('resultsContainer');
            const fullDetails = document.getElementById('fullDetails');
            resultsBody.innerHTML = '';
            fullDetails.style.display = 'none';
            resultsContainer.style.display = 'none';

            if (!input) {
                return;
            }

            // Split by any whitespace (English space, Japanese space, multiple spaces)
            const keywords = input.split(/\s+/).filter(keyword => keyword.length > 0);
            if (keywords.length === 0) {
                return;
            }
            console.log('Keywords processed:', keywords); // Debug log

            const filteredWorkers = workers.filter(worker => {
                return keywords.every(keyword => {
                    return Object.values(worker).some(value =>
                        value && value.toString().toLowerCase().includes(keyword)
                    );
                });
            });

            if (filteredWorkers.length === 0) {
                resultsBody.innerHTML = '<tr><td colspan="5">一致する労働者が見つかりませんでした</td></tr>';
                console.log('No matching workers found for keywords:', keywords);
                return;
            }

            resultsContainer.style.display = 'block';
            if (filteredWorkers.length > 1) {
                renderTableHeaders();
                document.querySelector('table').style.display = 'table';
                filteredWorkers.forEach((worker, index) => {
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
            } else if (filteredWorkers.length === 1) {
                console.log('Single worker found:', filteredWorkers[0]['雇用者情報（アルファベット）']);
                showFullDetails(filteredWorkers[0]['雇用者情報（アルファベット）']);
            }
        }

        function showFullDetails(name) {
            console.log('Showing full details for:', name); // Debug name
            const worker = workers.find(w => (w['雇用者情報（アルファベット）'] || '') === name); // Direct match
            if (!worker) {
                console.error('Worker not found for name:', name);
                alert('データが見つかりませんでした');
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
            document.querySelector('table').style.display = 'none';
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
            const worker = workers.find(w => (w['雇用者情報（アルファベット）'] || '') === name);
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

            fetch('php/search.php', {
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
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('keyup', searchWorkers);

        // Initial data fetch
        fetchData();
    </script>
</body>

</html>