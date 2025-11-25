<?php
/**
 * Get DICOM via API Gateway
 * Fetches DICOM files from local Orthanc via ngrok tunnel
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/dicom');
header('Access-Control-Allow-Origin: *');

$instanceId = $_GET['instanceId'] ?? '';

if (empty($instanceId)) {
    http_response_code(400);
    die('Instance ID required');
}

// Check if we should use API gateway
if (!USE_API_GATEWAY || empty(API_GATEWAY_URL) || empty(API_GATEWAY_KEY)) {
    http_response_code(500);
    die('API Gateway not configured');
}

$apiUrl = rtrim(API_GATEWAY_URL, '/') . '/api/instances/' . urlencode($instanceId) . '/file';

// Fetch from API gateway
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . API_GATEWAY_KEY
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$fileData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    error_log("Failed to fetch DICOM from gateway: HTTP $httpCode, Error: $error");
    die('Failed to fetch DICOM file from gateway');
}

if (!$fileData) {
    http_response_code(404);
    die('Empty response from gateway');
}

// Set headers and stream the file
header('Content-Length: ' . strlen($fileData));
header('Content-Disposition: inline; filename="' . $instanceId . '.dcm"');
header('Cache-Control: public, max-age=3600');

echo $fileData;
exit;
?>
