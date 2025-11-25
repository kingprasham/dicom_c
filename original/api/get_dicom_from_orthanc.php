<?php
/**
 * DICOM File Proxy - Fetches DICOM files from Orthanc via API Gateway or Direct
 * Bypasses CORS and authentication issues
 */

header('Content-Type: application/dicom');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

$instanceId = $_GET['instanceId'] ?? '';

if (empty($instanceId)) {
    http_response_code(400);
    die('Instance ID required');
}

// Determine if using API Gateway or direct Orthanc
$useGateway = defined('USE_API_GATEWAY') && USE_API_GATEWAY === true;

if ($useGateway) {
    // API GATEWAY MODE (Production)
    $gatewayUrl = rtrim(API_GATEWAY_URL, '/') . '/get_dicom_instance';

    $requestData = json_encode(['instance_id' => $instanceId]);

    $ch = curl_init($gatewayUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . API_GATEWAY_KEY
    ]);
} else {
    // DIRECT ORTHANC MODE (Localhost)
    $orthancUrl = ORTHANC_URL . "/instances/{$instanceId}/file";

    $ch = curl_init($orthancUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Add auth if configured
    if (defined('ORTHANC_USER') && defined('ORTHANC_PASS')) {
        curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    }
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Parse response if using API Gateway
if ($useGateway && $httpCode === 200 && $response) {
    $jsonResponse = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['success']) && $jsonResponse['success']) {
        $dicomData = base64_decode($jsonResponse['dicom_data']);
    } else {
        $dicomData = $response; // Fallback to raw data
    }
} else {
    $dicomData = $response;
}

if ($httpCode !== 200 || !$dicomData) {
    http_response_code($httpCode);
    error_log("Failed to fetch DICOM file: instanceId=$instanceId, httpCode=$httpCode");
    die('Failed to fetch DICOM file');
}

// Set headers for DICOM
header('Content-Length: ' . strlen($dicomData));
header('Content-Disposition: inline; filename="' . $instanceId . '.dcm"');
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year

echo $dicomData;
?>
