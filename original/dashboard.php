<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Get stats
$patientCount = $mysqli->query("SELECT COUNT(*) as c FROM cached_patients")->fetch_assoc()['c'];
$studyCount = $mysqli->query("SELECT COUNT(*) as c FROM cached_studies")->fetch_assoc()['c'];
$userCount = $mysqli->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];

// Get Orthanc status
function checkOrthanc() {
    $ch = curl_init(ORTHANC_URL . '/system');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $httpCode === 200;
}

$orthancStatus = checkOrthanc() ? 'online' : 'offline';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICOM System Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            min-height: 100vh;
        }
        .dashboard-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
        }
        .stat-card {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            color: white;
            height: 100%;
        }
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        .status-online {
            background: #198754;
            color: white;
        }
        .status-offline {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="text-center mb-5">
            <h1 class="display-4 text-white mb-3">
                <i class="bi bi-heart-pulse-fill text-primary"></i>
                DICOM System Dashboard
            </h1>
            <p class="lead text-light">Local Development Environment</p>
        </div>

        <!-- System Status -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <h3 class="text-white mb-3">
                        <i class="bi bi-activity me-2"></i>System Status
                    </h3>
                    <div class="row">
                        <div class="col-md-3">
                            <strong class="text-light">Environment:</strong>
                            <div class="text-info"><?php echo ENVIRONMENT; ?></div>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-light">Database:</strong>
                            <div class="text-success"><?php echo DB_NAME; ?> (Connected)</div>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-light">Orthanc Server:</strong>
                            <div>
                                <span class="status-badge status-<?php echo $orthancStatus; ?>">
                                    <?php echo strtoupper($orthancStatus); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <strong class="text-light">Orthanc URL:</strong>
                            <div class="text-info"><?php echo ORTHANC_URL; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="bi bi-people-fill" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $patientCount; ?></div>
                    <div>Cached Patients</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="bi bi-file-medical-fill" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $studyCount; ?></div>
                    <div>Cached Studies</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="bi bi-person-badge-fill" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $userCount; ?></div>
                    <div>System Users</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <h3 class="text-white mb-4">
                        <i class="bi bi-lightning-charge-fill me-2"></i>Quick Actions
                    </h3>
                    <div class="d-grid gap-3">
                        <a href="pages/patients.html" class="btn btn-primary btn-lg">
                            <i class="bi bi-list-ul me-2"></i>View Patient Worklist
                        </a>
                        <a href="sync_orthanc.php" class="btn btn-success btn-lg">
                            <i class="bi bi-arrow-clockwise me-2"></i>Sync from Orthanc
                        </a>
                        <a href="<?php echo ORTHANC_URL; ?>/app/explorer.html" target="_blank" class="btn btn-info btn-lg">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Open Orthanc Explorer
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="row">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <h3 class="text-white mb-3">
                        <i class="bi bi-info-circle-fill me-2"></i>Getting Started
                    </h3>
                    <ol class="text-light">
                        <li class="mb-2">
                            <strong>Upload DICOM files to Orthanc:</strong>
                            Use the Orthanc Explorer or send files from your MRI machine to Orthanc server (localhost:4242)
                        </li>
                        <li class="mb-2">
                            <strong>Sync data:</strong>
                            Click "Sync from Orthanc" to cache patient and study data in the local database
                        </li>
                        <li class="mb-2">
                            <strong>View patients:</strong>
                            Go to "View Patient Worklist" to see all patients and their studies
                        </li>
                        <li class="mb-2">
                            <strong>Auto-refresh:</strong>
                            Patient list automatically refreshes every 5 minutes, or use the "Refresh Data" button
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-light mt-5 mb-3">
            <small>DICOM Viewer Pro - Local Development</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
