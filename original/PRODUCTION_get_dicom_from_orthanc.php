<?php
/**
 * DICOM File Proxy - Fetches DICOM files from Orthanc via API Gateway or Direct
 * Bypasses CORS and authentication issues
 *
 * PRODUCTION VERSION with API Gateway Support
 * Upload this to: /public_html/dicom/api/get_dicom_from_orthanc.php
 */

// Set headers first
header('Content-Type: application/dicom');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load config
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    error_log("DICOM Proxy Error: config.php not found at: $configPath");
    die('Configuration file not found');
}

require_once $configPath;

// Validate instance ID
$instanceId = $_GET['instanceId'] ?? '';

if (empty($instanceId)) {
    http_response_code(400);
    error_log("DICOM Proxy Error: No instance ID provided");
    die('Instance ID required');
}

// Check if cURL is available
if (!function_exists('curl_init')) {
    http_response_code(500);
    error_log("DICOM Proxy Error: cURL extension not available");
    die('cURL extension not available');
}

// Determine if using API Gateway or direct Orthanc
$useGateway = defined('USE_API_GATEWAY') && USE_API_GATEWAY === true;

if ($useGateway) {
    // ============================================
    // API GATEWAY MODE (Production with ngrok)
    // ============================================

    if (!defined('API_GATEWAY_URL')) {
        http_response_code(500);
        error_log("DICOM Proxy Error: API_GATEWAY_URL not defined in config.php");
        die('API Gateway URL not configured');
    }

    if (!defined('API_GATEWAY_KEY')) {
        http_response_code(500);
        error_log("DICOM Proxy Error: API_GATEWAY_KEY not defined in config.php");
        die('API Gateway key not configured');
    }

    // Build API Gateway URL
    $gatewayUrl = rtrim(API_GATEWAY_URL, '/') . '/get_dicom_instance';
    error_log("DICOM Proxy: Using API Gateway: $gatewayUrl");

    // Prepare request data
    $requestData = json_encode([
        'instance_id' => $instanceId
    ]);

    // Initialize cURL for API Gateway
    $ch = curl_init($gatewayUrl);
    if (!$ch) {
        http_response_code(500);
        error_log("DICOM Proxy Error: Failed to initialize cURL for API Gateway");
        die('Failed to initialize HTTP client');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . API_GATEWAY_KEY
    ]);

    error_log("DICOM Proxy: Fetching instance $instanceId via API Gateway");

} else {
    // ============================================
    // DIRECT ORTHANC MODE (Localhost)
    // ============================================

    if (!defined('ORTHANC_URL')) {
        http_response_code(500);
        error_log("DICOM Proxy Error: ORTHANC_URL not defined in config.php");
        die('Orthanc URL not configured');
    }

    // Build Orthanc URL
    $orthancUrl = ORTHANC_URL . "/instances/{$instanceId}/file";
    error_log("DICOM Proxy: Using direct Orthanc: $orthancUrl");

    // Initialize cURL for direct Orthanc
    $ch = curl_init($orthancUrl);
    if (!$ch) {
        http_response_code(500);
        error_log("DICOM Proxy Error: Failed to initialize cURL for Orthanc");
        die('Failed to initialize HTTP client');
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    // Add authentication if configured
    if (defined('ORTHANC_USER') && defined('ORTHANC_PASS')) {
        curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        error_log("DICOM Proxy: Using authentication for Orthanc");
    }
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

// Log the result
if ($curlErrno) {
    error_log("DICOM Proxy Error: cURL error #$curlErrno: $curlError");
}

$source = $useGateway ? 'API Gateway' : 'Orthanc';
error_log("DICOM Proxy: $source responded with HTTP $httpCode for instance $instanceId");

// Parse response if using API Gateway
if ($useGateway && $httpCode === 200 && $response) {
    $jsonResponse = json_decode($response, true);

    if (json_last_error() === JSON_ERROR_NONE && isset($jsonResponse['success'])) {
        if ($jsonResponse['success'] === true && isset($jsonResponse['dicom_data'])) {
            // Decode base64 DICOM data from API Gateway
            $dicomData = base64_decode($jsonResponse['dicom_data']);

            if ($dicomData === false) {
                http_response_code(500);
                error_log("DICOM Proxy Error: Failed to decode base64 DICOM data from API Gateway");
                die('Failed to decode DICOM data');
            }

            error_log("DICOM Proxy: Successfully decoded " . strlen($dicomData) . " bytes from API Gateway");
        } else {
            http_response_code(500);
            $errorMsg = $jsonResponse['error'] ?? 'Unknown error from API Gateway';
            error_log("DICOM Proxy Error: API Gateway returned error: $errorMsg");
            die("API Gateway error: $errorMsg");
        }
    } else {
        // Response is not JSON, might be direct DICOM data
        $dicomData = $response;
        error_log("DICOM Proxy: Received non-JSON response from API Gateway, treating as raw DICOM data");
    }
} else {
    // Direct Orthanc mode - response is raw DICOM data
    $dicomData = $response;
}

// Check response
if ($httpCode !== 200 || !$dicomData) {
    http_response_code($httpCode ?: 500);

    $errorMsg = "Failed to fetch DICOM file from $source. ";
    $errorMsg .= "HTTP Code: $httpCode. ";
    if ($curlError) {
        $errorMsg .= "cURL Error: $curlError. ";
    }
    $errorMsg .= "Instance ID: $instanceId";

    error_log("DICOM Proxy Error: $errorMsg");
    die($errorMsg);
}

// Success - send DICOM data
error_log("DICOM Proxy: Successfully fetched " . strlen($dicomData) . " bytes for instance $instanceId from $source");

header('Content-Length: ' . strlen($dicomData));
header('Content-Disposition: inline; filename="' . $instanceId . '.dcm"');
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year

echo $dicomData;
?>
