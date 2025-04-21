<?php
session_start();
if (!isset($_SESSION['application_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITF Recruitment - Education</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center text-theme-blue">ITF Recruitment - Apply Now</h1>
        <div class="progress mb-4">
            <div class="progress-bar bg-theme-orange" role="progressbar" style="width: 60%;" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">Step 3 of 5</div>
        </div>
        <div class="card">
            <div class="card-header bg-theme-orange text-white">
                <h3 class="text-theme-blue">学歴 (Education Details)</h3>
            </div>
            <div class="card-body">
                <form id="part3-form" action="php/save_part3.php" method="POST">
                    <!-- High School Section -->
                    <h5 class="text-theme-blue">高等学校 (High School)</h5>
                    <div class="mb-3">
                        <label for="high_school_name" class="form-label">学校名 (Institution Name)</label>
                        <input type="text" class="form-control" id="high_school_name" name="high_school_name">
                    </div>
                    <div class="mb-3">
                        <label for="high_school_major" class="form-label">専攻 (Major)</label>
                        <select class="form-control" id="high_school_major" name="high_school_major">
                            <option value="">選択してください</option>
                            <option value="Science">Science</option>
                            <option value="Arts">Arts</option>
                            <option value="Commerce">Commerce</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="high_school_status" class="form-label">ステータス (Status)</label>
                        <select class="form-control" id="high_school_status" name="high_school_status" onchange="toggleGraduatedDate('high_school')">
                            <option value="">選択してください</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Running">Running</option>
                            <option value="Dropout">Dropout</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="high_school_joining" class="form-label">入学日 (Joining Date)</label>
                        <input type="date" class="form-control" id="high_school_joining" name="high_school_joining">
                    </div>
                    <div class="mb-3">
                        <label for="high_school_graduated" class="form-label">卒業日 (Graduated Date)</label>
                        <input type="date" class="form-control" id="high_school_graduated" name="high_school_graduated" disabled>
                    </div>

                    <!-- Vocational School Section -->
                    <h5 class="text-theme-blue">専門学校 (Vocational School)</h5>
                    <div class="mb-3">
                        <label for="vocational_name" class="form-label">学校名 (Institution Name)</label>
                        <input type="text" class="form-control" id="vocational_name" name="vocational_name">
                    </div>
                    <div class="mb-3">
                        <label for="vocational_major" class="form-label">専攻 (Major)</label>
                        <select class="form-control" id="vocational_major" name="vocational_major">
                            <option value="">選択してください</option>
                            <option value="IT">IT</option>
                            <option value="Design">Design</option>
                            <option value="Business">Business</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="vocational_status" class="form-label">ステータス (Status)</label>
                        <select class="form-control" id="vocational_status" name="vocational_status" onchange="toggleGraduatedDate('vocational')">
                            <option value="">選択してください</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Running">Running</option>
                            <option value="Dropout">Dropout</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="vocational_joining" class="form-label">入学日 (Joining Date)</label>
                        <input type="date" class="form-control" id="vocational_joining" name="vocational_joining">
                    </div>
                    <div class="mb-3">
                        <label for="vocational_graduated" class="form-label">卒業日 (Graduated Date)</label>
                        <input type="date" class="form-control" id="vocational_graduated" name="vocational_graduated" disabled>
                    </div>

                    <!-- Bachelor/University Level Section -->
                    <h5 class="text-theme-blue">大学 (Bachelor/University Level)</h5>
                    <div class="mb-3">
                        <label for="university_name" class="form-label">大学名 (Institution Name)</label>
                        <input type="text" class="form-control" id="university_name" name="university_name">
                    </div>
                    <div class="mb-3">
                        <label for="university_major" class="form-label">専攻 (Major)</label>
                        <select class="form-control" id="university_major" name="university_major">
                            <option value="">選択してください</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business">Business</option>
                            <option value="Arts">Arts</option>
                            <option value="Science">Science</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="university_status" class="form-label">ステータス (Status)</label>
                        <select class="form-control" id="university_status" name="university_status" onchange="toggleGraduatedDate('university')">
                            <option value="">選択してください</option>
                            <option value="Running">Running</option>
                            <option value="Graduated">Graduated</option>
                            <option value="Dropout">Dropout</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="university_joining" class="form-label">入学日 (Joining Date)</label>
                        <input type="date" class="form-control" id="university_joining" name="university_joining">
                    </div>
                    <div class="mb-3">
                        <label for="university_graduated" class="form-label">卒業日 (Graduated Date)</label>
                        <input type="date" class="form-control" id="university_graduated" name="university_graduated" disabled>
                    </div>

                    <!-- JLPT Level -->
                    <h5 class="text-theme-blue">日本語能力試験 (JLPT Level)</h5>
                    <div class="mb-3">
                        <label for="jlpt_level" class="form-label">日本語能力試験 (JLPT Level)</label>
                        <select class="form-control" id="jlpt_level" name="jlpt_level">
                            <option value="">選択してください</option>
                            <option value="N1">N1</option>
                            <option value="N2">N2</option>
                            <option value="N3">N3</option>
                            <option value="N4">N4</option>
                            <option value="N5">N5</option>
                            <option value="なし">なし</option>
                        </select>
                    </div>

                    <!-- Additional Education Section -->
                    <h5 class="text-theme-blue">追加の教育 (Additional Education)</h5>
                    <div id="additional-education">
                        <!-- Dynamic fields will be added here via JavaScript -->
                    </div>
                    <button type="button" class="btn btn-theme-orange mb-3" onclick="addEducation()">教育を追加 (Add Education)</button>

                    <button type="submit" class="btn btn-theme-orange">次へ (Next)</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleGraduatedDate(section) {
            const status = document.getElementById(section + '_status').value;
            const graduatedField = document.getElementById(section + '_graduated');
            if (status === 'Graduated') {
                graduatedField.disabled = false;
                graduatedField.required = true;
            } else {
                graduatedField.disabled = true;
                graduatedField.required = false;
                graduatedField.value = '';
            }
        }

        function addEducation() {
            const container = document.getElementById('additional-education');
            const div = document.createElement('div');
            div.className = 'additional-education-entry mb-3';
            div.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">機関名 (Institution Name)</label>
                    <input type="text" class="form-control" name="additional_institution[]">
                </div>
                <div class="mb-3">
                    <label class="form-label">専攻 (Major)</label>
                    <select class="form-control" name="additional_major[]">
                        <option value="">選択してください</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Engineering">Engineering</option>
                        <option value="Business">Business</option>
                        <option value="Arts">Arts</option>
                        <option value="Science">Science</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">ステータス (Status)</label>
                    <select class="form-control" name="additional_status[]" onchange="toggleAdditionalGraduatedDate(this)">
                        <option value="">選択してください</option>
                        <option value="Graduated">Graduated</option>
                        <option value="Running">Running</option>
                        <option value="Dropout">Dropout</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">入学日 (Joining Date)</label>
                    <input type="date" class="form-control" name="additional_joining[]">
                </div>
                <div class="mb-3">
                    <label class="form-label">卒業日 (Graduated Date)</label>
                    <input type="date" class="form-control additional-graduated" name="additional_graduated[]" disabled>
                </div>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">削除 (Remove)</button>
                <hr>
            `;
            container.appendChild(div);
        }

        function toggleAdditionalGraduatedDate(selectElement) {
            const status = selectElement.value;
            const graduatedField = selectElement.parentElement.nextElementSibling.querySelector('.additional-graduated');
            if (status === 'Graduated') {
                graduatedField.disabled = false;
                graduatedField.required = true;
            } else {
                graduatedField.disabled = true;
                graduatedField.required = false;
                graduatedField.value = '';
            }
        }
    </script>
</body>
</html>