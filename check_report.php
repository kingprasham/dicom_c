<?php
/**
 * Check if a report exists for an image/study
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/auth/session.php';

// Validate session
if (!isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$imageId = $_GET['imageId'] ?? '';
$studyUID = $_GET['studyUID'] ?? '';

if (empty($imageId) && empty($studyUID)) {
    http_response_code(400);
    die(json_encode(['error' => 'Image ID or Study UID required']));
}

try {
    $mysqli = getDbConnection();

    // Check if report exists for this image or study
    $query = "SELECT id, report_type, created_at FROM reports WHERE ";

    if (!empty($imageId)) {
        $query .= "image_id = ? OR instance_id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ss', $imageId, $imageId);
    } else {
        $query .= "study_uid = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('s', $studyUID);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if ($report) {
        echo json_encode([
            'success' => true,
            'hasReport' => true,
            'reportId' => $report['id'],
            'reportType' => $report['report_type'],
            'createdAt' => $report['created_at']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'hasReport' => false
        ]);
    }

} catch (Exception $e) {
    // Table might not exist, return no report
    echo json_encode([
        'success' => true,
        'hasReport' => false,
        'note' => 'Reports table not configured'
    ]);
}
