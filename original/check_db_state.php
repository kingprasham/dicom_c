<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

try {
    // Check patients
    $patientCount = $mysqli->query("SELECT COUNT(*) as c FROM cached_patients")->fetch_assoc()['c'];

    // Check studies
    $studyCount = $mysqli->query("SELECT COUNT(*) as c FROM cached_studies")->fetch_assoc()['c'];

    // Check users
    $userCount = $mysqli->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];

    // Check if dicom_instances table exists
    $tableExists = $mysqli->query("SHOW TABLES LIKE 'dicom_instances'")->num_rows > 0;
    $instanceCount = 0;
    if ($tableExists) {
        $instanceCount = $mysqli->query("SELECT COUNT(*) as c FROM dicom_instances")->fetch_assoc()['c'];
    }

    echo json_encode([
        'success' => true,
        'environment' => ENVIRONMENT,
        'database' => DB_NAME,
        'orthanc_url' => ORTHANC_URL,
        'counts' => [
            'patients' => (int)$patientCount,
            'studies' => (int)$studyCount,
            'users' => (int)$userCount,
            'dicom_instances' => (int)$instanceCount,
            'dicom_instances_table_exists' => $tableExists
        ]
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
