<?php
/**
 * Study List API - Enhanced with debugging
 */

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/study_list_api.log');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';

// Log for debugging
function logDebug($message, $data = null) {
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($data !== null) {
        $logMessage .= ' ' . json_encode($data);
    }
    error_log($logMessage);
}

logDebug('Study List API Called', [
    'patient_id' => $_GET['patient_id'] ?? 'none',
    'environment' => ENVIRONMENT,
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'none'
]);

// Validate session
$session = new SessionManager($mysqli);
if (!$session->validateSession()) {
    http_response_code(401);
    logDebug('Unauthorized access attempt');
    die(json_encode(['success' => false, 'error' => 'Unauthorized - Please login']));
}

try {
    // Get patient_id - handle "0" as valid
    $patientId = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
    
    if ($patientId === null || $patientId === '') {
        logDebug('No patient ID provided');
        throw new Exception('Patient ID required');
    }
    
    logDebug('Looking for patient', ['patient_id' => $patientId]);
    
    // Get patient info
    $stmt = $mysqli->prepare("SELECT * FROM cached_patients WHERE patient_id = ?");
    $stmt->bind_param('s', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();
    
    if (!$patient) {
        logDebug('Patient not found with patient_id, trying orthanc_id');
        // Try to find by orthanc_id as fallback
        $stmt = $mysqli->prepare("SELECT * FROM cached_patients WHERE orthanc_id = ?");
        $stmt->bind_param('s', $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();
        
        if (!$patient) {
            logDebug('Patient not found in cache');
            throw new Exception('Patient not found in cache');
        }
    }
    
    logDebug('Patient found', ['patient_name' => $patient['patient_name']]);
    
    // Get studies for this patient
    $stmt = $mysqli->prepare("
        SELECT * FROM cached_studies 
        WHERE patient_id = ? 
        ORDER BY study_date DESC, study_time DESC
    ");
    $stmt->bind_param('s', $patient['patient_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $studies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    logDebug('Studies retrieved', ['count' => count($studies)]);
    
    // If no studies found for this patient_id, let's check if there are ANY studies in the database
    if (count($studies) === 0) {
        $totalStudiesResult = $mysqli->query("SELECT COUNT(*) as total FROM cached_studies");
        $totalRow = $totalStudiesResult->fetch_assoc();
        logDebug('No studies found for patient. Total studies in DB', ['total' => $totalRow['total']]);
        
        // Check if there are studies with a different patient_id format
        $stmt = $mysqli->prepare("SELECT DISTINCT patient_id FROM cached_studies LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        $samplePatientIds = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        logDebug('Sample patient_ids in studies table', $samplePatientIds);
    }
    
    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'studies' => $studies,
        'count' => count($studies),
        'debug' => DEBUG_MODE ? [
            'environment' => ENVIRONMENT,
            'db_name' => DB_NAME,
            'query_patient_id' => $patient['patient_id']
        ] : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    logDebug('Error occurred', ['error' => $e->getMessage()]);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'patient_id_received' => $_GET['patient_id'] ?? 'none',
        'debug' => DEBUG_MODE ? [
            'environment' => ENVIRONMENT,
            'db_name' => DB_NAME,
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}
?>
