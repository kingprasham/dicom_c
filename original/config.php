<?php
// Detect environment more reliably
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = (
    strpos($httpHost, 'localhost') !== false || 
    strpos($httpHost, '127.0.0.1') !== false ||
    strpos($httpHost, 'papa') !== false  // Detect /papa/ subdirectory
);

// Enable debugging to see which config is being used
define('DEBUG_MODE', $isLocal);

if ($isLocal) {
    // LOCAL CONFIGURATION
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'dicom');  // Make sure this is the SAME database as production uses locally
    define('DICOM_STORAGE_PATH', __DIR__ . '/dicom_files');
    define('ORTHANC_URL', 'http://localhost:8042');
    define('ORTHANC_USER', 'orthanc');
    define('ORTHANC_PASS', 'orthanc');
    define('USE_API_GATEWAY', false);
    define('ENVIRONMENT', 'LOCAL');
} else {
    // PRODUCTION CONFIGURATION
    define('DB_HOST', 'localhost');
    define('DB_USER', 'acc_admin');
    define('DB_PASS', 'Prasham123$');
    define('DB_NAME', 'dicom');
    define('DICOM_STORAGE_PATH', '/home/odthzxeg2ajv/public_html/e-connect.in/dicom_files');
    
    // API Gateway Configuration
    define('USE_API_GATEWAY', true);
    define('API_GATEWAY_URL', 'https://brendon-interannular-nonconnectively.ngrok-free.dev/');
    define('API_GATEWAY_KEY', 'Hospital2025_DicomSecureKey_XyZ789ABC');
    define('ENVIRONMENT', 'PRODUCTION');
}

define('UPLOAD_API_KEY', 'DicomUpload2025SecureKey!@#');
define('SESSION_TIMEOUT', 3600);
define('SESSION_NAME', 'DICOM_SESSION');
define('SESSION_LIFETIME', 3600);

date_default_timezone_set('UTC');

error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? 1 : 0);  // Show errors in local
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

$dirs = [
    __DIR__ . '/logs',
    defined('DICOM_STORAGE_PATH') ? constant('DICOM_STORAGE_PATH') : __DIR__ . '/dicom_files'
];

foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

define('BASE_URL', $isLocal ? 'http://localhost/papa/dicom_again' : 'https://e-connect.in/dicom');
?>
