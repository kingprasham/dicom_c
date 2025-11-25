<?php
/**
 * Hospital DICOM Viewer Pro v2.0
 * Automated Sync Background Service
 *
 * This script runs continuously in the background and performs automatic
 * synchronization of DICOM files from Orthanc storage to GoDaddy FTP
 * based on the configured sync interval.
 *
 * Usage:
 * - Run directly: php sync-service.php
 * - Install as Windows Service: nssm install DicomSyncService "C:\path\to\php.exe" "C:\path\to\sync-service.php"
 *
 * IMPORTANT: This service must be run from the command line or as a Windows service
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Define constant
define('DICOM_VIEWER', true);

// Load dependencies
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/SyncManager.php';

// Service configuration
$serviceName = 'DICOM Sync Service';
$checkInterval = 60; // Check every 60 seconds
$maxMemoryLimit = '512M'; // Maximum memory limit

// Set memory limit
ini_set('memory_limit', $maxMemoryLimit);

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Log file
$logFile = LOG_PATH . '/sync-service.log';

// Ensure logs directory exists
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

/**
 * Log message to service log
 */
function serviceLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry; // Also output to console
}

/**
 * Check if sync should run
 */
function shouldRunSync($config) {
    // Check if sync is enabled
    if (!$config['sync_enabled']) {
        return false;
    }

    // Check if FTP is configured
    if (empty($config['ftp_host']) || empty($config['ftp_username'])) {
        return false;
    }

    // Check if enough time has passed since last sync
    if (empty($config['last_sync_at'])) {
        return true; // Never synced before
    }

    $lastSyncTimestamp = strtotime($config['last_sync_at']);
    $intervalSeconds = ($config['sync_interval'] ?? 120) * 60;
    $nextSyncTimestamp = $lastSyncTimestamp + $intervalSeconds;

    return time() >= $nextSyncTimestamp;
}

/**
 * Perform sync operation
 */
function performSync($syncManager) {
    serviceLog("Starting scheduled sync operation");

    try {
        $startTime = microtime(true);

        // Step 1: Scan Orthanc storage
        serviceLog("Scanning Orthanc storage directory");
        $allFiles = $syncManager->scanOrthancStorage();
        serviceLog("Found " . count($allFiles) . " total files");

        // Step 2: Detect new files
        serviceLog("Detecting new files");
        $newFiles = $syncManager->detectNewFiles($allFiles);
        serviceLog("Detected " . count($newFiles) . " new files to sync");

        if (empty($newFiles)) {
            serviceLog("No new files to sync");

            // Create history record
            $syncManager->createSyncHistory(
                'scheduled',
                'godaddy',
                0,
                0,
                'success',
                null
            );

            return [
                'success' => true,
                'files_synced' => 0,
                'total_size' => 0
            ];
        }

        // Step 3: Sync to FTP with retry logic
        serviceLog("Syncing " . count($newFiles) . " files to FTP");

        $maxRetries = 3;
        $retryCount = 0;
        $syncResult = null;
        $lastError = null;

        while ($retryCount < $maxRetries) {
            try {
                $syncResult = $syncManager->syncToFTP($newFiles);

                if ($syncResult['success']) {
                    break; // Success - exit retry loop
                } else {
                    $lastError = implode('; ', $syncResult['errors']);
                    $retryCount++;

                    if ($retryCount < $maxRetries) {
                        serviceLog("Sync attempt {$retryCount} failed, retrying in 5 seconds...", 'WARNING');
                        sleep(5);
                    }
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                $retryCount++;

                if ($retryCount < $maxRetries) {
                    serviceLog("Sync attempt {$retryCount} failed: {$lastError}, retrying in 5 seconds...", 'WARNING');
                    sleep(5);
                }
            }
        }

        // Determine final status
        $status = 'failed';
        $errorMessage = null;

        if ($syncResult && $syncResult['success']) {
            $status = 'success';
        } elseif ($syncResult && $syncResult['files_synced'] > 0) {
            $status = 'partial';
            $errorMessage = $lastError;
        } else {
            $errorMessage = $lastError ?? 'Sync failed after ' . $maxRetries . ' attempts';
        }

        // Calculate metrics
        $totalSizeMB = round(($syncResult['total_size'] ?? 0) / (1024 * 1024), 2);
        $duration = round(microtime(true) - $startTime, 2);

        // Create sync history record
        $syncManager->createSyncHistory(
            'scheduled',
            'godaddy',
            $syncResult['files_synced'] ?? 0,
            $syncResult['total_size'] ?? 0,
            $status,
            $errorMessage
        );

        serviceLog(
            "Sync completed: {$syncResult['files_synced']} files, {$totalSizeMB} MB, {$duration}s, Status: {$status}",
            $status === 'success' ? 'INFO' : 'WARNING'
        );

        return [
            'success' => $status === 'success',
            'files_synced' => $syncResult['files_synced'],
            'total_size' => $syncResult['total_size'],
            'status' => $status
        ];

    } catch (Exception $e) {
        $errorMsg = "Sync error: " . $e->getMessage();
        serviceLog($errorMsg, 'ERROR');

        // Create failed history record
        $syncManager->createSyncHistory(
            'scheduled',
            'godaddy',
            0,
            0,
            'failed',
            $e->getMessage()
        );

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Main service loop
 */
function runService() {
    global $serviceName, $checkInterval;

    serviceLog("========================================");
    serviceLog("{$serviceName} Started");
    serviceLog("Check Interval: {$checkInterval} seconds");
    serviceLog("PHP Version: " . PHP_VERSION);
    serviceLog("Memory Limit: " . ini_get('memory_limit'));
    serviceLog("========================================");

    $lastConfigCheck = 0;
    $configCheckInterval = 300; // Check config every 5 minutes

    while (true) {
        try {
            // Reconnect to database to avoid timeout
            $db = getDbConnection();

            // Initialize SyncManager
            $syncManager = new DicomViewer\SyncManager($db);

            // Get current configuration
            $config = $syncManager->getConfiguration(false);

            // Check if sync should run
            if (shouldRunSync($config)) {
                serviceLog("Sync conditions met - initiating sync");
                $result = performSync($syncManager);

                if ($result['success']) {
                    serviceLog("Sync completed successfully");
                } else {
                    serviceLog("Sync completed with errors: " . ($result['error'] ?? 'Unknown error'), 'ERROR');
                }
            } else {
                // Only log status periodically to avoid log spam
                if (time() - $lastConfigCheck >= $configCheckInterval) {
                    if (!$config['sync_enabled']) {
                        serviceLog("Auto-sync is disabled", 'INFO');
                    } else {
                        $nextSync = date('Y-m-d H:i:s', strtotime($config['last_sync_at']) + ($config['sync_interval'] * 60));
                        serviceLog("Next sync scheduled for: {$nextSync}", 'INFO');
                    }
                    $lastConfigCheck = time();
                }
            }

            // Clean up memory
            unset($syncManager);
            unset($config);

            // Sleep before next check
            sleep($checkInterval);

        } catch (Exception $e) {
            serviceLog("Service error: " . $e->getMessage(), 'ERROR');
            serviceLog("Waiting 30 seconds before retry...", 'WARNING');
            sleep(30);
        }
    }
}

/**
 * Signal handler for graceful shutdown
 */
function signalHandler($signal) {
    serviceLog("Received signal {$signal} - shutting down gracefully");
    exit(0);
}

// Register signal handlers (if available)
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, 'signalHandler');
    pcntl_signal(SIGINT, 'signalHandler');
    serviceLog("Signal handlers registered");
}

// Handle uncaught exceptions
set_exception_handler(function($exception) {
    serviceLog("Uncaught exception: " . $exception->getMessage(), 'ERROR');
    serviceLog("Stack trace: " . $exception->getTraceAsString(), 'ERROR');
});

// Start the service
try {
    runService();
} catch (Exception $e) {
    serviceLog("Fatal error: " . $e->getMessage(), 'ERROR');
    exit(1);
}
