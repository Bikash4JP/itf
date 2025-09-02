<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0); // Production mein errors browser mein nahi dikhne chahiye
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/it-future/www/itf/logs/php_errors.log');
error_reporting(E_ALL);

// Start output buffering to prevent output before headers
ob_start();

// Include database connection
try {
    require_once __DIR__ . '/db_connect.php'; // Correct path for db_connect.php
    if (!isset($pdo)) {
        throw new Exception("PDO object not initialized");
    }
} catch (Exception $e) {
    error_log("Database connection failed in download_rireksyo.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: Unable to connect to the database']);
    ob_end_flush();
    exit;
}

// Validate application_id
$application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
if ($application_id <= 0) {
    error_log("Invalid application ID in download_rireksyo.php: " . var_export($_GET['application_id'], true) . " at " . date('Y-m-d H:i:s'));
    http_response_code(400);
    echo json_encode(['error' => 'Invalid application ID']);
    ob_end_flush();
    exit;
}

// Fetch the resume path from the database
try {
    $stmt = $pdo->prepare("SELECT resume_path FROM applicant WHERE id = ?");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application || empty($application['resume_path'])) {
        error_log("Application or resume not found for ID: " . $application_id . " at " . date('Y-m-d H:i:s'));
        http_response_code(404);
        echo json_encode(['error' => 'Application or resume not found']);
        ob_end_flush();
        exit;
    }

    $resume_path = $application['resume_path'];
} catch (PDOException $e) {
    error_log("Failed to fetch application in download_rireksyo.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: Failed to fetch application']);
    ob_end_flush();
    exit;
}

// Check if the file exists and is readable
if (!file_exists($resume_path)) {
    error_log("Resume file not found at path: " . $resume_path . " for application ID: " . $application_id . " at " . date('Y-m-d H:i:s'));
    http_response_code(404);
    echo json_encode(['error' => 'Resume file not found']);
    ob_end_flush();
    exit;
}

if (!is_readable($resume_path)) {
    error_log("Resume file not readable at path: " . $resume_path . " for application ID: " . $application_id . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: Resume file not readable']);
    ob_end_flush();
    exit;
}

// Serve the file for download as PDF
try {
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($resume_path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($resume_path));
    ob_clean(); // Clear any output buffer before sending file
    flush(); // Flush system output buffer
    readfile($resume_path);
    exit;
} catch (Exception $e) {
    error_log("Failed to serve file in download_rireksyo.php: " . $e->getMessage() . " at " . date('Y-m-d H:i:s'));
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error: Failed to serve file']);
    ob_end_flush();
    exit;
}
?>