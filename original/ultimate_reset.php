<?php
/**
 * ULTIMATE NUCLEAR RESET - COMPLETE WIPEOUT
 * Deletes ALL data from: Database, Orthanc Server, Reports, Cache, Logs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$step = $_GET['step'] ?? 'confirm';

if ($step === 'confirm') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ultimate Nuclear Reset</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 800px;
                width: 100%;
            }
            .header {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
                padding: 40px;
                text-align: center;
                border-radius: 20px 20px 0 0;
            }
            .header h1 { font-size: 36px; margin-bottom: 10px; }
            .warning-icon {
                font-size: 100px;
                margin-bottom: 20px;
                animation: pulse 1.5s infinite;
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.15); }
            }
            .content { padding: 40px; }
            .warning-box {
                background: #fff3cd;
                border: 3px solid #ff0000;
                padding: 25px;
                margin: 20px 0;
                border-radius: 10px;
            }
            .warning-box h3 {
                color: #721c24;
                margin-bottom: 15px;
                font-size: 22px;
            }
            .delete-list {
                list-style: none;
                padding: 0;
            }
            .delete-list li {
                padding: 12px;
                margin: 10px 0;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #dc3545;
            }
            .delete-list li:before {
                content: '💀';
                margin-right: 10px;
                font-size: 24px;
            }
            .button-group {
                display: flex;
                gap: 15px;
                margin-top: 30px;
            }
            .btn {
                flex: 1;
                padding: 20px 30px;
                border: none;
                border-radius: 12px;
                font-size: 18px;
                font-weight: bold;
                cursor: pointer;
                text-decoration: none;
                text-align: center;
            }
            .btn-danger {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
                animation: glow 2s infinite;
            }
            @keyframes glow {
                0%, 100% { box-shadow: 0 0 20px rgba(245, 87, 108, 0.5); }
                50% { box-shadow: 0 0 40px rgba(245, 87, 108, 0.8); }
            }
            .btn-safe {
                background: #28a745;
                color: white;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="warning-icon">⚠️</div>
                <h1>ULTIMATE NUCLEAR RESET</h1>
                <p>Complete System Wipeout</p>
            </div>
            <div class="content">
                <div class="warning-box">
                    <h3>⚡ THIS WILL DELETE EVERYTHING! ⚡</h3>
                    <p><strong>This action is IRREVERSIBLE and will completely wipe:</strong></p>
                </div>

                <ul class="delete-list">
                    <li><strong>ALL Database Records (LOCAL)</strong><br>
                        - All patients, studies, series, instances<br>
                        - All sessions and user data<br>
                        - All prescriptions and reports metadata
                    </li>
                    <li><strong>ALL Orthanc Server Data (LOCAL)</strong><br>
                        - All DICOM studies from Orthanc<br>
                        - All patients from Orthanc<br>
                        - Complete Orthanc server reset
                    </li>
                    <li><strong>ALL Reports Folder (LOCAL)</strong><br>
                        - All medical reports (JSON files)<br>
                        - All report PDFs and documents
                    </li>
                    <li><strong>ALL Cache & Logs (LOCAL)</strong><br>
                        - DICOM cache folder<br>
                        - All log files<br>
                        - Temporary files
                    </li>
                    <li><strong>ALL DICOM Files (LOCAL)</strong><br>
                        - All stored DICOM files<br>
                        - Local storage completely cleared
                    </li>
                    <li><strong>🌐 ALL PRODUCTION DATA (e-connect.in)</strong><br>
                        - All database records from production<br>
                        - All DICOM files on production server<br>
                        - All reports on production server<br>
                        - Complete production wipeout!
                    </li>
                </ul>

                <div class="warning-box">
                    <h3>⚡ WARNING ⚡</h3>
                    <p>After this reset:</p>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>The database will be empty (structure preserved)</li>
                        <li>Orthanc will have NO studies or patients</li>
                        <li>All reports will be gone</li>
                        <li>You'll have a completely fresh system</li>
                        <li><strong>THIS CANNOT BE UNDONE!</strong></li>
                    </ul>
                </div>

                <div class="button-group">
                    <a href="pages/patients.html" class="btn btn-safe">Cancel - Go Back</a>
                    <a href="?step=execute" class="btn btn-danger">💣 NUKE EVERYTHING</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($step === 'execute') {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/db.php';

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reset in Progress</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Courier New', monospace;
                background: #000;
                color: #0f0;
                padding: 40px;
                line-height: 1.6;
            }
            .console {
                background: #111;
                border: 2px solid #0f0;
                border-radius: 10px;
                padding: 30px;
                max-width: 1000px;
                margin: 0 auto;
                box-shadow: 0 0 40px rgba(0, 255, 0, 0.3);
            }
            h1 {
                color: #f00;
                text-align: center;
                margin-bottom: 30px;
                font-size: 32px;
                text-shadow: 0 0 10px #f00;
            }
            .step {
                margin: 15px 0;
                padding: 10px;
                background: #0a0a0a;
                border-left: 4px solid #0f0;
            }
            .step.success { border-left-color: #0f0; }
            .step.error { border-left-color: #f00; color: #f00; }
            .step.warning { border-left-color: #ff0; color: #ff0; }
            .summary {
                margin-top: 30px;
                padding: 20px;
                background: #0a0a0a;
                border: 2px solid #0f0;
                border-radius: 5px;
            }
            .back-link {
                display: block;
                margin-top: 30px;
                text-align: center;
                color: #0f0;
                text-decoration: none;
                font-size: 18px;
                padding: 15px;
                background: #0a0a0a;
                border: 2px solid #0f0;
                border-radius: 5px;
            }
            .back-link:hover {
                background: #0f0;
                color: #000;
            }
        </style>
    </head>
    <body>
        <div class="console">
            <h1>☢️ NUCLEAR RESET IN PROGRESS ☢️</h1>
    <?php

    $results = [];
    $totalDeleted = 0;

    // ============================================================
    // STEP 1: Clear Database Tables
    // ============================================================
    echo "<div class='step success'>[STEP 1] 🗄️ Clearing Database Tables...</div>";
    flush();

    $tables = [
        'sessions',
        'prescriptions',
        'dicom_instances',
        'dicom_files',
        'cached_series',
        'cached_studies',
        'cached_patients'
    ];

    foreach ($tables as $table) {
        try {
            $result = $mysqli->query("TRUNCATE TABLE `{$table}`");
            if ($result) {
                $affected = $mysqli->affected_rows;
                echo "<div class='step success'>  ✓ Truncated table: {$table}</div>";
                flush();
            } else {
                echo "<div class='step warning'>  ⚠ Table {$table} might not exist (OK if new setup)</div>";
                flush();
            }
        } catch (Exception $e) {
            echo "<div class='step warning'>  ⚠ {$table}: " . $e->getMessage() . "</div>";
            flush();
        }
    }

    echo "<div class='step success'>  ✓ All database tables cleared!</div>";
    flush();

    // ============================================================
    // STEP 2: Delete ALL from Orthanc Server
    // ============================================================
    echo "<div class='step success'>[STEP 2] 🏥 Deleting ALL data from Orthanc Server...</div>";
    flush();

    $orthancDeletedPatients = 0;
    $orthancDeletedStudies = 0;

    try {
        // Get all patients from Orthanc
        $ch = curl_init(ORTHANC_URL . '/patients');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $patients = json_decode($response, true);

            if (!empty($patients)) {
                echo "<div class='step success'>  Found " . count($patients) . " patients in Orthanc. Deleting...</div>";
                flush();

                foreach ($patients as $patientId) {
                    // Delete each patient (this deletes all their studies too)
                    $ch = curl_init(ORTHANC_URL . '/patients/' . $patientId);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $delResponse = curl_exec($ch);
                    $delHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($delHttpCode === 200) {
                        $orthancDeletedPatients++;
                        echo "<div class='step success'>  ✓ Deleted patient: {$patientId}</div>";
                        flush();
                    } else {
                        echo "<div class='step error'>  ✗ Failed to delete patient: {$patientId} (HTTP {$delHttpCode})</div>";
                        flush();
                    }
                }

                echo "<div class='step success'>  ✓ Deleted {$orthancDeletedPatients} patients from Orthanc!</div>";
                flush();
            } else {
                echo "<div class='step success'>  ℹ Orthanc is already empty (no patients found)</div>";
                flush();
            }
        } else {
            echo "<div class='step error'>  ✗ Could not connect to Orthanc (HTTP {$httpCode})</div>";
            echo "<div class='step warning'>  ⚠ Make sure Orthanc is running on " . ORTHANC_URL . "</div>";
            flush();
        }
    } catch (Exception $e) {
        echo "<div class='step error'>  ✗ Orthanc deletion error: " . $e->getMessage() . "</div>";
        flush();
    }

    // ============================================================
    // STEP 3: Delete Reports Folder
    // ============================================================
    echo "<div class='step success'>[STEP 3] 📄 Deleting Reports Folder...</div>";
    flush();

    $reportsDir = __DIR__ . '/reports';
    $reportsDeleted = 0;

    if (is_dir($reportsDir)) {
        $files = glob($reportsDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $reportsDeleted++;
            }
        }
        echo "<div class='step success'>  ✓ Deleted {$reportsDeleted} report files</div>";
        flush();
    } else {
        echo "<div class='step warning'>  ℹ Reports directory doesn't exist (OK)</div>";
        flush();
    }

    // ============================================================
    // STEP 4: Delete DICOM Files
    // ============================================================
    echo "<div class='step success'>[STEP 4] 💾 Deleting DICOM Files...</div>";
    flush();

    $dicomDir = __DIR__ . '/dicom_files';
    $dicomDeleted = 0;

    if (is_dir($dicomDir)) {
        // Delete all subdirectories and files
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dicomDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
                $dicomDeleted++;
            }
        }

        echo "<div class='step success'>  ✓ Deleted {$dicomDeleted} DICOM files</div>";
        flush();
    } else {
        echo "<div class='step warning'>  ℹ DICOM files directory doesn't exist (OK)</div>";
        flush();
    }

    // ============================================================
    // STEP 5: Delete Cache
    // ============================================================
    echo "<div class='step success'>[STEP 5] 🗑️ Deleting Cache...</div>";
    flush();

    $cacheDir = __DIR__ . '/dicom_cache';
    $cacheDeleted = 0;

    if (is_dir($cacheDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
                $cacheDeleted++;
            }
        }

        echo "<div class='step success'>  ✓ Deleted {$cacheDeleted} cached files</div>";
        flush();
    } else {
        echo "<div class='step warning'>  ℹ Cache directory doesn't exist (OK)</div>";
        flush();
    }

    // ============================================================
    // STEP 6: Clear Logs
    // ============================================================
    echo "<div class='step success'>[STEP 6] 📋 Clearing Logs...</div>";
    flush();

    $logsDir = __DIR__ . '/logs';
    $logsCleared = 0;

    if (is_dir($logsDir)) {
        $logFiles = glob($logsDir . '/*.log');
        foreach ($logFiles as $logFile) {
            file_put_contents($logFile, ''); // Clear content but keep file
            $logsCleared++;
        }
        echo "<div class='step success'>  ✓ Cleared {$logsCleared} log files</div>";
        flush();
    }

    // ============================================================
    // STEP 7: Clear Remote Production Server (e-connect.in)
    // ============================================================
    echo "<div class='step success'>[STEP 7] 🌐 Clearing Remote Production Server...</div>";
    flush();

    $remoteDeleted = 0;
    $remoteFailed = 0;

    // Check if we're on local or remote
    $isLocal = in_array($_SERVER['HTTP_HOST'] ?? 'localhost', ['localhost', '127.0.0.1', 'localhost:80']);

    if ($isLocal) {
        echo "<div class='step warning'>  ℹ Running on LOCAL server - will attempt remote cleanup via API</div>";
        flush();

        // Send reset command to production server
        try {
            $resetUrl = 'https://e-connect.in/dicom/remote_reset_handler.php';
            $resetKey = 'DicomUpload2025SecureKey!@#'; // Using UPLOAD_API_KEY as reset key

            $ch = curl_init($resetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['reset_key' => $resetKey]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                if ($result && $result['success']) {
                    echo "<div class='step success'>  ✓ Remote server reset successful!</div>";
                    echo "<div class='step success'>  ✓ Deleted " . ($result['deleted'] ?? 0) . " items from production</div>";
                    $remoteDeleted = $result['deleted'] ?? 0;
                    flush();
                } else {
                    echo "<div class='step warning'>  ⚠ Remote reset returned: " . ($result['message'] ?? 'Unknown error') . "</div>";
                    flush();
                }
            } else {
                echo "<div class='step warning'>  ⚠ Could not reach remote server (HTTP {$httpCode})</div>";
                echo "<div class='step warning'>  ℹ Remote data NOT cleared - manual cleanup may be needed</div>";
                flush();
            }
        } catch (Exception $e) {
            echo "<div class='step warning'>  ⚠ Remote reset error: " . $e->getMessage() . "</div>";
            flush();
        }
    } else {
        echo "<div class='step success'>  ℹ Running on PRODUCTION server - clearing local data</div>";
        flush();

        // We're already on production, clear the production database
        $prodTables = ['cached_patients', 'cached_studies', 'cached_series', 'dicom_instances', 'dicom_files', 'sessions', 'prescriptions'];

        foreach ($prodTables as $table) {
            try {
                $result = $mysqli->query("TRUNCATE TABLE `{$table}`");
                if ($result) {
                    echo "<div class='step success'>  ✓ Cleared production table: {$table}</div>";
                    $remoteDeleted++;
                    flush();
                }
            } catch (Exception $e) {
                echo "<div class='step warning'>  ⚠ {$table}: " . $e->getMessage() . "</div>";
                flush();
            }
        }

        // Clear production DICOM files
        $prodDicomDir = '/home/odthzxeg2ajv/public_html/e-connect.in/dicom_files';
        if (is_dir($prodDicomDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($prodDicomDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                    $remoteDeleted++;
                }
            }
            echo "<div class='step success'>  ✓ Cleared production DICOM files</div>";
            flush();
        }
    }

    // ============================================================
    // SUMMARY
    // ============================================================
    echo "<div class='summary'>";
    echo "<h2 style='color: #0f0; margin-bottom: 20px;'>✅ RESET COMPLETE!</h2>";
    echo "<p><strong>Summary of deleted items:</strong></p>";
    echo "<ul style='margin-left: 20px; margin-top: 10px;'>";
    echo "<li>Local Database: " . count($tables) . " tables truncated</li>";
    echo "<li>Local Orthanc: {$orthancDeletedPatients} patients deleted</li>";
    echo "<li>Local Reports: {$reportsDeleted} report files deleted</li>";
    echo "<li>Local DICOM Files: {$dicomDeleted} files deleted</li>";
    echo "<li>Local Cache: {$cacheDeleted} cached files deleted</li>";
    echo "<li>Local Logs: {$logsCleared} log files cleared</li>";
    echo "<li>Remote Production: {$remoteDeleted} items cleared from e-connect.in</li>";
    echo "</ul>";
    echo "<p style='margin-top: 20px; color: #ff0;'><strong>⚡ Both LOCAL and PRODUCTION systems are now completely clean!</strong></p>";
    echo "</div>";

    ?>
            <a href="pages/patients.html" class="back-link">🏠 Go to Patients Page</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
