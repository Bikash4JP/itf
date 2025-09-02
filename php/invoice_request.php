<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

// Handle AJAX request for staff list
if (isset($_GET['client']) && isset($_GET['month'])) {
    $client = $_GET['client'];
    $month = $_GET['month'];
    $monthDate = new DateTime($month);
    $startDate = $monthDate->format('Y-m-01');
    $endDate = $monthDate->format('Y-m-t');

    $stmt = $pdo->prepare("SELECT 雇用者情報（アルファベット）, 入社日, 状態 FROM talents WHERE 施設名（勤務先） = ? AND (入社日 BETWEEN ? AND ? OR 状態 = '在職中')");
    $stmt->execute([$client, $startDate, $endDate]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Fetch unique clients for dropdown
$clients = getUniqueValues($pdo, '施設名（勤務先）');

// Function to get unique values
function getUniqueValues($pdo, $column) {
    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM talents WHERE $column IS NOT NULL AND $column != ''");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>請求書リクエスト</title>
    <link rel="stylesheet" href="../css/staffdb.css">
    <link rel="stylesheet" href="../css/search.css">
    <style>
        .invoice-container {
            width: 80%;
            margin: 20px auto;
        }
        .filters {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        select {
            padding: 10px;
            border: 1px solid #1e90ff;
            border-radius: 5px;
        }
        #invoiceList {
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #09b2e6;
            color: white;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .confirm-btn {
            background-color: #4169e1;
            color: white;
        }
        .edit-btn {
            background-color: #28a745;
            color: white;
            margin-left: 10px;
        }
        /* Print Template Style (hidden by default) */
        #invoiceTemplate {
            display: none;
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            background: white;
        }
        #invoiceTemplate h2 {
            text-align: center;
        }
        #invoiceTemplate table {
            width: 100%;
            margin-top: 20px;
        }
        @media print {
            #invoiceTemplate {
                display: block;
            }
            .main-container, header, .filters, .buttons {
                display: none;
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
                <li><a href="manage_posts.php">投稿を管理</a></li>
                <li><a href="searcch.php" class="active">検索</a></li>
                <li><a href="logout.php">ログアウト</a></li>
            </ul>
        </nav>
    </header>

    <div class="main-container">
        <!-- Sidebar Menu -->
        <div class="menu-bar">
            <div class="menu-icon"><img src="../images/searcch.png" alt="Search Icon"></div>
            <div class="menu-title">Menus</div>
            <ul>
                <li><a href="addstaff.php" class="menu-btn">人材を追加</a></li>
                <li><a href="editstaff.php" class="menu-btn">人材を編集</a></li>
                <li><a href="invoice_request.php" class="menu-btn">請求書リクエスト</a></li>
                <li><a href="t.html" class="menu-btn">今月の入社</a></li>
                <li><a href="t.html" class="menu-btn">未定1</a></li>
                <li><a href="t.html" class="menu-btn">未定2</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="content-area">
            <div class="invoice-container">
                <div class="filters">
                    <select id="clientSelect">
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo htmlspecialchars($client); ?>"><?php echo htmlspecialchars($client); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="monthSelect">
                        <option value="">Select Month</option>
                        <!-- JS will populate months -->
                    </select>
                </div>
                <div id="invoiceList">
                    <table>
                        <thead>
                            <tr>
                                <th>ご請求内容</th>
                                <th>入社日</th>
                                <th>金　　額</th>
                            </tr>
                        </thead>
                        <tbody id="listBody"></tbody>
                    </table>
                    <div class="buttons">
                        <button class="btn confirm-btn" onclick="confirmInvoice()">Confirm and Print</button>
                        <button class="btn edit-btn" onclick="editInvoice()">Edit</button>
                    </div>
                </div>
                <!-- Print Template -->
                <div id="invoiceTemplate">
                    <p>管理NO. <span id="managementNo">1851</span></p>
                    <p>御中 <span id="templateClient"></span></p>
                    <h2>御請求書</h2>
                    <p>発行日: <span id="issueDate"></span></p>
                    <p>支払期限: <span id="dueDate"></span></p>
                    <p>毎度お引き立てにあずかり誠にありがとうございます。</p>
                    <p>株式会社　アイティーエフ</p>
                    <p>〒555-0017　大阪市浪速区湊町1-4-38</p>
                    <p>近鉄新難波ビル10階</p>
                    <p>TEL：06-6644-1800　FAX：06-6644-1801</p>
                    <p>登録番号 T9120001214875</p>
                    <p>御請求金額: <span id="templateTotalAmount"></span></p>
                    <p>ご請求内容</p>
                    <table>
                        <thead>
                            <tr>
                                <th>ご請求内容</th>
                                <th>入社日</th>
                                <th>金　　額</th>
                            </tr>
                        </thead>
                        <tbody id="templateBody"></tbody>
                    </table>
                    <p>小計: <span id="templateSubtotal"></span></p>
                    <p>消費税 (0.1): <span id="templateTax"></span></p>
                    <p>合計金額（税込）: <span id="templateTotalAmount2"></span></p>
                    <p>内訳</p>
                    <p>10％対象: <span id="templateTaxable"></span>, 消費税: <span id="templateTaxValue"></span></p>
                    <p>課税対象外: 0, 消費税: 対象外</p>
                    <p>お振込先: 三井住友銀行　奈良支店　普通　1669102　株式会社アイティーエフ</p>
                    <p>銀行振込手数料は、貴社のご負担にてお願いします。</p>
                    <p>＊　課税対象外</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Populate month dropdown (e.g., last 12 months)
        const monthSelect = document.getElementById('monthSelect');
        const currentDate = new Date();
        for (let i = 0; i < 12; i++) {
            const date = new Date(currentDate.getFullYear(), currentDate.getMonth() - i, 1);
            const month = date.toLocaleString('default', { month: 'long', year: 'numeric' });
            monthSelect.innerHTML += `<option value="${month}">${month}</option>`;
        }

        // Fetch list on selection change
        document.getElementById('clientSelect').addEventListener('change', fetchList);
        document.getElementById('monthSelect').addEventListener('change', fetchList);

        function fetchList() {
            const client = document.getElementById('clientSelect').value;
            const month = document.getElementById('monthSelect').value;
            if (client && month) {
                const listBody = document.getElementById('listBody');
                listBody.innerHTML = '<tr><td colspan="3">Loading...</td></tr>';
                document.getElementById('invoiceList').style.display = 'block';

                fetch(`invoice_request.php?client=${encodeURIComponent(client)}&month=${encodeURIComponent(month)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        listBody.innerHTML = '';
                        const hasNewJoining = data.some(item => item['入社日']);
                        const monthNum = new Date(month).getMonth() + 1;
                        if (data.length > 0) {
                            data.forEach(item => {
                                const joinDate = item['入社日'] ? new Date(item['入社日']).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '年').replace(/(\d+)年(\d+)月(\d+)日/, '$1年$2月$3日') : '';
                                const statusText = item['入社日'] ? `${joinDate}` : (hasNewJoining ? '' : `${monthNum}月`);
                                const amount = item['状態'] === '在職中' ? 20000 : (item['入社日'] ? 200000 : 0);
                                const staffLabel = `${item['雇用者情報（アルファベット）']} 様`;
                                const feeType = item['状態'] === '在職中' ? '支援委託料' : (item['入社日'] ? 'ご紹介手数料' : '');
                                if (item['状態'] === '在職中' || item['入社日']) {
                                    listBody.innerHTML += `<tr><td>${staffLabel} ${feeType}</td><td>${statusText}</td><td>${amount}</td></tr>`;
                                }
                            });
                        } else {
                            listBody.innerHTML = '<tr><td colspan="3">No data found</td></tr>';
                        }
                    })
                    .catch(error => {
                        listBody.innerHTML = '<tr><td colspan="3">Error fetching data: ' + error.message + '</td></tr>';
                        console.error('Error:', error);
                    });
            }
        }

        function confirmInvoice() {
            const client = document.getElementById('clientSelect').value;
            const month = document.getElementById('monthSelect').value;
            const listBody = document.getElementById('listBody');
            const managementNo = document.getElementById('managementNo');
            const issueDate = document.getElementById('issueDate');
            const dueDate = document.getElementById('dueDate');
            const templateClient = document.getElementById('templateClient');
            const templateMonth = document.getElementById('templateMonth');
            const templateBody = document.getElementById('templateBody');
            const templateTotalAmount = document.getElementById('templateTotalAmount');
            const templateSubtotal = document.getElementById('templateSubtotal');
            const templateTax = document.getElementById('templateTax');
            const templateTaxable = document.getElementById('templateTaxable');
            const templateTaxValue = document.getElementById('templateTaxValue');
            const templateTotalAmount2 = document.getElementById('templateTotalAmount2');
            const monthDate = new Date(month);
            const startDate = new Date(monthDate.getFullYear(), monthDate.getMonth(), 1).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '年').replace(/(\d+)年(\d+)月(\d+)日/, '$1年$2月$3日');
            const endDate = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '年').replace(/(\d+)年(\d+)月(\d+)日/, '$1年$2月$3日');

            templateClient.textContent = client;
            templateMonth.textContent = month;
            templateBody.innerHTML = listBody.innerHTML;
            managementNo.textContent = '1851'; // Example, replace with dynamic logic if needed
            issueDate.textContent = new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '');
            dueDate.textContent = new Date(new Date().setDate(new Date().getDate() + 30)).toLocaleDateString('ja-JP', { year: 'numeric', month: '2-digit', day: '2-digit' }).replace(/\//g, '');

            let total = 0;
            const rows = templateBody.getElementsByTagName('tr');
            for (let row of rows) {
                const amount = parseInt(row.cells[2].textContent) || 0;
                total += amount;
            }
            const tax = total * 0.1;
            const totalAmount = total + tax;

            templateTotalAmount.textContent = totalAmount;
            templateSubtotal.textContent = total;
            templateTax.textContent = tax;
            templateTaxable.textContent = total;
            templateTaxValue.textContent = tax;
            templateTotalAmount2.textContent = totalAmount;

            window.print();
        }

        function editInvoice() {
            alert('Edit functionality to be implemented');
        }
    </script>
</body>
</html>