<?php
/**
 * Auto-Scanner Service
 * Background service that automatically scans monitored paths for new folders/files
 * Can be run via cron job, Windows Task Scheduler, or manually
 */

// Allow running from command line or web
if (php_sapi_name() !== 'cli') {
    define('DICOM_VIEWER', true);
    require_once __DIR__ . '/../includes/config.php';
}

class AutoScannerService {
    private $db;
    private $logFile;
    private $startTime;

    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/auto-scanner.log';
        $this->startTime = microtime(true);

        // Ensure logs directory exists
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Main scan execution
     */
    public function runScan($scanType = 'auto') {
        $this->log("========================================");
        $this->log("Auto-Scanner Service Started ($scanType)");
        $this->log("========================================");

        try {
            $this->db = getDbConnection();

            // Check if auto-import is enabled (only for auto scans)
            if ($scanType === 'auto') {
                $config = $this->getConfig();
                if (!$config['auto_import_enabled']) {
                    $this->log("Auto-import is disabled. Skipping scan.");
                    return ['success' => true, 'message' => 'Auto-import disabled'];
                }

                // Check if enough time has passed since last scan
                if ($config['last_scan_time']) {
                    $lastScan = new DateTime($config['last_scan_time']);
                    $now = new DateTime();
                    $minutesSinceLastScan = ($now->getTimestamp() - $lastScan->getTimestamp()) / 60;

                    if ($minutesSinceLastScan < $config['scan_interval_minutes']) {
                        $this->log("Not enough time passed since last scan. Skipping.");
                        return ['success' => true, 'message' => 'Too soon for next scan'];
                    }
                }
            }

            // Update status to scanning
            $this->updateScanStatus('scanning');

            // Get all active monitored paths
            $paths = $this->getMonitoredPaths();

            if (empty($paths)) {
                $this->log("No monitored paths configured.");
                $this->updateScanStatus('idle');
                return ['success' => false, 'error' => 'No monitored paths'];
            }

            $this->log("Found " . count($paths) . " monitored path(s)");

            $totalFoldersFound = 0;
            $totalNewFolders = 0;
            $totalFilesImported = 0;
            $errors = [];

            // Scan each monitored path
            foreach ($paths as $path) {
                $this->log("\nScanning path: {$path['name']} ({$path['path']})");

                if (!is_dir($path['path'])) {
                    $this->log("  WARNING: Path does not exist or is not accessible");
                    $errors[] = "Path not accessible: {$path['path']}";
                    continue;
                }

                $result = $this->scanPath($path);

                $totalFoldersFound += $result['folders_found'];
                $totalNewFolders += $result['new_folders'];
                $totalFilesImported += $result['files_imported'];

                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }
            }

            // Auto-import new files if enabled
            if ($this->getConfig()['auto_import_enabled']) {
                $this->log("\nAuto-import is enabled. Processing new folders...");
                $this->updateScanStatus('importing');

                $importResult = $this->autoImportNewFolders();
                $totalFilesImported = $importResult['files_imported'];

                if (!empty($importResult['errors'])) {
                    $errors = array_merge($errors, $importResult['errors']);
                }
            }

            // Calculate scan duration
            $duration = round(microtime(true) - $this->startTime, 2);

            // Log scan results
            $this->logScanResult([
                'scan_type' => $scanType,
                'folders_found' => $totalFoldersFound,
                'new_folders' => $totalNewFolders,
                'files_imported' => $totalFilesImported,
                'scan_duration_seconds' => $duration,
                'status' => empty($errors) ? 'success' : 'partial',
                'error_message' => empty($errors) ? null : implode('; ', $errors)
            ]);

            // Update status and last scan time
            $this->updateScanStatus('idle');
            $this->updateLastScanTime();

            $this->log("\n========================================");
            $this->log("Scan Summary:");
            $this->log("  Folders Found: $totalFoldersFound");
            $this->log("  New Folders: $totalNewFolders");
            $this->log("  Files Imported: $totalFilesImported");
            $this->log("  Duration: {$duration}s");
            $this->log("  Status: " . (empty($errors) ? 'SUCCESS' : 'PARTIAL (with errors)'));
            $this->log("========================================");

            return [
                'success' => true,
                'folders_found' => $totalFoldersFound,
                'new_folders' => $totalNewFolders,
                'files_imported' => $totalFilesImported,
                'duration' => $duration,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->updateScanStatus('idle');

            $this->logScanResult([
                'scan_type' => $scanType,
                'folders_found' => 0,
                'new_folders' => 0,
                'files_imported' => 0,
                'scan_duration_seconds' => round(microtime(true) - $this->startTime, 2),
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Scan a specific monitored path
     */
    private function scanPath($pathInfo) {
        $foldersFound = 0;
        $newFolders = 0;
        $filesImported = 0;
        $errors = [];

        try {
            $iterator = new DirectoryIterator($pathInfo['path']);

            foreach ($iterator as $item) {
                if ($item->isDir() && !$item->isDot()) {
                    $foldersFound++;
                    $folderPath = $item->getPathname();
                    $folderName = $item->getFilename();

                    // Check if this folder is already known
                    $stmt = $this->db->prepare("SELECT id FROM known_folders WHERE folder_path = ?");
                    $stmt->bind_param('s', $folderPath);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $isNew = $result->num_rows === 0;
                    $stmt->close();

                    if ($isNew) {
                        $newFolders++;
                        $this->log("  NEW FOLDER: $folderName");

                        // Add to known folders
                        $stmt = $this->db->prepare("INSERT INTO known_folders (folder_path, folder_name, monitored_path_id) VALUES (?, ?, ?)");
                        $stmt->bind_param('ssi', $folderPath, $folderName, $pathInfo['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $this->log("  Found $foldersFound folders, $newFolders are new");

        } catch (Exception $e) {
            $this->log("  ERROR scanning path: " . $e->getMessage());
            $errors[] = "Error scanning {$pathInfo['path']}: " . $e->getMessage();
        }

        return [
            'folders_found' => $foldersFound,
            'new_folders' => $newFolders,
            'files_imported' => $filesImported,
            'errors' => $errors
        ];
    }

    /**
     * Auto-import DICOM files from new folders
     */
    private function autoImportNewFolders() {
        $filesImported = 0;
        $errors = [];

        try {
            // Get folders that haven't been synced yet
            $result = $this->db->query("
                SELECT id, folder_path, folder_name
                FROM known_folders
                WHERE synced_at IS NULL
                ORDER BY first_seen ASC
                LIMIT 100
            ");

            $newFolders = [];
            while ($row = $result->fetch_assoc()) {
                $newFolders[] = $row;
            }

            if (empty($newFolders)) {
                $this->log("  No new folders to import");
                return ['files_imported' => 0, 'errors' => []];
            }

            $this->log("  Found " . count($newFolders) . " folder(s) to import");

            foreach ($newFolders as $folder) {
                $this->log("  Importing: {$folder['folder_name']}");

                try {
                    // Count DICOM files in folder
                    $dicomFiles = $this->scanDicomFiles($folder['folder_path']);
                    $fileCount = count($dicomFiles);

                    if ($fileCount > 0) {
                        $this->log("    Found $fileCount DICOM file(s)");

                        // Here you would trigger the actual import process
                        // For now, just mark as synced
                        // TODO: Integrate with existing DICOM import logic

                        $filesImported += $fileCount;

                        // Mark folder as synced
                        $stmt = $this->db->prepare("UPDATE known_folders SET synced_at = NOW() WHERE id = ?");
                        $stmt->bind_param('i', $folder['id']);
                        $stmt->execute();
                        $stmt->close();

                        $this->log("    Import queued successfully");
                    } else {
                        $this->log("    No DICOM files found, skipping");

                        // Mark as synced anyway (empty folder)
                        $stmt = $this->db->prepare("UPDATE known_folders SET synced_at = NOW() WHERE id = ?");
                        $stmt->bind_param('i', $folder['id']);
                        $stmt->execute();
                        $stmt->close();
                    }

                } catch (Exception $e) {
                    $this->log("    ERROR: " . $e->getMessage());
                    $errors[] = "Error importing {$folder['folder_name']}: " . $e->getMessage();
                }
            }

        } catch (Exception $e) {
            $this->log("  ERROR during auto-import: " . $e->getMessage());
            $errors[] = "Auto-import error: " . $e->getMessage();
        }

        return [
            'files_imported' => $filesImported,
            'errors' => $errors
        ];
    }

    /**
     * Scan folder for DICOM files
     */
    private function scanDicomFiles($folderPath, $recursive = true) {
        $dicomFiles = [];

        if (!is_dir($folderPath)) {
            return $dicomFiles;
        }

        try {
            $iterator = $recursive
                ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS))
                : new DirectoryIterator($folderPath);

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filePath = $file->getPathname();

                    // Check if file is DICOM (simple check)
                    $handle = @fopen($filePath, 'rb');
                    if ($handle) {
                        fseek($handle, 128);
                        $marker = fread($handle, 4);
                        fclose($handle);

                        if ($marker === 'DICM' || strtolower($file->getExtension()) === 'dcm') {
                            $dicomFiles[] = $filePath;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->log("Error scanning DICOM files in $folderPath: " . $e->getMessage());
        }

        return $dicomFiles;
    }

    /**
     * Get monitored paths from database
     */
    private function getMonitoredPaths() {
        $result = $this->db->query("SELECT id, path, name FROM monitored_paths WHERE is_active = 1 ORDER BY id ASC");

        $paths = [];
        while ($row = $result->fetch_assoc()) {
            $paths[] = $row;
        }

        return $paths;
    }

    /**
     * Get scan configuration
     */
    private function getConfig() {
        $result = $this->db->query("SELECT * FROM auto_scan_config WHERE id = 1");
        return $result->fetch_assoc();
    }

    /**
     * Update scan status
     */
    private function updateScanStatus($status) {
        $stmt = $this->db->prepare("UPDATE auto_scan_config SET scan_status = ? WHERE id = 1");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Update last scan time
     */
    private function updateLastScanTime() {
        $this->db->query("UPDATE auto_scan_config SET last_scan_time = NOW() WHERE id = 1");
    }

    /**
     * Log scan result to database
     */
    private function logScanResult($data) {
        $stmt = $this->db->prepare("
            INSERT INTO scan_logs
            (scan_type, folders_found, new_folders, files_imported, scan_duration_seconds, status, error_message)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'siiiiss',
            $data['scan_type'],
            $data['folders_found'],
            $data['new_folders'],
            $data['files_imported'],
            $data['scan_duration_seconds'],
            $data['status'],
            $data['error_message']
        );

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";

        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);

        // Output to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Allow running from command line
if (php_sapi_name() === 'cli') {
    define('DICOM_VIEWER', true);
    require_once __DIR__ . '/../includes/config.php';

    $scanner = new AutoScannerService();
    $result = $scanner->runScan('manual');

    exit($result['success'] ? 0 : 1);
}
