<?php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Database connection
require_once 'db_connect.php';

// Fetch unique values from database
function getUniqueValues($pdo, $column) {
    $stmt = $pdo->prepare("SELECT DISTINCT $column FROM talents WHERE $column IS NOT NULL AND $column != ''");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$genderOptions = getUniqueValues($pdo, '雇用者情報（性別）');
$nationalityOptions = getUniqueValues($pdo, '雇用者情報（国籍）');
$responsiblePersonOptions = getUniqueValues($pdo, '担当責任者');
$mainResponsiblePersonOptions = getUniqueValues($pdo, '正担当者');
$referrerOptions = getUniqueValues($pdo, '紹介元');
$residenceTypeOptions = getUniqueValues($pdo, '住居タイプ');
$managementFeeOptions = getUniqueValues($pdo, '管理費');
$referralFeeOptions = getUniqueValues($pdo, '紹介料');

function calculateAge($dob) {
    if (!$dob) return null;
    $dobDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dobDate)->y;
    return $age;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>スタッフ情報フォーム</title>
  <link rel="stylesheet" href="../css/staffdb.css">
  <link rel="stylesheet" href="../css/search.css">
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background: #fff;
      margin: 0;
      padding: 20px;
    }

    .form-container {
      width: 70%;
      margin: auto;
      background: #fff;
      padding: 20px 40px;
      border-radius: 8px;
      box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
    }

    .step-header {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .step-header .arrow {
      font-size: 22px;
      margin-right: 8px;
      color: #000;
    }

    .step-title {
      font-size: 18px;
      font-weight: bold;
      color: #4169e1;
      padding: 5px 15px;
      background: #f5f7fc;
      border-radius: 4px;
    }

    .progress-bar {
      width: 100%;
      height: 4px;
      background: #ddd;
      margin: 15px 0 25px;
      border-radius: 2px;
      overflow: hidden;
    }

    .progress {
      height: 100%;
      width: 0%;
      background: #4169e1;
      transition: width 0.3s ease;
    }

    .form-step {
      display: none;
    }

    .form-step.active {
      display: block;
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-size: 14px;
    }

    input, select {
      width: 100%;
      padding: 8px;
      border: 1px solid #1e90ff;
      border-radius: 6px;
      font-size: 14px;
      outline: none;
    }

    .form-navigation {
      text-align: center;
      margin-top: 20px;
    }

    .btn {
      padding: 8px 18px;
      margin: 0 5px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
    }

    .btn-next {
      background: #4169e1;
      color: white;
    }

    .btn-back {
      background: #ccc;
      color: #000;
    }

    .btn:hover {
      opacity: 0.9;
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
        <li><a href="php/search.php" class="active">検索</a></li>
        <li><a href="php/logout.php">ログアウト</a></li>
      </ul>
    </nav>
  </header>
  <div class="form-container">
    <!-- Step Header -->
    <div class="step-header">
      <span class="arrow">»</span>
      <span class="step-title" id="stepTitle">個人情報</span>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar">
      <div class="progress" id="progress"></div>
    </div>

    <!-- Form -->
    <form id="staffForm" action="search.php" method="POST">
      <!-- Step 1: 個人情報 -->
      <div class="form-step active">
        <div class="form-grid">
          <div>
            <label>雇用者情報（アルファベット）</label>
            <input type="text" name="雇用者情報（アルファベット）">
          </div>
          <div>
            <label>雇用者情報（カタカナ）</label>
            <input type="text" name="雇用者情報（カタカナ）">
          </div>
          <div>
            <label>雇用者情報（生年月日）</label>
            <input type="date" name="雇用者情報（生年月日）" id="dobInput" required>
          </div>
          <div>
            <label>支援者住所</label>
            <input type="text" name="支援者住所">
          </div>
          <div>
            <label>連絡先①</label>
            <input type="text" name="連絡先①">
          </div>
          <div>
            <label>雇用者情報（性別）</label>
            <select name="雇用者情報（性別）">
              <option value="">選択してください</option>
              <?php foreach ($genderOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>雇用者情報（国籍）</label>
            <select name="雇用者情報（国籍）">
              <option value="">選択してください</option>
              <?php foreach ($nationalityOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Step 2: 会社情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label>施設名（勤務先）</label>
            <select name="施設名（勤務先）" id="facilitySelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label>入社日</label>
            <input type="date" name="入社日">
          </div>
          <div>
            <label>支援退職日</label>
            <input type="date" name="支援退職日">
          </div>
          <div>
            <label>状態</label>
            <select name="状態" id="stateSelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label>エリア</label>
            <select name="エリア" id="areaSelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label>受け入れ期間</label>
            <input type="text" name="受け入れ期間">
          </div>
          <div>
            <label>受入機関（郵便番号）</label>
            <input type="text" name="受入機関（郵便番号）">
          </div>
          <div>
            <label>受入機関（電話番号）</label>
            <input type="text" name="受入機関（電話番号）">
          </div>
        </div>
      </div>

      <!-- Step 3: 追加情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label>雇用者在留番号</label>
            <input type="text" name="雇用者在留番号">
          </div>
          <div>
            <label>雇用者在留期限</label>
            <input type="date" name="雇用者在留期限">
          </div>
          <div>
            <label>更新回数</label>
            <input type="number" name="更新回数">
          </div>
          <div>
            <label>管理番号</label>
            <input type="text" name="管理番号">
          </div>
          <div>
            <label>担当責任者</label>
            <select name="担当責任者">
              <option value="">選択してください</option>
              <?php foreach ($responsiblePersonOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>正担当者</label>
            <select name="正担当者">
              <option value="">選択してください</option>
              <?php foreach ($mainResponsiblePersonOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>紹介元</label>
            <select name="紹介元">
              <option value="">選択してください</option>
              <?php foreach ($referrerOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>住居タイプ</label>
            <select name="住居タイプ">
              <option value="">選択してください</option>
              <?php foreach ($residenceTypeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>JLPT状況</label>
            <input type="text" name="JLPT状況">
          </div>
        </div>
      </div>

      <!-- Step 4: ビジネス情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label>管理費</label>
            <select name="管理費">
              <option value="">選択してください</option>
              <?php foreach ($managementFeeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>紹介料</label>
            <select name="紹介料">
              <option value="">選択してください</option>
              <?php foreach ($referralFeeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label>基本契約書</label>
            <input type="text" name="基本契約書">
          </div>
          <div>
            <label>委託契約書</label>
            <input type="text" name="委託契約書">
          </div>
          <div>
            <label>担当者（企業）</label>
            <input type="text" name="担当者（企業）">
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="form-navigation">
        <button type="button" class="btn btn-back" id="prevBtn" onclick="prevStep()" style="display:none;">戻る</button>
        <button type="button" class="btn btn-next" id="nextBtn" onclick="nextStep()">次へ</button>
      </div>
    </form>
  </div>

  <script>
    const steps = document.querySelectorAll(".form-step");
    const progress = document.getElementById("progress");
    const stepTitle = document.getElementById("stepTitle");
    const titles = ["個人情報", "会社情報", "追加情報", "ビジネス情報"];
    const dobInput = document.getElementById("dobInput");

    let currentStep = 0;
    let workers = [];

    const columns = ['採用日時', '施設名（勤務先）', '管理番号', '担当者（企業）', '基本契約書', '委託契約書', '紹介元', '受入機関（郵便番号）', '受入機関（住所）', '請求書送付先', '受入機関（電話番号）', '担当責任者', '区分', '受入機関名（所属機関）', '雇用者情報（アルファベット）', '雇用者情報（カタカナ）', '雇用者情報（性別）', '雇用者情報（国籍）', '雇用者情報（生年月日）', '年齢', '雇用者在留番号', '雇用者在留期限', '更新回数', 'X', '入社日', '在留カード最初発行日', '支援退職日', '状態', '管理費', '紹介料', '住居タイプ', '不動産会社', '不動産連絡先', '支援者住所', '連絡先①', 'AJ', 'AK', 'AL', 'AM', '支援者の家賃', '共益費', 'AP', '満了時期', '備考欄', '正担当者', 'JLPT状況', 'エリア', '受け入れ期間', '紹介手数料', '四半期', '介護福祉士合格し卒業の方'];

    function fetchData() {
      fetch('search.php?query=')
        .then(response => response.json())
        .then(data => {
          if (Array.isArray(data)) {
            workers = data;
            populateSelects();
          }
        })
        .catch(error => console.error('Error:', error));
    }

    function getUniqueValues(header) {
      return [...new Set(workers.map(w => w[header] || '').filter(v => v))];
    }

    function populateSelects() {
      const facilitySelect = document.getElementById('facilitySelect');
      const stateSelect = document.getElementById('stateSelect');
      const areaSelect = document.getElementById('areaSelect');

      const uniqueFacilities = getUniqueValues('施設名（勤務先）');
      facilitySelect.innerHTML = '<option value="">選択してください</option>' + uniqueFacilities.map(v => `<option value="${v}">${v}</option>`).join('');

      const uniqueStates = getUniqueValues('状態');
      stateSelect.innerHTML = '<option value="">選択してください</option>' + uniqueStates.map(v => `<option value="${v}">${v}</option>`).join('');

      const uniqueAreas = getUniqueValues('エリア');
      areaSelect.innerHTML = '<option value="">選択してください</option>' + uniqueAreas.map(v => `<option value="${v}">${v}</option>`).join('');
    }

    function calculateAge() {
      const dob = dobInput.value;
      if (dob) {
        const dobDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - dobDate.getFullYear();
        const monthDiff = today.getMonth() - dobDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dobDate.getDate())) {
          age--;
        }
        return age;
      }
      return null;
    }

    function showStep(step) {
      steps.forEach((s, i) => {
        s.classList.toggle("active", i === step);
      });
      stepTitle.textContent = titles[step];
      progress.style.width = ((step+1) / steps.length) * 100 + "%";
      document.getElementById("prevBtn").style.display = step > 0 ? "inline-block" : "none";
      document.getElementById("nextBtn").textContent = step === steps.length-1 ? "送信" : "次へ";
    }

    function nextStep() {
      if (currentStep < steps.length-1) {
        currentStep++;
        showStep(currentStep);
      } else {
        submitForm();
      }
    }

    function prevStep() {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
      }
    }

    function submitForm() {
      const formData = new FormData(document.getElementById('staffForm'));
      let values = columns.map(col => {
        if (col === '年齢') {
          return calculateAge();
        }
        return formData.get(col) || '';
      });
      const name = formData.get('雇用者情報（アルファベット）') || '';
      fetch('search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          'add': '1',
          name: name,
          values: JSON.stringify(values)
        }).toString()
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('エラー: ' + data.error);
        } else {
          alert('データベースに正常に保存されました。確認のため、メインページから検索してください。');
          window.location.href = 'https://it-future.jp/php/searcch.php';
        }
      })
      .catch(error => alert('エラー: ' + error.message));
    }

    showStep(currentStep);
    fetchData();
  </script>
</body>
</html>