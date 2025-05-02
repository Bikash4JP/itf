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
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $religion = filter_input(INPUT_POST, 'religion', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $birth_place = filter_input(INPUT_POST, 'birth_place', FILTER_SANITIZE_STRING);
    $marital_status = filter_input(INPUT_POST, 'marital_status', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
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
        die("Error: job_id is missing or invalid. Please ensure the form is accessed with a valid job_id (e.g., ?job_id=1).");
    }

    // Validate required fields
    if (!$fullname || !$furigana || !$roman_name || !$nationality || !$gender || !$dob || !$marital_status || !$address || !$phone || !$email || !$passport_have || !$self_intro || !$motivation) {
        die("Required fields are missing.");
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
                address, phone, email, height_cm, weight_kg, passport_have, passport_number, passport_expiry,
                migration_history, recent_migration_entry, recent_migration_exit, residency_status, residency_expiry,
                education, work_experience, certifications, self_intro, motivation, job_preference, uploads
            ) VALUES (
                :job_id, :fullname, :furigana, :roman_name, :nationality, :gender, :religion, :dob, :birth_place, :marital_status,
                :address, :phone, :email, :height_cm, :weight_kg, :passport_have, :passport_number, :passport_expiry,
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

    // Generate resume
    try {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Map data to Excel cells based on the resume format
        $sheet->setCellValue('A1', '履歴書');
        $sheet->setCellValue('A3', '氏名');
        $sheet->setCellValue('B3', $fullname);
        $sheet->setCellValue('A4', 'ふりがな');
        $sheet->setCellValue('B4', $furigana);
        $sheet->setCellValue('A5', 'ローマ字');
        $sheet->setCellValue('B5', $roman_name);
        $sheet->setCellValue('A6', '国籍');
        $sheet->setCellValue('B6', $nationality);
        $sheet->setCellValue('A7', '性別');
        $sheet->setCellValue('B7', $gender === 'Male' ? '男性' : ($gender === 'Female' ? '女性' : 'その他'));
        $sheet->setCellValue('A8', '宗教');
        $sheet->setCellValue('B8', $religion);
        $sheet->setCellValue('A9', '生年月日');
        $sheet->setCellValue('B9', $dob);
        $sheet->setCellValue('A10', '出生地');
        $sheet->setCellValue('B10', $birth_place);
        $sheet->setCellValue('A11', '配偶者の有無');
        $sheet->setCellValue('B11', $marital_status === 'Single' ? '無し' : '有り');
        $sheet->setCellValue('A12', '現住所');
        $sheet->setCellValue('B12', $address);
        $sheet->setCellValue('A13', '連絡先');
        $sheet->setCellValue('B13', $phone);
        $sheet->setCellValue('A14', 'Email');
        $sheet->setCellValue('B14', $email);
        $sheet->setCellValue('A15', '身長');
        $sheet->setCellValue('B15', $height_cm ? $height_cm . ' cm' : '');
        $sheet->setCellValue('A16', '体重');
        $sheet->setCellValue('B16', $weight_kg ? $weight_kg . ' kg' : '');
        $sheet->setCellValue('A17', 'パスポート有無');
        $sheet->setCellValue('B17', $passport_have === 'Yes' ? '有り' : '無し');
        $sheet->setCellValue('A18', 'パスポートNO');
        $sheet->setCellValue('B18', $passport_number);
        $sheet->setCellValue('A19', '有効期限');
        $sheet->setCellValue('B19', $passport_expiry);
        $sheet->setCellValue('A20', '過去の出入国歴 (回数)');
        $sheet->setCellValue('B20', $migration_history);
        $sheet->setCellValue('A21', '直近の入国日');
        $sheet->setCellValue('B21', $recent_migration_entry);
        $sheet->setCellValue('A22', '直近の出国日');
        $sheet->setCellValue('B22', $recent_migration_exit);
        $sheet->setCellValue('A23', '現在の在留資格');
        $sheet->setCellValue('B23', $residency_status);
        $sheet->setCellValue('A24', '在留期限');
        $sheet->setCellValue('B24', $residency_expiry);

        // Education
        $sheet->setCellValue('A26', '学歴');
        $row = 27;
        foreach ($education as $edu) {
            $sheet->setCellValue('A' . $row, $edu['institution_name']);
            $sheet->setCellValue('B' . $row, $edu['join_date'] . ' - ' . $edu['leave_date']);
            $sheet->setCellValue('C' . $row, $edu['major']);
            $sheet->setCellValue('D' . $row, $edu['status']);
            $row++;
        }

        // Work Experience
        $sheet->setCellValue('A' . ($row + 1), '職歴');
        $row += 2;
        foreach ($work_experience as $exp) {
            $sheet->setCellValue('A' . $row, $exp['company_name']);
            $sheet->setCellValue('B' . $row, $exp['join_date'] . ' - ' . $exp['leave_date']);
            $sheet->setCellValue('C' . $row, $exp['job_role']);
            $sheet->setCellValue('D' . $row, $exp['current_status']);
            $row++;
        }

        // Certifications
        $sheet->setCellValue('A' . ($row + 1), '免許・資格');
        $row += 2;
        foreach ($certifications as $cert) {
            $sheet->setCellValue('A' . $row, $cert['name']);
            $sheet->setCellValue('B' . $row, $cert['type']);
            $sheet->setCellValue('C' . $row, $cert['score']);
            $sheet->setCellValue('D' . $row, $cert['date_obtained']);
            $row++;
        }

        // Self-PR and Motivation
        $sheet->setCellValue('A' . ($row + 1), '自己PR');
        $sheet->setCellValue('B' . ($row + 1), $self_intro);
        $row += 2;
        $sheet->setCellValue('A' . $row, '志望動機');
        $sheet->setCellValue('B' . $row, $motivation);
        $row += 2;
        $sheet->setCellValue('A' . $row, '本人希望欄');
        $sheet->setCellValue('B' . $row, $job_preference);

        // Save resume
        $resume_path = '../resumes/' . $applicant_id . '_resume.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($resume_path);
    } catch (Exception $e) {
        die("Failed to generate resume: " . $e->getMessage());
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
    header("Location: ../confirmation.php?applicant_id=$applicant_id");
    exit;
} else {
    die("Invalid request method.");
}
?>