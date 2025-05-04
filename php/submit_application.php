<?php
// Define the path to vendor/autoload.php
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

// Check if the autoload file exists
if (!file_exists($autoloadPath)) {
    die("Error: Composer autoload file not found at: $autoloadPath. Please ensure you ran 'composer require phpoffice/phpspreadsheet:1.29.0' in the project root directory (C:\\xampp\\htdocs\\IDT).");
}

// Require the autoload file
require $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Database connection
try {
    $db = new PDO('mysql:host=localhost;dbname=itf', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $furigana = filter_input(INPUT_POST, 'furigana', FILTER_SANITIZE_STRING);
    $roman_name = filter_input(INPUT_POST, 'roman_name', FILTER_SANITIZE_STRING);
    $nationality = filter_input(INPUT_POST, 'nationality', FILTER_SANITIZE_STRING);
    $custom_nationality = filter_input(INPUT_POST, 'custom_nationality', FILTER_SANITIZE_STRING) ?: '';
    $nationality = ($nationality === 'その他' && !empty($custom_nationality)) ? $custom_nationality : $nationality;
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $birth_place = filter_input(INPUT_POST, 'birth_place', FILTER_SANITIZE_STRING);
    $marital_status = filter_input(INPUT_POST, 'marital_status', FILTER_SANITIZE_STRING);

    // Address fields are already concatenated in recruit.js
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $postal_code = filter_input(INPUT_POST, 'postal_code', FILTER_SANITIZE_STRING);

    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $height_cm = filter_input(INPUT_POST, 'height_cm', FILTER_SANITIZE_NUMBER_INT) ?: null;
    $weight_kg = filter_input(INPUT_POST, 'weight_kg', FILTER_SANITIZE_NUMBER_INT) ?: null;
    $passport_have = filter_input(INPUT_POST, 'passport_have', FILTER_SANITIZE_STRING);
    $passport_number = filter_input(INPUT_POST, 'passport_number', FILTER_SANITIZE_STRING) ?: null;
    $passport_expiry = filter_input(INPUT_POST, 'passport_expiry', FILTER_SANITIZE_STRING) ?: null;
    $migration_history = filter_input(INPUT_POST, 'migration_history', FILTER_SANITIZE_NUMBER_INT) ?: 0;
    $recent_migration_entry = filter_input(INPUT_POST, 'recent_migration_entry', FILTER_SANITIZE_STRING) ?: null;
    $recent_migration_exit = filter_input(INPUT_POST, 'recent_migration_exit', FILTER_SANITIZE_STRING) ?: null;
    $residency_status = filter_input(INPUT_POST, 'residency_status', FILTER_SANITIZE_STRING) ?: null;
    $residency_expiry = filter_input(INPUT_POST, 'residency_expiry', FILTER_SANITIZE_STRING) ?: null;
    $self_intro = filter_input(INPUT_POST, 'self_intro', FILTER_SANITIZE_STRING);
    $motivation = filter_input(INPUT_POST, 'motivation', FILTER_SANITIZE_STRING);
    $job_preference = filter_input(INPUT_POST, 'job_preference', FILTER_SANITIZE_STRING) ?: null;
    $job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);

    // Validate job_id
    if ($job_id === false || $job_id <= 0) {
        die("Error: job_id is missing or invalid. Please ensure the form is accessed with a valid job_id (e.g., ?job_id=13).");
    }

    // Validate required fields
    if (!$fullname || !$furigana || !$roman_name || !$nationality || !$gender || !$dob || !$marital_status || !$address || !$phone || !$email || !$passport_have || !$self_intro || !$motivation || !$postal_code) {
        $missingFields = [];
        if (!$fullname) $missingFields[] = "fullname";
        if (!$furigana) $missingFields[] = "furigana";
        if (!$roman_name) $missingFields[] = "roman_name";
        if (!$nationality) $missingFields[] = "nationality";
        if (!$gender) $missingFields[] = "gender";
        if (!$dob) $missingFields[] = "dob";
        if (!$marital_status) $missingFields[] = "marital_status";
        if (!$address) $missingFields[] = "address";
        if (!$phone) $missingFields[] = "phone";
        if (!$email) $missingFields[] = "email";
        if (!$passport_have) $missingFields[] = "passport_have";
        if (!$self_intro) $missingFields[] = "self_intro";
        if (!$motivation) $missingFields[] = "motivation";
        if (!$postal_code) $missingFields[] = "postal_code";
        die("Required fields are missing: " . implode(", ", $missingFields));
    }

    // Prepare education JSON
    $education = [];
    if (isset($_POST['institution_name']) && is_array($_POST['institution_name'])) {
        foreach ($_POST['institution_name'] as $i => $name) {
            $education[] = [
                'institution_name' => $name,
                'institution_address' => $_POST['institution_address'][$i] ?? '',
                'join_date' => $_POST['edu_join_date'][$i] ?? '',
                'leave_date' => $_POST['edu_leave_date'][$i] ?? '',
                'faculty' => $_POST['faculty'][$i] ?? '',
                'major' => $_POST['major'][$i] ?? '',
                'status' => $_POST['edu_status'][$i] ?? ''
            ];
        }
    }
    $education_json = json_encode($education);

    // Validate education (at least one entry required)
    if (empty($education)) {
        die("At least one education entry is required.");
    }

    // Prepare work experience JSON
    $work_experience = [];
    if (isset($_POST['company_name']) && is_array($_POST['company_name'])) {
        foreach ($_POST['company_name'] as $i => $name) {
            $work_experience[] = [
                'company_name' => $name,
                'company_address' => $_POST['company_address'][$i] ?? '',
                'business_type' => $_POST['business_type'][$i] ?? '',
                'job_role' => $_POST['job_role'][$i] ?? '',
                'join_date' => $_POST['exp_join_date'][$i] ?? '',
                'leave_date' => $_POST['exp_leave_date'][$i] ?? '',
                'current_status' => $_POST['exp_status'][$i] ?? ''
            ];
        }
    }
    $work_experience_json = json_encode($work_experience);

    // Prepare certifications JSON
    $certifications = [];
    if (isset($_POST['cert_name']) && is_array($_POST['cert_name'])) {
        foreach ($_POST['cert_name'] as $i => $name) {
            $certifications[] = [
                'type' => $_POST['cert_type'][$i] ?? '',
                'name' => $name,
                'score' => $_POST['cert_score'][$i] ?? '',
                'date_obtained' => $_POST['cert_date'][$i] ?? ''
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

    $photo_path = null; // To store the path of the uploaded photo
    foreach ($upload_types as $field => $type) {
        if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) {
            if ($field === 'certificates') {
                // Handle multiple certificate files
                if (is_array($_FILES[$field]['name'])) {
                    foreach ($_FILES[$field]['name'] as $key => $name) {
                        if ($_FILES[$field]['error'][$key] === UPLOAD_ERR_OK) {
                            $file_path = '../uploads/' . uniqid() . '_' . basename($name);
                            if (move_uploaded_file($_FILES[$field]['tmp_name'][$key], $file_path)) {
                                $uploads[] = ['type' => $type, 'path' => $file_path];
                            }
                        }
                    }
                }
            } else {
                // Handle single file
                if ($_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                    $file_path = '../uploads/' . uniqid() . '_' . basename($_FILES[$field]['name']);
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
        die("Photo upload is required.");
    }

    $uploads_json = json_encode($uploads);

    // Insert applicant data using named parameters
    try {
        $stmt = $db->prepare("
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
        $applicant_id = $db->lastInsertId();
    } catch (PDOException $e) {
        die("Failed to save applicant data: " . $e->getMessage());
    }

    // Load the template file and fill user data
    try {
        // Path to the template file
        $templateFile = dirname(__DIR__) . '/templates/template.xlsx';

        // Check if the template file exists
        if (!file_exists($templateFile)) {
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
        $resume_path = '../resumes/' . $applicant_id . '_resume.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($resume_path);

        // If successful, proceed to the confirmation page
    } catch (Exception $e) {
        die("Failed to load or save the template: " . $e->getMessage());
    }

    // Update applicant with resume path
    try {
        $stmt = $db->prepare("UPDATE applicant SET resume_path = :resume_path WHERE id = :applicant_id");
        $stmt->execute([
            'resume_path' => $resume_path,
            'applicant_id' => $applicant_id
        ]);
    } catch (PDOException $e) {
        die("Failed to update resume path: " . $e->getMessage());
    }

    // Redirect to confirmation page
    header("Location: confirmation.php?applicant_id=$applicant_id");
    exit;
} else {
    die("Invalid request method.");
}
?>