<?php
// Start output buffering
ob_start();

// Log folder path
$log_dir = "/home/it-future/www/itf/logs/";
$error_log = $log_dir . "submit_application_error.log";
$success_log = $log_dir . "submit_application_success.log";

// Check if log directory exists and is writable
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0775, true);
}
if (!is_writable($log_dir)) {
    die("Log directory is not writable. Please check permissions.");
}

// Include manually created autoload
require_once __DIR__ . '/../vendor/autoload.php'; // Path to manually created autoload.php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

// Include database connection
try {
    require_once __DIR__ . '/db_connect.php';
    if (!isset($pdo)) {
        throw new Exception("PDO object not initialized");
    }
    file_put_contents($success_log, "Database connection included successfully at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($error_log, "Include db_connect.php Failed: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// Function to validate and reformat date (YYYY/MM/DD to YYYY-MM-DD)
function validateAndReformatDate($dateString) {
    if ($dateString === null || trim($dateString) === '') {
        return null;
    }
    $dateString = str_replace('/', '-', $dateString);
    try {
        $date = new DateTime($dateString);
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}

// Sanitize inputs
$fullname = isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8') : '';
$furigana = isset($_POST['furigana']) ? htmlspecialchars($_POST['furigana'], ENT_QUOTES, 'UTF-8') : '';
$roman_name = isset($_POST['roman_name']) ? htmlspecialchars($_POST['roman_name'], ENT_QUOTES, 'UTF-8') : '';
$nationality = isset($_POST['nationality']) ? htmlspecialchars($_POST['nationality'], ENT_QUOTES, 'UTF-8') : '';
$custom_nationality = isset($_POST['custom_nationality']) ? htmlspecialchars($_POST['custom_nationality'], ENT_QUOTES, 'UTF-8') : '';
$nationality = ($nationality === 'その他' && !empty($custom_nationality)) ? $custom_nationality : $nationality;
$gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender'], ENT_QUOTES, 'UTF-8') : '';
$religion = isset($_POST['religion']) ? htmlspecialchars($_POST['religion'], ENT_QUOTES, 'UTF-8') : '';
$dob = isset($_POST['dob']) ? htmlspecialchars($_POST['dob'], ENT_QUOTES, 'UTF-8') : null;
$dob = validateAndReformatDate($dob);
$birth_place = isset($_POST['birth_place']) ? htmlspecialchars($_POST['birth_place'], ENT_QUOTES, 'UTF-8') : '';
$marital_status_form = isset($_POST['marital_status']) ? htmlspecialchars($_POST['marital_status'], ENT_QUOTES, 'UTF-8') : '';
$postal_code = isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code'], ENT_QUOTES, 'UTF-8') : '';
$prefecture = isset($_POST['prefecture']) ? htmlspecialchars($_POST['prefecture'], ENT_QUOTES, 'UTF-8') : '';
$city_ward = isset($_POST['city_ward']) ? htmlspecialchars($_POST['city_ward'], ENT_QUOTES, 'UTF-8') : '';
$street_address = isset($_POST['street_address']) ? htmlspecialchars($_POST['street_address'], ENT_QUOTES, 'UTF-8') : '';
$home_details = isset($_POST['home_details']) ? htmlspecialchars($_POST['home_details'], ENT_QUOTES, 'UTF-8') : '';
$address = $postal_code . ' ' . $prefecture . ' ' . $city_ward . ' ' . $street_address . ' ' . $home_details;
$phone = isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
$height_cm = isset($_POST['height_cm']) ? filter_var($_POST['height_cm'], FILTER_SANITIZE_NUMBER_INT) : null;
$weight_kg = isset($_POST['weight_kg']) ? filter_var($_POST['weight_kg'], FILTER_SANITIZE_NUMBER_INT) : null;
$passport_have = isset($_POST['passport_have']) ? htmlspecialchars($_POST['passport_have'], ENT_QUOTES, 'UTF-8') : '';
$passport_number = isset($_POST['passport_number']) ? htmlspecialchars($_POST['passport_number'], ENT_QUOTES, 'UTF-8') : null;
$passport_expiry = isset($_POST['passport_expiry']) ? htmlspecialchars($_POST['passport_expiry'], ENT_QUOTES, 'UTF-8') : null;
$passport_expiry = validateAndReformatDate($passport_expiry);
$migration_history = isset($_POST['migration_history']) ? filter_var($_POST['migration_history'], FILTER_SANITIZE_NUMBER_INT) : 0;
$recent_migration_entry = isset($_POST['recent_migration_entry']) ? htmlspecialchars($_POST['recent_migration_entry'], ENT_QUOTES, 'UTF-8') : null;
$recent_migration_entry = validateAndReformatDate($recent_migration_entry);
$recent_migration_exit = isset($_POST['recent_migration_exit']) ? htmlspecialchars($_POST['recent_migration_exit'], ENT_QUOTES, 'UTF-8') : null;
$recent_migration_exit = validateAndReformatDate($recent_migration_exit);
$residency_status = isset($_POST['residency_status']) ? htmlspecialchars($_POST['residency_status'], ENT_QUOTES, 'UTF-8') : null;
$residency_expiry = isset($_POST['residency_expiry']) ? htmlspecialchars($_POST['residency_expiry'], ENT_QUOTES, 'UTF-8') : null;
$residency_expiry = validateAndReformatDate($residency_expiry);
$self_intro = isset($_POST['self_intro']) ? htmlspecialchars($_POST['self_intro'], ENT_QUOTES, 'UTF-8') : '';
$motivation = isset($_POST['motivation']) ? htmlspecialchars($_POST['motivation'], ENT_QUOTES, 'UTF-8') : '';
$job_preference = isset($_POST['job_preference']) ? htmlspecialchars($_POST['job_preference'], ENT_QUOTES, 'UTF-8') : null;
$job_id = isset($_POST['job_id']) ? filter_var($_POST['job_id'], FILTER_VALIDATE_INT) : false;

// Validate job_id
if ($job_id === false || $job_id <= 0) {
    file_put_contents($error_log, "Invalid job_id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Error: job_id is missing or invalid. Please ensure the form is accessed with a valid job_id (e.g., ?job_id=13).");
}

// Validate required fields
if (empty($fullname) || empty($furigana) || empty($roman_name) || empty($nationality) || empty($gender) || empty($dob) || empty($marital_status_form) || empty($postal_code) || empty($prefecture) || empty($city_ward) || empty($street_address) || empty($phone) || empty($email) || empty($passport_have) || empty($self_intro) || empty($motivation)) {
    $missingFields = [];
    if (empty($fullname)) $missingFields[] = "fullname";
    if (empty($furigana)) $missingFields[] = "furigana";
    if (empty($roman_name)) $missingFields[] = "roman_name";
    if (empty($nationality)) $missingFields[] = "nationality";
    if (empty($gender)) $missingFields[] = "gender";
    if (empty($dob)) $missingFields[] = "dob";
    if (empty($marital_status_form)) $missingFields[] = "marital_status";
    if (empty($postal_code)) $missingFields[] = "postal_code";
    if (empty($prefecture)) $missingFields[] = "prefecture";
    if (empty($city_ward)) $missingFields[] = "city_ward";
    if (empty($street_address)) $missingFields[] = "street_address";
    if (empty($phone)) $missingFields[] = "phone";
    if (empty($email)) $missingFields[] = "email";
    if (empty($passport_have)) $missingFields[] = "passport_have";
    if (empty($self_intro)) $missingFields[] = "self_intro";
    if (empty($motivation)) $missingFields[] = "motivation";
    $error_message = "Required fields are missing: " . implode(", ", $missingFields);
    file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die($error_message);
}

// Validate required date fields and passport details
if ($passport_have === 'Yes') {
    if ($passport_expiry === null) {
        $error_message = "Passport expiry date is required when passport is available and must be a valid date.";
        file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        die($error_message);
    }
    if (empty($passport_number)) {
        $error_message = "Passport number is required when passport is available.";
        file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        die($error_message);
    }
}
if ($dob === null) {
    $error_message = "Date of birth is required and must be a valid date.";
    file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die($error_message);
}

// Prepare education JSON
$education = [];
if (isset($_POST['institution_name']) && is_array($_POST['institution_name'])) {
    foreach ($_POST['institution_name'] as $i => $name) {
        $edu_join_date = validateAndReformatDate($_POST['edu_join_date'][$i] ?? '');
        $edu_leave_date = validateAndReformatDate($_POST['edu_leave_date'][$i] ?? '');
        $education[] = [
            'institution_name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'institution_address' => htmlspecialchars($_POST['institution_address'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'join_date' => $edu_join_date,
            'leave_date' => $edu_leave_date,
            'faculty' => htmlspecialchars($_POST['faculty'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'major' => htmlspecialchars($_POST['major'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'status' => htmlspecialchars($_POST['edu_status'][$i] ?? '', ENT_QUOTES, 'UTF-8')
        ];
        if ($edu_join_date === null) {
            $error_message = "Education join date is required and must be a valid date for entry #" . ($i + 1) . ".";
            file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            die($error_message);
        }
    }
}
$education_json = json_encode($education);

// Validate education (at least one entry required)
if (empty($education)) {
    $error_message = "At least one education entry is required.";
    file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die($error_message);
}

// Prepare work experience JSON
$work_experience = [];
if (isset($_POST['company_name']) && is_array($_POST['company_name'])) {
    foreach ($_POST['company_name'] as $i => $name) {
        $exp_join_date = validateAndReformatDate($_POST['exp_join_date'][$i] ?? '');
        $exp_leave_date = validateAndReformatDate($_POST['exp_leave_date'][$i] ?? '');
        $work_experience[] = [
            'company_name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'company_address' => htmlspecialchars($_POST['company_address'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'business_type' => htmlspecialchars($_POST['business_type'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'job_role' => htmlspecialchars($_POST['job_role'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'join_date' => $exp_join_date,
            'leave_date' => $exp_leave_date,
            'current_status' => htmlspecialchars($_POST['exp_status'][$i] ?? '', ENT_QUOTES, 'UTF-8')
        ];
        if ($exp_join_date === null) {
            $error_message = "Work experience join date is required and must be a valid date for entry #" . ($i + 1) . ".";
            file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            die($error_message);
        }
    }
}
$work_experience_json = json_encode($work_experience);

// Prepare certifications JSON
$certifications = [];
if (isset($_POST['cert_name']) && is_array($_POST['cert_name'])) {
    foreach ($_POST['cert_name'] as $i => $name) {
        $cert_date = validateAndReformatDate($_POST['cert_date'][$i] ?? '');
        $certifications[] = [
            'type' => htmlspecialchars($_POST['cert_type'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'custom_skill' => htmlspecialchars($_POST['custom_skill'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'score' => htmlspecialchars($_POST['cert_score'][$i] ?? '', ENT_QUOTES, 'UTF-8'),
            'date_obtained' => $cert_date
        ];
    }
}
$certifications_json = json_encode($certifications);

// Handle file uploads and prepare uploads JSON
$uploads = [];
$upload_types = [
    'photo' => 'Photo',
    'passport_file' => 'Passport',
    'residence_card' => 'ResidenceCard',
    'skills_certificate' => 'SkillsCertificate',
    'certificates' => 'Certificate'
];

$upload_dir = '/home/it-future/www/itf/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0775, true);
}

$photo_path = null;
foreach ($upload_types as $field => $type) {
    if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) {
        if ($field === 'certificates') {
            if (is_array($_FILES[$field]['name'])) {
                foreach ($_FILES[$field]['name'] as $key => $name) {
                    if ($_FILES[$field]['error'][$key] === UPLOAD_ERR_OK) {
                        $file_path = $upload_dir . uniqid() . '_' . basename($name);
                        if (move_uploaded_file($_FILES[$field]['tmp_name'][$key], $file_path)) {
                            $uploads[] = ['type' => $type, 'path' => $file_path];
                        }
                    }
                }
            }
        } else {
            if ($_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_path = $upload_dir . uniqid() . '_' . basename($_FILES[$field]['name']);
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $file_path)) {
                    $uploads[] = ['type' => $type, 'path' => $file_path];
                    if ($field === 'photo') {
                        $photo_path = $file_path;
                    }
                }
            }
        }
    }
}

if (!$photo_path) {
    $error_message = "Photo upload is required.";
    file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die($error_message);
}

$uploads_json = json_encode($uploads);

// Insert applicant data
try {
    $stmt = $pdo->prepare("
        INSERT INTO applicant (
            job_id, fullname, furigana, roman_name, nationality, gender, religion, dob, birth_place, marital_status,
            address, postal_code, phone, email, height_cm, weight_kg, passport_have, passport_number, passport_expiry,
            migration_history, recent_migration_entry, recent_migration_exit, residency_status, residency_expiry,
            education, work_experience, certifications, self_intro, motivation, job_preference, uploads
        ) VALUES (
            :job_id, :fullname, :furigana, :roman_name, :nationality, :gender, :religion, :dob, :birth_place, :marital_status,
            :address, :postal_code, :phone, :email, :height_cm, :weight_kg, :passport_have, :passport_number, :passport_expiry,
            :migration_history, :recent_migration_entry, :recent_migration_exit, :residency_status, :residency_expiry,
            :education, :work_experience, :certifications, :self_intro, :motivation, :job_preference, :uploads
        )
    ");
    $marital_status = ($marital_status_form === '無し') ? 'Single' : 'Married';
    $stmt->execute([
        'job_id' => $job_id,
        'fullname' => $fullname,
        'furigana' => $furigana,
        'roman_name' => $roman_name,
        'nationality' => $nationality,
        'gender' => $gender,
        'religion' => $religion,
        'dob' => $dob,
        'birth_place' => $birth_place,
        'marital_status' => $marital_status,
        'address' => $address,
        'postal_code' => $postal_code,
        'phone' => $phone,
        'email' => $email,
        'height_cm' => $height_cm,
        'weight_kg' => $weight_kg,
        'passport_have' => $passport_have,
        'passport_number' => $passport_number,
        'passport_expiry' => $passport_expiry,
        'migration_history' => $migration_history,
        'recent_migration_entry' => $recent_migration_entry,
        'recent_migration_exit' => $recent_migration_exit,
        'residency_status' => $residency_status,
        'residency_expiry' => $residency_expiry,
        'education' => $education_json,
        'work_experience' => $work_experience_json,
        'certifications' => $certifications_json,
        'self_intro' => $self_intro,
        'motivation' => $motivation,
        'job_preference' => $job_preference,
        'uploads' => $uploads_json
    ]);
    $applicant_id = $pdo->lastInsertId();
    file_put_contents($success_log, "Applicant data saved successfully for ID $applicant_id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents($error_log, "Failed to save applicant data: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Failed to save applicant data: " . $e->getMessage());
}

// Generate Excel resume (for staff)
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Load template
$templatePath = __DIR__ . '/../templates/template.xlsx';
if (file_exists($templatePath)) {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
    $sheet = $spreadsheet->getActiveSheet();
} else {
    $sheet->setCellValue('A1', '個人情報');
    $sheet->setCellValue('A2', '氏名');
    $sheet->setCellValue('B2', $fullname);
    // Add more fields as per template structure (expand as needed)
}

// Save Excel file
$excel_path = '/home/it-future/www/itf/resumes/' . $applicant_id . '_resume.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($excel_path);

// Generate PDF using Dompdf
$dompdf = new Dompdf();
$html = '<h1>履歴書</h1>';
$html .= '<p><strong>氏名:</strong> ' . $fullname . '</p>';
$html .= '<p><strong>ふりがな:</strong> ' . $furigana . '</p>';
$html .= '<p><strong>国籍:</strong> ' . $nationality . '</p>';
$html .= '<p><strong>生年月日:</strong> ' . ($dob ? date('Y年m月d日', strtotime($dob)) : '-') . '</p>';
// Add more fields as needed (e.g., education, experience)
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdf_path = '/home/it-future/www/itf/resumes/' . $applicant_id . '_resume.pdf';
file_put_contents($pdf_path, $dompdf->output());

// Update applicant with resume paths
try {
    $stmt = $pdo->prepare("UPDATE applicant SET resume_path = :pdf_path, excel_path = :excel_path WHERE id = :applicant_id");
    $stmt->execute([
        'pdf_path' => $pdf_path,
        'excel_path' => $excel_path,
        'applicant_id' => $applicant_id
    ]);
    file_put_contents($success_log, "Resume paths updated for applicant ID $applicant_id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents($error_log, "Failed to update resume paths: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Failed to update resume paths: " . $e->getMessage());
}

// Redirect to confirmation page
header("Location: /php/application_success.php?job_id=$job_id&application_id=$applicant_id");
ob_end_clean();
exit;
?>