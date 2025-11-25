<?php
/**
 * Hospital DICOM Viewer Pro v2.0
 * Data Monitor Service
 *
 * Continuously monitors hospital data directory for new DICOM files
 * and imports them to Orthanc
 *
 * Run as Windows service using NSSM
 */

define('DICOM_VIEWER', true);

// Load configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/classes/HospitalDataImporter.php';

// Configure error reporting for service
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . '/monitor-service-error.log');

// Service configuration
$checkInterval = 30; // Check every 30 seconds
$serviceName = 'DICOM Data Monitor Service';

logMessage("=== {$serviceName} Started ===", 'info', 'monitor-service.log');
logMessage("Check interval: {$checkInterval} seconds", 'info', 'monitor-service.log');

// Main service loop
while (true) {
    try {
        $db = getDbConnection();

        // Get configuration
        $stmt = $db->prepare("
            SELECT hospital_data_path, monitoring_enabled
            FROM sync_configuration
            LIMIT 1
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $config = $result->fetch_assoc();
        $stmt->close();

        if ($config && $config['monitoring_enabled'] && !empty($config['hospital_data_path'])) {
            $hospitalPath = $config['hospital_data_path'];

            logMessage("Checking for new files in: {$hospitalPath}", 'debug', 'monitor-service.log');

            // Initialize importer
            $importer = new \DicomViewer\HospitalDataImporter($db);

            // Scan for new files
            $newFiles = $importer->scanForNewFiles($hospitalPath);

            if (count($newFiles) > 0) {
                logMessage("Found " . count($newFiles) . " new file(s)", 'info', 'monitor-service.log');

                // Create incremental import job
                $jobId = $importer->createImportJob($hospitalPath, 'incremental', count($newFiles));

                // Process new files
                $result = $importer->batchImport($newFiles, $jobId);

                logMessage(
                    "Import completed: {$result['imported']} imported, {$result['failed']} failed",
                    'info',
                    'monitor-service.log'
                );

                // Update job status
                $importer->updateJobProgress(
                    $jobId,
                    $result['processed'],
                    $result['imported'],
                    $result['failed'],
                    'completed'
                );
            } else {
                logMessage("No new files found", 'debug', 'monitor-service.log');
            }
        } else {
            logMessage("Monitoring disabled or path not configured", 'debug', 'monitor-service.log');
        }

        $db->close();

    } catch (Exception $e) {
        logMessage("Service error: " . $e->getMessage(), 'error', 'monitor-service.log');
    }

    // Sleep for check interval
    sleep($checkInterval);

    // Memory cleanup
    gc_collect_cycles();
}

logMessage("=== {$serviceName} Stopped ===", 'info', 'monitor-service.log');
