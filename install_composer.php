<?php
// Install Composer on the server
$log_file = '/home/it-future/www/itf/logs/install_composer.log';

// Check if Composer is already installed
$composer_path = '/home/it-future/www/itf/composer.phar';
if (file_exists($composer_path)) {
    file_put_contents($log_file, "Composer already exists at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(['status' => 'success', 'message' => 'Composer already exists']);
    exit;
}

// Download Composer installer
$installer_url = 'https://getcomposer.org/installer';
$installer_path = '/home/it-future/www/itf/composer-installer.php';

try {
    $installer_content = file_get_contents($installer_url);
    if ($installer_content === false) {
        throw new Exception('Failed to download Composer installer');
    }
    file_put_contents($installer_path, $installer_content);
    file_put_contents($log_file, "Downloaded Composer installer at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

    // Run Composer installer
    $output = shell_exec('php ' . $installer_path . ' 2>&1');
    file_put_contents($log_file, "Composer Install Output at " . date('Y-m-d H:i:s') . ":\n" . $output . "\n", FILE_APPEND);

    // Check if composer.phar was created
    if (!file_exists($composer_path)) {
        throw new Exception('Composer installation failed');
    }

    // Clean up installer
    unlink($installer_path);

    echo json_encode(['status' => 'success', 'message' => 'Composer installed successfully', 'output' => $output]);
} catch (Exception $e) {
    file_put_contents($log_file, "Composer Install Error at " . date('Y-m-d H:i:s') . ":\n" . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>