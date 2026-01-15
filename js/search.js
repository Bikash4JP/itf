let workers = [];
let currentStep = 0;
const steps = ['personal', 'company', 'additional', 'business'];

function fetchData() {
    const loading = document.getElementById('loading');
    loading.style.display = 'block';
    fetch('https://it-future.jp/php/search.php?query=')
        .then(response => {
            if (!response.ok) throw new Error(`Network error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            loading.style.display = 'none';
            if (Array.isArray(data)) {
                workers = data;
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
}

function handleEnter(event) {
    if (event.key === 'Enter') {
        searchWorkers();
        document.querySelector('.info-text').style.display = 'none';
    }
}

function searchWorkers() {
    const input = document.getElementById('searchInput').value.toLowerCase().trim();
    const facilityFilter = document.getElementById('filterFacility').value.toLowerCase();
    const referrerFilter = document.getElementById('filterReferrer').value.toLowerCase();
    const nationalityFilter = document.getElementById('filterNationality').value.toLowerCase();
    const jlptFilter = document.getElementById('filterJLPT').value.toLowerCase();
    const genderFilter = document.getElementById('filterGender').value.toLowerCase();

    const resultsBody = document.getElementById('resultsBody');
    const resultsContainer = document.getElementById('resultsContainer');
    const fullDetails = document.getElementById('fullDetails');
    const infoText = document.querySelector('.info-text');
    resultsBody.innerHTML = '';
    fullDetails.style.display = 'none';
    resultsContainer.style.display = 'none';
    infoText.style.display = 'none';
    if (!input && !facilityFilter && !referrerFilter && !nationalityFilter && !jlptFilter && !genderFilter) {
        infoText.style.display = 'block';
        return;
    }
    const keywords = input.split(/\s+/).filter(keyword => keyword.length > 0);
    const filteredWorkers = workers.filter(worker => {
        let matches = keywords.length === 0 || keywords.every(keyword => {
            return Object.values(worker).some(value =>
                value && value.toString().toLowerCase().includes(keyword)
            );
        });
        if (facilityFilter && (worker['施設名（勤務先）'] || '').toLowerCase() !== facilityFilter) matches = false;
        if (referrerFilter && (worker['紹介元'] || '').toLowerCase() !== referrerFilter) matches = false;
        if (nationalityFilter && (worker['雇用者情報（国籍）'] || '').toLowerCase() !== nationalityFilter) matches = false;
        if (jlptFilter && (worker['JLPT状況'] || '').toLowerCase() !== jlptFilter) matches = false;
        if (genderFilter && (worker['雇用者情報（性別）'] || '').toLowerCase() !== genderFilter) matches = false;
        return matches;
    });
    if (filteredWorkers.length === 0) {
        resultsBody.innerHTML = '<tr><td colspan="5">一致する労働者が見つかりませんでした</td></tr>';
        resultsContainer.style.display = 'block';
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
        showFullDetails(filteredWorkers[0]['雇用者情報（アルファベット）']);
    }
}

function showFullDetails(name) {
    const worker = workers.find(w => (w['雇用者情報（アルファベット）'] || '') === name);
    if (!worker) {
        alert('データが見つかりませんでした');
        return;
    }
    const fullDetails = document.getElementById('fullDetails');
    const resultsContainer = document.getElementById('resultsContainer');
    const infoText = document.querySelector('.info-text');
    const buttonGroup = document.querySelector('.button-group');
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
    `;
    fullDetails.style.display = 'flex';
    resultsContainer.style.display = 'block';
    infoText.style.display = 'none';
    document.querySelector('table').style.display = 'none';
    buttonGroup.style.display = 'block';
}

function editWorker() {
    const name = document.querySelector('#fullDetails .box ul li:nth-child(1)').textContent.split(': ')[1];
    if (name) {
        window.location.href = `editstaff.php?name=${encodeURIComponent(name)}`;
    }
}

function printDetails() {
    const fullDetails = document.getElementById('fullDetails').innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Details</title>');
    printWindow.document.write('<style>@page { size: A4; margin: 1cm; } body { font-family: Arial, sans-serif; font-size: 12pt; } .box { border: 1px solid #ddd; padding: 10px; margin: 5px 0; page-break-inside: avoid; } .title { font-size: 16pt; font-weight: bold; border-bottom: 2px solid #000; margin-bottom: 5px; color: #0bb4e7; padding: 5px; } ul { list-style: none; padding: 0; } li { margin-bottom: 5px; font-size: 10pt; } .details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; page-break-inside: avoid; } @media print { body { margin: 0; } }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="details-grid">' + fullDetails + '</div>');
    printWindow.document.close();
    printWindow.print();
}

fetchData();

// Add event listeners to filters
document.querySelectorAll('.filter-container select').forEach(select => {
    select.addEventListener('change', searchWorkers);
});
