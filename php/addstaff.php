<?php
// php\addstaff.php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

// Database connection
require_once 'db_connect.php';

// Fetch unique values from database
function getUniqueValues($pdo, $column)
{
  $stmt = $pdo->prepare("SELECT DISTINCT $column FROM talents WHERE $column IS NOT NULL AND $column != ''");
  $stmt->execute();
  $options = $stmt->fetchAll(PDO::FETCH_COLUMN);
  // Debugging: Log the fetched values to verify
  error_log("Fetched values for $column: " . json_encode($options));
  return $options;
}

$genderOptions = getUniqueValues($pdo, '雇用者情報（性別）');
$nationalityOptions = getUniqueValues($pdo, '雇用者情報（国籍）');
$responsiblePersonOptions = getUniqueValues($pdo, '担当責任者');
$mainResponsiblePersonOptions = getUniqueValues($pdo, '正担当者');
$referrerOptions = getUniqueValues($pdo, '紹介元');
$residenceTypeOptions = getUniqueValues($pdo, '住居タイプ');
$managementFeeOptions = getUniqueValues($pdo, '管理費');
$referralFeeOptions = getUniqueValues($pdo, '紹介料');
$jlptOptions = getUniqueValues($pdo, 'JLPT状況');
$basicContractOptions = getUniqueValues($pdo, '基本契約書');
$entrustmentContractOptions = getUniqueValues($pdo, '委託契約書');
$companyContactOptions = getUniqueValues($pdo, '担当者（企業）');
$AcceptingOrganization = getUniqueValues($pdo, '受け入れ機関');

function calculateAge($dob)
{
  if (!$dob)
    return null;
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
  <link rel="stylesheet" href="../css/addstaff.css">
</head>

<body>
  <header>
    <div class="logo"><a href="https://it-future.jp/"><img src="../images/logo.png" alt="ITF Logo"></a></div>
    <nav>
      <ul>
        <li><a href="staffdb.php">ホーム</a></li>
        <li><a href="manage_posts.php">投稿を管理</a></li>
        <li><a href="logout.php">ログアウト</a></li>
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
            <label data-english="(Alphabet Name)">雇用者情報（アルファベット）</label>
            <input type="text" name="雇用者情報（アルファベット）">
          </div>
          <div>
            <label data-english="(Katakana Name)">雇用者情報（カタカナ）</label>
            <input type="text" name="雇用者情報（カタカナ）">
          </div>
          <div>
            <label data-english="(Date of Birth)">雇用者情報（生年月日）</label>
            <input type="text" name="雇用者情報（生年月日）" id="dobInput" placeholder="yyyy-mm-dd" oninput="formatDate(this)"
              maxlength="10" required>
          </div>
          <div>
            <label data-english="(Support Address)">支援者住所</label>
            <input type="text" name="支援者住所">
          </div>
          <div>
            <label data-english="(Contact 1)">連絡先①</label>
            <input type="text" name="連絡先①">
          </div>
          <div>
            <label data-english="(Gender)">雇用者情報（性別）</label>
            <select name="雇用者情報（性別）">
              <option value="">選択してください</option>
              <?php foreach ($genderOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_雇用者情報（性別）" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Nationality)">雇用者情報（国籍）</label>
            <select name="雇用者情報（国籍）">
              <option value="">選択してください</option>
              <?php foreach ($nationalityOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_雇用者情報（国籍）" style="display:none;" placeholder="New value">
          </div>
        </div>
      </div>

      <!-- Step 2: 会社情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label data-english="(Facility Name)">施設名（勤務先）</label>
            <select name="施設名（勤務先）" id="facilitySelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label data-english="(Joining Date)">入社日</label>
            <input type="text" name="入社日" placeholder="yyyy-mm-dd" oninput="formatDate(this)" maxlength="10">
          </div>
          <div>
            <label data-english="(Retirement Date)">支援退職日</label>
            <input type="text" name="支援退職日" placeholder="yyyy-mm-dd" oninput="formatDate(this)" maxlength="10">
          </div>
          <div>
            <label data-english="(Status)">状態</label>
            <select name="状態" id="stateSelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label data-english="(Area)">エリア</label>
            <select name="エリア" id="areaSelect">
              <option value="">選択してください</option>
            </select>
          </div>
          <div>
            <label data-english="(Acceptance Period)">受け入れ機関</label>
            <select name="受け入れ機関">
              <option value="">選択してください</option>
              <?php foreach ($AcceptingOrganization as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_受け入れ機関" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Institution ZIP)">受入機関（郵便番号）</label>
            <input type="text" name="受入機関（郵便番号）">
          </div>
          <div>
            <label data-english="(Institution Phone)">受入機関（電話番号）</label>
            <input type="text" name="受入機関（電話番号）">
          </div>
        </div>
      </div>

      <!-- Step 3: 追加情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label data-english="(Residence Permit No)">雇用者在留番号</label>
            <input type="text" name="雇用者在留番号">
          </div>
          <div>
            <label data-english="(Residence Permit Expiry)">雇用者在留期限</label>
            <input type="text" name="雇用者在留期限" placeholder="yyyy-mm-dd" oninput="formatDate(this)" maxlength="10">
          </div>
          <div>
            <label data-english="(Renewal Count)">更新回数</label>
            <input type="number" name="更新回数">
          </div>
          <div>
            <label data-english="(Management No)">管理番号</label>
            <input type="text" name="管理番号">
          </div>
          <div>
            <label data-english="(Responsible Person)">担当責任者</label>
            <select name="担当責任者">
              <option value="">選択してください</option>
              <?php foreach ($responsiblePersonOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_担当責任者" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Main Responsible)">正担当者</label>
            <select name="正担当者">
              <option value="">選択してください</option>
              <?php foreach ($mainResponsiblePersonOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_正担当者" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Referrer)">紹介元</label>
            <select name="紹介元">
              <option value="">選択してください</option>
              <?php foreach ($referrerOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_紹介元" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Residence Type)">住居タイプ</label>
            <select name="住居タイプ">
              <option value="">選択してください</option>
              <?php foreach ($residenceTypeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_住居タイプ" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(JLPT Status)">JLPT状況</label>
            <select name="JLPT状況">
              <option value="">選択してください</option>
              <?php foreach ($jlptOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_JLPT状況" style="display:none;" placeholder="New value">
          </div>
        </div>
      </div>

      <!-- Step 4: ビジネス情報 -->
      <div class="form-step">
        <div class="form-grid">
          <div>
            <label data-english="(Management Fee)">管理費</label>
            <select name="管理費">
              <option value="">選択してください</option>
              <?php foreach ($managementFeeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_管理費" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Referral Fee)">紹介料</label>
            <select name="紹介料">
              <option value="">選択してください</option>
              <?php foreach ($referralFeeOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_紹介料" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Basic Contract)">基本契約書</label>
            <select name="基本契約書">
              <option value="">選択してください</option>
              <?php foreach ($basicContractOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_基本契約書" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Entrustment Contract)">委託契約書</label>
            <select name="委託契約書">
              <option value="">選択してください</option>
              <?php foreach ($entrustmentContractOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_委託契約書" style="display:none;" placeholder="New value">
          </div>
          <div>
            <label data-english="(Company Contact)">担当者（企業）</label>
            <select name="担当者（企業）">
              <option value="">選択してください</option>
              <?php foreach ($companyContactOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
              <?php endforeach; ?>
              <option value="+">+</option>
            </select>
            <input type="text" name="new_担当者（企業）" style="display:none;" placeholder="New value">
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

    const columns = ['採用日時', '施設名（勤務先）', '管理番号', '担当者（企業）', '基本契約書', '委託契約書', '紹介元', '受入機関（郵便番号）', '受入機関（住所）', '請求書送付先', '受入機関（電話番号）', '担当責任者', '区分', '受入機関名（所属機関）', '雇用者情報（アルファベット）', '雇用者情報（カタカナ）', '雇用者情報（性別）', '雇用者情報（国籍）', '雇用者情報（生年月日）', '年齢', '雇用者在留番号', '雇用者在留期限', '更新回数', 'X', '入社日', '在留カード最初発行日', '支援退職日', '状態', '管理費', '紹介料', '住居タイプ', '不動産会社', '不動産連絡先', '支援者住所', '連絡先①', 'AJ', 'AK', 'AL', 'AM', '支援者の家賃', '共益費', 'AP', '満了時期', '備考欄', '正担当者', 'JLPT状況', 'エリア', '受け入れ機関', '紹介手数料', '四半期', '介護福祉士合格し卒業の方'];

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

    function formatDate(input) {
      let value = input.value.replace(/\D/g, '');
      if (value.length > 8) value = value.slice(0, 8);
      if (value.length >= 4) value = value.slice(0, 4) + '-' + value.slice(4);
      if (value.length >= 7) value = value.slice(0, 7) + '-' + value.slice(7);
      input.value = value;
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
      progress.style.width = ((step + 1) / steps.length) * 100 + "%";
      document.getElementById("prevBtn").style.display = step > 0 ? "inline-block" : "none";
      document.getElementById("nextBtn").textContent = step === steps.length - 1 ? "送信" : "次へ";
    }

    function nextStep() {
      if (currentStep < steps.length - 1) {
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

    function checkDuplicate(name, company, dob, phone) {
      return new Promise((resolve) => {
        fetch('search.php?query=' + encodeURIComponent(name + ' ' + company + ' ' + dob + ' ' + phone))
          .then(response => response.json())
          .then(data => {
            resolve(data.length > 0);
          })
          .catch(() => resolve(false));
      });
    }

    function submitForm() {
      const formData = new FormData(document.getElementById('staffForm'));
      let values = columns.map(col => {
        if (col === '年齢') {
          return calculateAge();
        }
        // Handle manually entered values from + option
        const newField = 'new_' + col;
        const newValue = formData.get(newField);
        return newValue !== null && newValue !== '' ? newValue : formData.get(col) || '';
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
            alert('Successfully stored in database, try to search from main page for verification');
            window.location.href = 'https://it-future.jp/php/staffdb.php';
          }
        })
        .catch(error => alert('エラー: ' + error.message));
    }

    document.querySelectorAll('select').forEach(select => {
      select.addEventListener('change', (e) => {
        const newInput = e.target.nextElementSibling;
        if (e.target.value === '+') {
          newInput.style.display = 'block';
          e.target.name = '';
          newInput.name = 'new_' + e.target.name.replace('new_', '');
        } else {
          newInput.style.display = 'none';
          e.target.name = e.target.name.replace('new_', '');
        }
      });
    });

    showStep(currentStep);
    fetchData();
  </script>
</body>

</html>