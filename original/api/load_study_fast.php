<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/load_study_debug.log');

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/session.php';
    
    $session = new SessionManager($mysqli);
    if (!$session->validateSession()) {
        ob_clean();
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Unauthorized']));
    }
    
    $studyUID = $_GET['studyUID'] ?? '';
    $orthancId = $_GET['orthanc_id'] ?? '';
    
    if (empty($studyUID) && empty($orthancId)) {
        ob_clean();
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Study UID required']));
    }
    
    if (!empty($studyUID) && empty($orthancId)) {
        $orthancId = $studyUID;
    }
    
    // Get study info from database
    $stmt = $mysqli->prepare("
        SELECT cs.orthanc_id, cs.study_instance_uid, cs.study_description, 
               cs.instance_count, cp.patient_name, cp.patient_id
        FROM cached_studies cs
        LEFT JOIN cached_patients cp ON cs.patient_id = cp.patient_id
        WHERE cs.orthanc_id = ? OR cs.study_instance_uid = ?
        LIMIT 1
    ");
    
    $stmt->bind_param('ss', $orthancId, $orthancId);
    $stmt->execute();
    $result = $stmt->get_result();
    $studyInfo = $result->fetch_assoc();
    $stmt->close();
    
    if (!$studyInfo) {
        ob_clean();
        http_response_code(404);
        die(json_encode([
            'success' => false,
            'error' => 'Study not found',
            'message' => 'Study not found in database'
        ]));
    }
    
    $orthancStudyId = $studyInfo['orthanc_id'];
    $actualStudyUID = $studyInfo['study_instance_uid'];
    
    // Load instances based on configuration
    if (USE_API_GATEWAY) {
        $images = loadFromApiGateway($orthancStudyId, $studyInfo);
    } else {
        $images = loadFromOrthanc($orthancStudyId, $studyInfo);
    }
    
    if (empty($images)) {
        ob_clean();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No DICOM files found',
            'message' => USE_API_GATEWAY 
                ? 'Could not fetch from API Gateway. Check if gateway is running.' 
                : 'No instances found',
            'studyId' => $orthancStudyId
        ]);
        exit;
    }
    
    $response = [
        'success' => true,
        'studyUID' => $actualStudyUID,
        'orthancId' => $orthancStudyId,
        'studyDescription' => $studyInfo['study_description'],
        'patientName' => $studyInfo['patient_name'],
        'images' => $images,
        'totalImages' => count($images),
        'imageCount' => count($images),
        'source' => USE_API_GATEWAY ? 'api_gateway' : 'orthanc_direct'
    ];
    
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function loadFromApiGateway($orthancStudyId, $studyInfo) {
    $apiUrl = API_GATEWAY_URL;
    $apiKey = API_GATEWAY_KEY;
    
    if (empty($apiUrl) || empty($apiKey)) {
        throw new Exception('API Gateway not configured in config.php');
    }
    
    $ch = curl_init("{$apiUrl}/gateway/studies/{$orthancStudyId}/instances");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: {$apiKey}"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        error_log("API Gateway failed: HTTP $httpCode, Error: $error");
        throw new Exception("API Gateway request failed (HTTP $httpCode)");
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !$data['success']) {
        throw new Exception('Invalid response from API Gateway');
    }
    
    $images = [];
    foreach ($data['instances'] as $instance) {
        $images[] = [
            'instanceId' => $instance['instanceId'],
            'orthancInstanceId' => $instance['instanceId'],
            'seriesInstanceUID' => $instance['seriesInstanceUID'],
            'sopInstanceUID' => $instance['sopInstanceUID'],
            'instanceNumber' => $instance['instanceNumber'],
            'seriesDescription' => $instance['seriesDescription'],
            'seriesNumber' => $instance['seriesNumber'],
            'patientName' => $studyInfo['patient_name'],
            'useApiGateway' => true,
            'isOrthancImage' => true
        ];
    }

    return $images;
}

function loadFromOrthanc($orthancStudyId, $studyInfo) {
    $orthancUrl = ORTHANC_URL;
    $orthancUser = ORTHANC_USER;
    $orthancPass = ORTHANC_PASS;
    
    if (empty($orthancUrl)) {
        throw new Exception('Orthanc not configured');
    }
    
    $studyData = fetchOrthancData("{$orthancUrl}/studies/{$orthancStudyId}", $orthancUser, $orthancPass);
    
    if (!$studyData) {
        throw new Exception('Study not found in Orthanc');
    }
    
    $images = [];
    
    if (isset($studyData['Series']) && is_array($studyData['Series'])) {
        foreach ($studyData['Series'] as $seriesId) {
            $seriesData = fetchOrthancData("{$orthancUrl}/series/{$seriesId}", $orthancUser, $orthancPass);
            
            if ($seriesData && isset($seriesData['Instances'])) {
                $seriesDesc = $seriesData['MainDicomTags']['SeriesDescription'] ?? 'Series';
                $seriesUID = $seriesData['MainDicomTags']['SeriesInstanceUID'] ?? $seriesId;
                $seriesNumber = $seriesData['MainDicomTags']['SeriesNumber'] ?? 0;
                
                foreach ($seriesData['Instances'] as $instanceId) {
                    $instanceData = fetchOrthancData("{$orthancUrl}/instances/{$instanceId}", $orthancUser, $orthancPass);
                    
                    if ($instanceData) {
                        $images[] = [
                            'instanceId' => $instanceId,
                            'orthancInstanceId' => $instanceId,
                            'seriesInstanceUID' => $seriesUID,
                            'sopInstanceUID' => $instanceData['MainDicomTags']['SOPInstanceUID'] ?? $instanceId,
                            'instanceNumber' => intval($instanceData['MainDicomTags']['InstanceNumber'] ?? 0),
                            'seriesDescription' => $seriesDesc,
                            'seriesNumber' => intval($seriesNumber),
                            'patientName' => $studyInfo['patient_name'],
                            'useApiGateway' => false,
                            'isOrthancImage' => true
                        ];
                    }
                }
            }
        }
    }
    
    usort($images, function($a, $b) {
        $seriesCompare = $a['seriesNumber'] - $b['seriesNumber'];
        if ($seriesCompare !== 0) return $seriesCompare;
        return $a['instanceNumber'] - $b['instanceNumber'];
    });
    
    return $images;
}

function fetchOrthancData($url, $user, $pass) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$user}:{$pass}");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $result) {
        return json_decode($result, true);
    }
    return null;
}
?>