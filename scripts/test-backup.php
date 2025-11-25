<?php
/**
 * Hospital DICOM Viewer Pro v2.0
 * Backup System Test Script
 *
 * This script tests the backup system components
 * Run from command line: php test-backup.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

define('DICOM_VIEWER', true);
require_once __DIR__ . '/../includes/config.php';

echo "=== Hospital DICOM Viewer Pro v2.0 - Backup System Test ===\n\n";

// Test 1: Database Connection
echo "[TEST 1] Testing database connection...\n";
try {
    $db = getDbConnection();
    if ($db && $db->ping()) {
        echo "✓ Database connection successful\n";
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Backup Configuration
echo "\n[TEST 2] Checking backup configuration...\n";
$configQuery = "SELECT * FROM gdrive_backup_config LIMIT 1";
$configResult = $db->query($configQuery);
if ($configResult && $config = $configResult->fetch_assoc()) {
    echo "✓ Backup configuration found\n";
    echo "  - Backup Enabled: " . ($config['backup_enabled'] ? 'Yes' : 'No') . "\n";
    echo "  - Schedule: {$config['backup_schedule']}\n";
    echo "  - Time: {$config['backup_time']}\n";
    echo "  - Retention Days: {$config['retention_days']}\n";
    echo "  - Has Client ID: " . (!empty($config['client_id']) ? 'Yes' : 'No') . "\n";
    echo "  - Has Client Secret: " . (!empty($config['client_secret']) ? 'Yes' : 'No') . "\n";
    echo "  - Has Refresh Token: " . (!empty($config['refresh_token']) ? 'Yes' : 'No') . "\n";
} else {
    echo "✗ Backup configuration not found\n";
}

// Test 3: Backup Class Loading
echo "\n[TEST 3] Loading GoogleDriveBackup class...\n";
try {
    require_once __DIR__ . '/../includes/classes/GoogleDriveBackup.php';
    $backupService = new \DicomViewer\GoogleDriveBackup($db);
    echo "✓ GoogleDriveBackup class loaded successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to load GoogleDriveBackup class: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Backup Directory Permissions
echo "\n[TEST 4] Checking backup directories...\n";
$backupDir = __DIR__ . '/../backups/temp/';
if (is_dir($backupDir)) {
    echo "✓ Backup directory exists: {$backupDir}\n";
    if (is_writable($backupDir)) {
        echo "✓ Backup directory is writable\n";
    } else {
        echo "✗ Backup directory is not writable\n";
    }
} else {
    echo "⚠ Backup directory does not exist, attempting to create...\n";
    if (mkdir($backupDir, 0755, true)) {
        echo "✓ Backup directory created successfully\n";
    } else {
        echo "✗ Failed to create backup directory\n";
    }
}

// Test 5: Log Directory
echo "\n[TEST 5] Checking log directory...\n";
$logDir = __DIR__ . '/../logs/';
if (is_dir($logDir)) {
    echo "✓ Log directory exists: {$logDir}\n";
    if (is_writable($logDir)) {
        echo "✓ Log directory is writable\n";
    } else {
        echo "✗ Log directory is not writable\n";
    }
} else {
    echo "⚠ Log directory does not exist, attempting to create...\n";
    if (mkdir($logDir, 0755, true)) {
        echo "✓ Log directory created successfully\n";
    } else {
        echo "✗ Failed to create log directory\n";
    }
}

// Test 6: Google API Library
echo "\n[TEST 6] Checking Google API library...\n";
if (class_exists('Google_Client')) {
    echo "✓ Google API client library is installed\n";
} else {
    echo "✗ Google API client library not found\n";
    echo "  Run: composer require google/apiclient\n";
}

// Test 7: PHP Extensions
echo "\n[TEST 7] Checking required PHP extensions...\n";
$requiredExtensions = ['mysqli', 'zip', 'curl', 'json', 'mbstring'];
$allInstalled = true;
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ {$ext} extension installed\n";
    } else {
        echo "✗ {$ext} extension NOT installed\n";
        $allInstalled = false;
    }
}

// Test 8: mysqldump availability
echo "\n[TEST 8] Checking mysqldump availability...\n";
$mysqldumpCheck = shell_exec('mysqldump --version 2>&1');
if (stripos($mysqldumpCheck, 'mysqldump') !== false) {
    echo "✓ mysqldump is available\n";
    echo "  Version: " . trim($mysqldumpCheck) . "\n";
} else {
    echo "⚠ mysqldump not found in PATH\n";
    echo "  System will use PHP-based backup as fallback\n";
}

// Test 9: Backup History
echo "\n[TEST 9] Checking backup history...\n";
$historyQuery = "SELECT COUNT(*) as total FROM backup_history";
$historyResult = $db->query($historyQuery);
if ($historyResult) {
    $historyData = $historyResult->fetch_assoc();
    echo "✓ Total backups in history: {$historyData['total']}\n";

    // Get last backup
    $lastBackupQuery = "SELECT * FROM backup_history ORDER BY created_at DESC LIMIT 1";
    $lastBackupResult = $db->query($lastBackupQuery);
    if ($lastBackupResult && $lastBackup = $lastBackupResult->fetch_assoc()) {
        echo "  Last backup:\n";
        echo "    - Name: {$lastBackup['backup_name']}\n";
        echo "    - Type: {$lastBackup['backup_type']}\n";
        echo "    - Status: {$lastBackup['status']}\n";
        echo "    - Created: {$lastBackup['created_at']}\n";
    }
}

// Test 10: Statistics
echo "\n[TEST 10] Getting backup statistics...\n";
try {
    $stats = $backupService->getStatistics();
    echo "✓ Backup statistics retrieved:\n";
    echo "  - Total backups: {$stats['total_backups']}\n";
    echo "  - Successful: {$stats['successful_backups']}\n";
    echo "  - Failed: {$stats['failed_backups']}\n";
    echo "  - Total size: {$stats['total_size_formatted']}\n";
} catch (Exception $e) {
    echo "✗ Failed to get statistics: " . $e->getMessage() . "\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "All basic tests completed.\n";
echo "Review the output above for any issues.\n\n";

if (!empty($config['client_id']) && !empty($config['refresh_token'])) {
    echo "System Status: READY for automated backups\n";
} else if (!empty($config['client_id']) && empty($config['refresh_token'])) {
    echo "System Status: NEEDS AUTHENTICATION\n";
    echo "Action Required: Complete OAuth authentication in admin panel\n";
} else {
    echo "System Status: NEEDS CONFIGURATION\n";
    echo "Action Required: Configure Google Drive credentials in admin panel\n";
}

echo "\n";
exit(0);
