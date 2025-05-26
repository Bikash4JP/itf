<?php
// Define the path to vendor/autoload.php
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

// Check if the autoload file exists
if (!file_exists($autoloadPath)) {
    die("Error: Composer autoload file not found at: $autoloadPath. Please ensure you ran 'composer require phpoffice/phpspreadsheet:1.29.0' in the project root directory (/home/it-future/www/itf).");
}

// Require the autoload file
require $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

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
    die(json_encode(['status' => 'error', 'message' => 'Log directory is not writable']));
}

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
    // Check for null, empty string, or whitespace
    if ($dateString === null || trim($dateString) === '') {
        return null; // Return NULL for empty or null values
    }

    // Replace slashes with dashes for consistency
    $dateString = str_replace('/', '-', $dateString);

    // Try to create a DateTime object to validate the date
    try {
        $date = new DateTime($dateString);
        return $date->format('Y-m-d'); // Return in YYYY-MM-DD format
    } catch (Exception $e) {
        return null; // Return NULL if the date is invalid
    }
}

// Sanitize inputs using htmlspecialchars and validate dates
$fullname = isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8') : '';
$furigana = isset($_POST['furigana']) ? htmlspecialchars($_POST['furigana'], ENT_QUOTES, 'UTF-8') : '';
$roman_name = isset($_POST['roman_name']) ? htmlspecialchars($_POST['roman_name'], ENT_QUOTES, 'UTF-8') : '';
$nationality = isset($_POST['nationality']) ? htmlspecialchars($_POST['nationality'], ENT_QUOTES, 'UTF-8') : '';
$custom_nationality = isset($_POST['custom_nationality']) ? htmlspecialchars($_POST['custom_nationality'], ENT_QUOTES, 'UTF-8') : '';
$nationality = ($nationality === 'その他' && !empty($custom_nationality)) ? $custom_nationality : $nationality;
$gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender'], ENT_QUOTES, 'UTF-8') : '';
$religion = isset($_POST['religion']) ? htmlspecialchars($_POST['religion'], ENT_QUOTES, 'UTF-8') : '';
$dob = isset($_POST['dob']) ? htmlspecialchars($_POST['dob'], ENT_QUOTES, 'UTF-8') : null;
$dob = validateAndReformatDate($dob); // Validate and reformat dob
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
$passport_expiry = validateAndReformatDate($passport_expiry); // Validate and reformat passport_expiry
$migration_history = isset($_POST['migration_history']) ? filter_var($_POST['migration_history'], FILTER_SANITIZE_NUMBER_INT) : 0;
$recent_migration_entry = isset($_POST['recent_migration_entry']) ? htmlspecialchars($_POST['recent_migration_entry'], ENT_QUOTES, 'UTF-8') : null;
$recent_migration_entry = validateAndReformatDate($recent_migration_entry); // Validate and reformat
$recent_migration_exit = isset($_POST['recent_migration_exit']) ? htmlspecialchars($_POST['recent_migration_exit'], ENT_QUOTES, 'UTF-8') : null;
$recent_migration_exit = validateAndReformatDate($recent_migration_exit); // Validate and reformat
$residency_status = isset($_POST['residency_status']) ? htmlspecialchars($_POST['residency_status'], ENT_QUOTES, 'UTF-8') : null;
$residency_expiry = isset($_POST['residency_expiry']) ? htmlspecialchars($_POST['residency_expiry'], ENT_QUOTES, 'UTF-8') : null;
$residency_expiry = validateAndReformatDate($residency_expiry); // Validate and reformat
$self_intro = isset($_POST['self_intro']) ? htmlspecialchars($_POST['self_intro'], ENT_QUOTES, 'UTF-8') : '';
$motivation = isset($_POST['motivation']) ? htmlspecialchars($_POST['motivation'], ENT_QUOTES, 'UTF-8') : '';
$job_preference = isset($_POST['job_preference']) ? htmlspecialchars($_POST['job_preference'], ENT_QUOTES, 'UTF-8') : null;
$job_id = isset($_POST['job_id']) ? filter_var($_POST['job_id'], FILTER_VALIDATE_INT) : false;

// Log all date fields for debugging
file_put_contents($success_log, "Date fields before insertion - dob: " . var_export($dob, true) . ", passport_expiry: " . var_export($passport_expiry, true) . ", recent_migration_entry: " . var_export($recent_migration_entry, true) . ", recent_migration_exit: " . var_export($recent_migration_exit, true) . ", residency_expiry: " . var_export($residency_expiry, true) . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Map marital_status to database ENUM values
$marital_status = '';
if ($marital_status_form === '無し') {
    $marital_status = 'Single';
} elseif ($marital_status_form === '有り（子供あり)' || $marital_status_form === '有り（子供なし)') {
    $marital_status = 'Married';
} else {
    $marital_status = 'Single'; // Default to Single if unexpected value
}
file_put_contents($success_log, "Mapped marital_status '$marital_status_form' to '$marital_status' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Validate job_id
if ($job_id === false || $job_id <= 0) {
    file_put_contents($error_log, "Invalid job_id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Error: job_id is missing or invalid. Please ensure the form is accessed with a valid job_id (e.g., ?job_id=13).");
}

// Validate required fields
if (empty($fullname) || empty($furigana) || empty($roman_name) || empty($nationality) || empty($gender) || empty($dob) || empty($marital_status) || empty($postal_code) || empty($prefecture) || empty($city_ward) || empty($street_address) || empty($phone) || empty($email) || empty($passport_have) || empty($self_intro) || empty($motivation)) {
    $missingFields = [];
    if (empty($fullname)) $missingFields[] = "fullname";
    if (empty($furigana)) $missingFields[] = "furigana";
    if (empty($roman_name)) $missingFields[] = "roman_name";
    if (empty($nationality)) $missingFields[] = "nationality";
    if (empty($gender)) $missingFields[] = "gender";
    if (empty($dob)) $missingFields[] = "dob";
    if (empty($marital_status)) $missingFields[] = "marital_status";
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
        // Validate required education dates
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
        // Validate required work experience dates
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

$photo_path = null; // To store the path of the uploaded photo
foreach ($upload_types as $field => $type) {
    if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) {
        if ($field === 'certificates') {
            // Handle multiple certificate files
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
            // Handle single file
            if ($_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file_path = $upload_dir . uniqid() . '_' . basename($_FILES[$field]['name']);
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $file_path)) {
                    $uploads[] = ['type' => $type, 'path' => $file_path];
                    // Store the photo path if this is the photo upload
                    if ($field === 'photo') {
                        $photo_path = $file_path;
                    }
                }
            }
        }
    }
}

// Ensure photo is uploaded (required field)
$photo_uploaded = false;
foreach ($uploads as $upload) {
    if ($upload['type'] === 'Photo') {
        $photo_uploaded = true;
        break;
    }
}
if (!$photo_uploaded) {
    $error_message = "Photo upload is required.";
    file_put_contents($error_log, "$error_message at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die($error_message);
}

$uploads_json = json_encode($uploads);

// Insert applicant data using named parameters
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

// Load the template file and fill user data
try {
    // Path to the template file
    $templateFile = dirname(__DIR__) . '/templates/template.xlsx';

    // Check if the template file exists
    if (!file_exists($templateFile)) {
        file_put_contents($error_log, "Template file not found at: $templateFile at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        die("Error: Template file not found at: $templateFile. Please ensure the template.xlsx file exists in the templates/ directory.");
    }

    // Load the template
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templateFile);
    $sheet = $spreadsheet->getActiveSheet();

    // Fill Header (Filled Date)
    $sheet->setCellValue('AC2', date('Y'));  // Year (merged AC2:AD2)
    $sheet->setCellValue('AF2', date('m'));  // Month
    $sheet->setCellValue('AH2', date('d'));  // Day

    // Fill Personal Details
    $sheet->setCellValue('H3', $fullname);  // Full Name (merged H3:T3)
    $sheet->setCellValue('H4', $furigana);  // Katakana Name (merged H4:T4)
    $sheet->setCellValue('H5', $dob ? date('Y', strtotime($dob)) : '-');  // Date of Birth Year (merged H5:I5)
    $sheet->setCellValue('K5', $dob ? date('m', strtotime($dob)) : '-');  // Date of Birth Month (merged K5:L5)
    $sheet->setCellValue('N5', $dob ? date('d', strtotime($dob)) : '-');  // Date of Birth Day (merged N5:O5)
    $sheet->setCellValue('Q5', $dob ? (date('Y') - date('Y', strtotime($dob))) : '-');  // Age (merged Q5:R5)
    $sheet->setCellValue('H6', $birth_place ? $birth_place : '-');  // Birthplace (merged H6:Q6)
    $sheet->mergeCells('E7:H7');  // Merge cells for Postal Code
    $sheet->setCellValue('E7', $postal_code ? $postal_code : '-');  // Postal Code (merged E7:H7)
    $sheet->mergeCells('B8:T8');  // Merge cells for Address
    $sheet->setCellValue('B8', $address ? $address : '-');  // Address (merged B8:T8)
    $sheet->setCellValue('X3', $nationality ? $nationality : '-');  // Nationality (merged X3:AC3)
    $sheet->setCellValue('X4', $gender === 'Male' ? '男性' : ($gender === 'Female' ? '女性' : 'その他'));  // Gender (merged X4:AC4)
    $sheet->setCellValue('X5', $religion ? $religion : '-');  // Religion (merged X5:AC5)
    $sheet->setCellValue('X6', $marital_status === 'Single' ? '無し' : ($marital_status === 'Married' ? '有り' : '-'));  // Marital Status (merged X6:AC6)
    $sheet->setCellValue('X7', $phone ? $phone : '-');  // Phone (merged X7:AC7)
    $sheet->setCellValue('X8', $email ? $email : '-');  // Email (merged X8:AC8)
    $sheet->setCellValue('H9', $height_cm ? $height_cm . ' cm' : '-');  // Height (merged H9:K9)
    $sheet->setCellValue('R9', $weight_kg ? $weight_kg . ' kg' : '-');  // Weight (merged R9:U9)

    // Fill Photo (merged AD3:AI8)
    if ($photo_path) {
        $drawing = new Drawing();
        $drawing->setPath($photo_path);
        $drawing->setCoordinates('AD3');  // Top-left cell of the merged range AD3:AI8
        $drawing->setWidth(150);  // Adjust width to cover columns AD to AI
        $drawing->setHeight(150);  // Adjust height to cover rows 3 to 8
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);
    }

    // Fill Passport and Residency
    $sheet->setCellValue('H10', $passport_have === 'Yes' ? '有り' : '無し');  // Passport Possession (merged H10:K10)
    $sheet->setCellValue('P10', $passport_expiry ? date('Y', strtotime($passport_expiry)) : '-');  // Passport Expiry Year (merged P10:Q10)
    $sheet->setCellValue('S10', $passport_expiry ? date('m', strtotime($passport_expiry)) : '-');  // Passport Expiry Month
    $sheet->setCellValue('U10', $passport_expiry ? date('d', strtotime($passport_expiry)) : '-');  // Passport Expiry Day
    $sheet->setCellValue('AB10', $passport_number ? $passport_number : '-');  // Passport Number (merged AB10:AI10)
    $sheet->setCellValue('H11', $migration_history ? $migration_history : '0');  // Migration History (H11)
    $sheet->setCellValue('T11', $recent_migration_entry ? date('Y', strtotime($recent_migration_entry)) : '-');  // Recent Migration Entry Year (merged T11:U11)
    $sheet->setCellValue('W11', $recent_migration_entry ? date('m', strtotime($recent_migration_entry)) : '-');  // Recent Migration Entry Month
    $sheet->setCellValue('Y11', $recent_migration_entry ? date('d', strtotime($recent_migration_entry)) : '-');  // Recent Migration Entry Day
    $sheet->setCellValue('AC11', $recent_migration_exit ? date('Y', strtotime($recent_migration_exit)) : '-');  // Recent Migration Exit Year (merged AC11:AD11)
    $sheet->setCellValue('AF11', $recent_migration_exit ? date('m', strtotime($recent_migration_exit)) : '-');  // Recent Migration Exit Month
    $sheet->setCellValue('AH11', $recent_migration_exit ? date('d', strtotime($recent_migration_exit)) : '-');  // Recent Migration Exit Day
    $sheet->setCellValue('H12', $residency_status ? $residency_status : '-');  // Residency Status (merged H12:K12)
    $sheet->setCellValue('T12', $residency_expiry ? date('Y', strtotime($residency_expiry)) : '-');  // Residency Expiry Year (merged T12:U12)
    $sheet->setCellValue('W12', $residency_expiry ? date('m', strtotime($residency_expiry)) : '-');  // Residency Expiry Month
    $sheet->setCellValue('Y12', $residency_expiry ? date('d', strtotime($residency_expiry)) : '-');  // Residency Expiry Day
    $sheet->setCellValue('AC12', $recent_migration_exit ? date('Y', strtotime($recent_migration_exit)) : '-');  // Exit Year (merged AC12:AD12)
    $sheet->setCellValue('AF12', $recent_migration_exit ? date('m', strtotime($recent_migration_exit)) : '-');  // Exit Month
    $sheet->setCellValue('AH12', $recent_migration_exit ? date('d', strtotime($recent_migration_exit)) : '-');  // Exit Day

    // Fill Education (Start at row 15, multiple entries at rows 15 to 19)
    $educationRows = [15, 16, 17, 18, 19];  // Rows 15 to 19 as specified
    foreach ($educationRows as $index => $row) {
        if (isset($education[$index])) {
            $edu = $education[$index];
            $sheet->setCellValue("B{$row}", $edu['join_date'] ? date('Y', strtotime($edu['join_date'])) : '');  // From Year (merged B15:C15)
            $sheet->setCellValue("E{$row}", $edu['join_date'] ? date('m', strtotime($edu['join_date'])) : '');  // From Month
            $sheet->setCellValue("H{$row}", $edu['leave_date'] ? date('Y', strtotime($edu['leave_date'])) : '');  // To Year (merged H15:I15)
            $sheet->setCellValue("K{$row}", $edu['leave_date'] ? date('m', strtotime($edu['leave_date'])) : '');  // To Month
            $sheet->setCellValue("M{$row}", $edu['institution_name'] ? $edu['institution_name'] : '');  // Institution Name (merged M15:AD15)
            $sheet->setCellValue("AE{$row}", $edu['major'] ? $edu['major'] : '');  // Major (merged AE15:AI15)
        } else {
            $sheet->setCellValue("B{$row}", '');
            $sheet->setCellValue("E{$row}", '');
            $sheet->setCellValue("H{$row}", '');
            $sheet->setCellValue("K{$row}", '');
            $sheet->setCellValue("M{$row}", '');
            $sheet->setCellValue("AE{$row}", '');
        }
    }

    // Fill Work Experience (Start at row 21, multiple entries at rows 21 to 24)
    $workRows = [21, 22, 23, 24];  // Rows 21 to 24 as specified
    foreach ($workRows as $index => $row) {
        if (isset($work_experience[$index])) {
            $work = $work_experience[$index];
            $sheet->setCellValue("B{$row}", $work['join_date'] ? date('Y', strtotime($work['join_date'])) : '');  // From Year (merged B21:C21)
            $sheet->setCellValue("E{$row}", $work['join_date'] ? date('m', strtotime($work['join_date'])) : '');  // From Month
            $sheet->setCellValue("H{$row}", $work['leave_date'] ? date('Y', strtotime($work['leave_date'])) : '');  // To Year (merged H21:I21)
            $sheet->setCellValue("K{$row}", $work['leave_date'] ? date('m', strtotime($work['leave_date'])) : '');  // To Month
            $sheet->setCellValue("M{$row}", $work['company_name'] ? $work['company_name'] : '');  // Org Name (merged M21:AD21)
            $sheet->setCellValue("AE{$row}", $work['job_role'] ? $work['job_role'] : '');  // Designation (merged AE21:AI21)
        } else {
            $sheet->setCellValue("B{$row}", '');
            $sheet->setCellValue("E{$row}", '');
            $sheet->setCellValue("H{$row}", '');
            $sheet->setCellValue("K{$row}", '');
            $sheet->setCellValue("M{$row}", '');
            $sheet->setCellValue("AE{$row}", '');
        }
    }

    // Fill Certifications (Start at row 27, multiple entries at rows 27 to 31)
    $certRows = [27, 28, 29, 30, 31];  // Rows 27 to 31 as specified
    foreach ($certRows as $index => $row) {
        if (isset($certifications[$index])) {
            $cert = $certifications[$index];
            $sheet->setCellValue("B{$row}", $cert['date_obtained'] ? date('Y', strtotime($cert['date_obtained'])) : '');  // Year (merged B27:D27)
            $sheet->setCellValue("E{$row}", $cert['date_obtained'] ? date('m', strtotime($cert['date_obtained'])) : '');  // Month (merged E27:F27)
            $sheet->setCellValue("G{$row}", $cert['name'] ? $cert['name'] : '');  // Exam Name (merged G27:AI27)
        } else {
            $sheet->setCellValue("B{$row}", '');
            $sheet->setCellValue("E{$row}", '');
            $sheet->setCellValue("G{$row}", '');
        }
    }

    // Fill Self-PR, Motivation, and Preferences
    $sheet->setCellValue('B33', $self_intro ? $self_intro : '-');  // Self-Introduction (merged B33:AI37)
    $sheet->setCellValue('B39', $motivation ? $motivation : '-');  // Motivation (merged B39:AI39)
    $sheet->setCellValue('B45', $job_preference ? $job_preference : '-');  // Applicant's Preferences (merged B45:AI46)

    // Save the filled template
    $resume_dir = '/home/it-future/www/itf/resumes/';
    if (!file_exists($resume_dir)) {
        mkdir($resume_dir, 0775, true);
    }
    $resume_path = $resume_dir . $applicant_id . '_resume.xlsx';
    $writer = new Xlsx($spreadsheet);
    $writer->save($resume_path);

    // If successful, proceed to update the database
} catch (Exception $e) {
    file_put_contents($error_log, "Failed to load or save the template: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Failed to load or save the template: " . $e->getMessage());
}

// Update applicant with resume path
try {
    $stmt = $pdo->prepare("UPDATE applicant SET resume_path = :resume_path WHERE id = :applicant_id");
    $stmt->execute([
        'resume_path' => $resume_path,
        'applicant_id' => $applicant_id
    ]);
    file_put_contents($success_log, "Resume path updated for applicant ID $applicant_id at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
} catch (PDOException $e) {
    file_put_contents($error_log, "Failed to update resume path: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    die("Failed to update resume path: " . $e->getMessage());
}

// Redirect to confirmation page
header("Location: /confirmation.php?application_id=$applicant_id");
ob_end_clean();
exit;
?>