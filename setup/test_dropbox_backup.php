<?php
/**
 * Quick Test Script for Dropbox Backup
 * Run this to verify backup is working
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/MultiProviderBackupManager.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

echo "===========================================\n";
echo "Dropbox Backup Test Script\n";
echo "===========================================\n\n";

try {
    $db = getDbConnection();
    
    // Get Dropbox account
    $result = $db->query("
        SELECT id, account_name, backup_provider, dropbox_access_token, folder_name 
        FROM backup_accounts 
        WHERE backup_provider = 'dropbox' AND is_active = 1 
        LIMIT 1
    ");
    
    if ($result->num_rows == 0) {
        throw new Exception("No active Dropbox account found");
    }
    
    $account = $result->fetch_assoc();
    echo "✓ Found Dropbox account: {$account['account_name']}\n";
    echo "  Folder: {$account['folder_name']}\n\n";
    
    // Set temp config
    $stmt = $db->prepare("
        INSERT INTO hospital_data_config (config_key, config_value)
        VALUES ('temp_dropbox_token', ?), ('temp_dropbox_folder', ?)
        ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
    ");
    $stmt->bind_param('ss', $account['dropbox_access_token'], $account['folder_name']);
    $stmt->execute();
    $stmt->close();
    
    echo "Creating backup...\n";
    $backupManager = new MultiProviderBackupManager();
    $result = $backupManager->createBackup('dropbox', 'manual');
    
    echo "\n===========================================\n";
    echo "✓ BACKUP SUCCESSFUL!\n";
    echo "===========================================\n\n";
    
    echo "Backup Details:\n";
    echo "  - Filename: {$result['filename']}\n";
    echo "  - Size: " . round($result['size'] / 1024 / 1024, 2) . " MB\n";
    echo "  - Backup ID: {$result['backup_id']}\n";
    echo "  - File ID: {$result['file_id']}\n";
    
    echo "\n✓ Check your Dropbox folder to verify the file!\n\n";
    
} catch (Exception $e) {
    echo "\n===========================================\n";
    echo "✗ BACKUP FAILED\n";
    echo "===========================================\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
