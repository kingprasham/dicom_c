<?php
/**
 * Hospital DICOM Viewer Pro v2.0
 * Automated Backup Service
 *
 * This script should be run via Windows Task Scheduler or Cron
 * Runs daily at configured time to create automated backups
 *
 * Usage:
 * - Windows Task Scheduler: php C:\xampp\htdocs\papa\dicom_again\claude\scripts\backup-service.php
 * - Cron: 0 2 * * * /usr/bin/php /path/to/scripts/backup-service.php
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Define constants
define('DICOM_VIEWER', true);
define('SCRIPT_START', microtime(true));

// Include configuration
require_once __DIR__ . '/../includes/config.php';

// Define log file
$logFile = __DIR__ . '/../logs/backup-service.log';

/**
 * Log message to backup service log
 */
function logToFile($message, $level = 'INFO')
{
    global $logFile;

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";

    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND);

    // Also output to console if in CLI mode
    echo $logEntry;
}

try {
    logToFile("=== Backup Service Started ===");
    logToFile("PHP Version: " . PHP_VERSION);
    logToFile("Script Path: " . __FILE__);

    // Get database connection
    $db = getDbConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    logToFile("Database connection established");

    // Load backup configuration
    $configQuery = "SELECT * FROM gdrive_backup_config LIMIT 1";
    $configResult = $db->query($configQuery);

    if (!$configResult) {
        throw new Exception("Failed to load backup configuration: " . $db->error);
    }

    $config = $configResult->fetch_assoc();
    $configResult->free();

    if (!$config) {
        throw new Exception("Backup configuration not found");
    }

    logToFile("Backup configuration loaded");
    logToFile("Backup Enabled: " . ($config['backup_enabled'] ? 'Yes' : 'No'));
    logToFile("Backup Schedule: " . $config['backup_schedule']);
    logToFile("Backup Time: " . $config['backup_time']);
    logToFile("Retention Days: " . $config['retention_days']);

    // Check if backup is enabled
    if (!$config['backup_enabled']) {
        logToFile("Backup is disabled in configuration. Exiting.", 'WARNING');
        exit(0);
    }

    // Check if it's time to run backup based on schedule
    $shouldRunBackup = false;
    $currentTime = new DateTime();
    $scheduledTime = new DateTime($config['backup_time']);
    $lastBackupTime = $config['last_backup_at'] ? new DateTime($config['last_backup_at']) : null;

    logToFile("Current Time: " . $currentTime->format('Y-m-d H:i:s'));
    logToFile("Scheduled Time: " . $scheduledTime->format('H:i:s'));
    logToFile("Last Backup: " . ($lastBackupTime ? $lastBackupTime->format('Y-m-d H:i:s') : 'Never'));

    switch ($config['backup_schedule']) {
        case 'daily':
            // Run if no backup today or if past scheduled time and no backup since then
            if (!$lastBackupTime || $lastBackupTime->format('Y-m-d') < $currentTime->format('Y-m-d')) {
                $shouldRunBackup = true;
            }
            break;

        case 'weekly':
            // Run on Mondays if no backup this week
            if ($currentTime->format('N') == 1) { // Monday
                if (!$lastBackupTime || $lastBackupTime->format('Y-W') < $currentTime->format('Y-W')) {
                    $shouldRunBackup = true;
                }
            }
            break;

        case 'monthly':
            // Run on first day of month if no backup this month
            if ($currentTime->format('d') == '01') {
                if (!$lastBackupTime || $lastBackupTime->format('Y-m') < $currentTime->format('Y-m')) {
                    $shouldRunBackup = true;
                }
            }
            break;
    }

    if (!$shouldRunBackup) {
        logToFile("Not scheduled to run at this time. Exiting.", 'INFO');
        exit(0);
    }

    logToFile("Backup is scheduled to run now");

    // Load Google Drive Backup class
    require_once __DIR__ . '/../includes/classes/GoogleDriveBackup.php';

    // Initialize backup service
    logToFile("Initializing Google Drive Backup service...");
    $backupService = new \DicomViewer\GoogleDriveBackup($db);

    // Check if Google Drive is configured
    if (empty($config['client_id']) || empty($config['client_secret'])) {
        logToFile("Google Drive credentials not configured. Exiting.", 'ERROR');
        exit(1);
    }

    if (empty($config['refresh_token'])) {
        logToFile("Google Drive not authenticated (no refresh token). Exiting.", 'ERROR');
        exit(1);
    }

    logToFile("Google Drive credentials verified");

    // Create backup
    logToFile("Starting scheduled backup creation...");
    $startTime = microtime(true);

    $result = $backupService->createBackup('scheduled');

    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);

    logToFile("Backup created successfully!");
    logToFile("Backup Name: " . $result['backup_name']);
    logToFile("Backup Size: " . $result['size_formatted']);
    logToFile("Google Drive File ID: " . ($result['gdrive_file_id'] ?? 'N/A'));
    logToFile("Duration: {$duration} seconds");

    // Clean up old backups based on retention policy
    logToFile("Running cleanup of old backups...");
    $cleanupResult = $backupService->cleanupOldBackups($config['retention_days']);

    logToFile("Cleanup completed");
    logToFile("Backups deleted: " . $cleanupResult['deleted_count']);

    if (!empty($cleanupResult['errors'])) {
        logToFile("Cleanup errors: " . count($cleanupResult['errors']), 'WARNING');
        foreach ($cleanupResult['errors'] as $error) {
            logToFile("  - {$error}", 'WARNING');
        }
    }

    // Calculate total execution time
    $totalDuration = round(microtime(true) - SCRIPT_START, 2);
    logToFile("Total execution time: {$totalDuration} seconds");
    logToFile("=== Backup Service Completed Successfully ===");

    exit(0);

} catch (Exception $e) {
    logToFile("FATAL ERROR: " . $e->getMessage(), 'ERROR');
    logToFile("Stack Trace: " . $e->getTraceAsString(), 'ERROR');

    // Try to log error to database
    try {
        $db = getDbConnection();
        if ($db) {
            $stmt = $db->prepare("
                INSERT INTO backup_history (backup_type, backup_name, status, error_message)
                VALUES ('scheduled', ?, 'failed', ?)
            ");
            $backupName = 'scheduled_backup_' . date('Y-m-d_H-i-s');
            $errorMsg = $e->getMessage();
            $stmt->bind_param('ss', $backupName, $errorMsg);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $logError) {
        logToFile("Failed to log error to database: " . $logError->getMessage(), 'ERROR');
    }

    logToFile("=== Backup Service Failed ===");
    exit(1);
}
