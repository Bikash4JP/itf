<?php
// Run Composer audit using local composer.phar
$composer_path = '/home/it-future/www/itf/composer.phar';
$log_file = '/home/it-future/www/itf/logs/composer_audit.log';

if (!file_exists($composer_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Composer PHAR not found']);
    exit;
}

// Run Composer audit
$output = shell_exec("php $composer_path audit 2>&1");

// Log the output
file_put_contents($log_file, "Composer Audit Output at " . date('Y-m-d H:i:s') . ":\n" . $output . "\n", FILE_APPEND);

echo json_encode(['status' => 'success', 'output' => $output]);
?>