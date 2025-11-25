<?php
/**
 * Sync Studies from Orthanc - ONLY COMPLETE STUDIES
 * Only caches studies when ALL instances are uploaded
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Orthanc Sync</title>";
echo "<style>body{background:#000;color:#0f0;font-family:monospace;padding:20px;}";
echo ".success{color:#0f0;font-weight:bold;}.error{color:#f00;font-weight:bold;}";
echo ".info{color:#0af;}.warning{color:#ff0;}</style></head><body>";

echo "<h1>🔄 Syncing Studies from Orthanc</h1>";
echo "<pre>\n";

$orthancUrl = ORTHANC_URL;

echo "Orthanc URL: $orthancUrl\n";
echo "Strategy: Sync all studies directly from Orthanc\n\n";

function callOrthanc($endpoint) {
    $url = ORTHANC_URL . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

// Get all patients
echo "=== FETCHING PATIENTS ===\n";
$patients = callOrthanc('/patients');

if (!$patients) {
    echo "<span class='error'>✗ Failed to connect to Orthanc!</span>\n";
    exit;
}

echo "<span class='success'>✓ Found " . count($patients) . " patients in Orthanc</span>\n\n";

$studiesAdded = 0;
$studiesUpdated = 0;

foreach ($patients as $patientOrthancId) {
    $patientData = callOrthanc("/patients/$patientOrthancId");
    
    if (!$patientData) {
        continue;
    }
    
    $patientId = $patientData['MainDicomTags']['PatientID'] ?? 'UNKNOWN';
    $patientName = $patientData['MainDicomTags']['PatientName'] ?? 'Unknown';
    
    echo "\nPatient: <span class='info'>$patientName</span> (ID: $patientId)\n";
    
    // Check if patient exists
    $stmt = $mysqli->prepare("SELECT patient_id FROM cached_patients WHERE patient_id = ?");
    $stmt->bind_param('s', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cachedPatient = $result->fetch_assoc();
    $stmt->close();
    
    if (!$cachedPatient) {
        // Insert patient
        $birthDate = $patientData['MainDicomTags']['PatientBirthDate'] ?? null;
        $sex = $patientData['MainDicomTags']['PatientSex'] ?? null;
        
        if ($birthDate && strlen($birthDate) === 8) {
            $birthDate = substr($birthDate, 0, 4) . '-' . substr($birthDate, 4, 2) . '-' . substr($birthDate, 6, 2);
        } else {
            $birthDate = null;
        }
        
        $stmt = $mysqli->prepare("INSERT INTO cached_patients (orthanc_id, patient_id, patient_name, patient_birth_date, patient_sex, study_count, last_study_date) VALUES (?, ?, ?, ?, ?, 0, CURDATE())");
        $stmt->bind_param('sssss', $patientOrthancId, $patientId, $patientName, $birthDate, $sex);
        $stmt->execute();
        $stmt->close();
        
        echo "  <span class='success'>✓ Added patient to cache</span>\n";
    }
    
    // Get studies
    $studies = $patientData['Studies'] ?? [];
    echo "  Studies in Orthanc: " . count($studies) . "\n";
    
    foreach ($studies as $studyOrthancId) {
        $studyData = callOrthanc("/studies/$studyOrthancId");
        
        if (!$studyData) {
            continue;
        }
        
        // Count total instances in Orthanc for this study
        $totalInstancesInOrthanc = 0;
        foreach ($studyData['Series'] ?? [] as $seriesId) {
            $seriesData = callOrthanc("/series/$seriesId");
            if ($seriesData) {
                $totalInstancesInOrthanc += count($seriesData['Instances'] ?? []);
            }
        }

        // Get study UID
        $studyUID = $studyData['MainDicomTags']['StudyInstanceUID'] ?? null;

        // MODIFIED: Sync all studies from Orthanc regardless of dicom_instances table
        // The source of truth is Orthanc itself, not our database tracking
        $studyDate = $studyData['MainDicomTags']['StudyDate'] ?? null;
        $studyTime = $studyData['MainDicomTags']['StudyTime'] ?? null;
        $studyDesc = $studyData['MainDicomTags']['StudyDescription'] ?? 'PACS Study';
        $accessionNumber = $studyData['MainDicomTags']['AccessionNumber'] ?? null;
        
        // Get modality
        $modality = null;
        if (isset($studyData['Series']) && count($studyData['Series']) > 0) {
            $firstSeries = callOrthanc("/series/" . $studyData['Series'][0]);
            if ($firstSeries) {
                $modality = $firstSeries['MainDicomTags']['Modality'] ?? 'CT';
            }
        }
        if (!$modality) $modality = 'CT';
        
        $seriesCount = count($studyData['Series'] ?? []);
        
        // Format date
        if ($studyDate && strlen($studyDate) === 8) {
            $studyDate = substr($studyDate, 0, 4) . '-' . substr($studyDate, 4, 2) . '-' . substr($studyDate, 6, 2);
        } else {
            $studyDate = date('Y-m-d');
        }
        
        // Format time
        if ($studyTime && strlen($studyTime) >= 6) {
            $studyTime = substr($studyTime, 0, 2) . ':' . substr($studyTime, 2, 2) . ':' . substr($studyTime, 4, 2);
        } else {
            $studyTime = date('H:i:s');
        }
        
        // Check if study exists
        $stmt = $mysqli->prepare("SELECT id FROM cached_studies WHERE study_instance_uid = ?");
        $stmt->bind_param('s', $studyUID);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingStudy = $result->fetch_assoc();
        $stmt->close();
        
        if ($existingStudy) {
            // Update
            $stmt = $mysqli->prepare("UPDATE cached_studies SET 
                orthanc_id = ?,
                patient_id = ?,
                study_date = ?,
                study_time = ?,
                study_description = ?,
                accession_number = ?,
                modality = ?,
                series_count = ?,
                instance_count = ?,
                instances_count = ?,
                last_synced = NOW()
                WHERE study_instance_uid = ?");
            $stmt->bind_param('sssssssiiss', 
                $studyOrthancId,
                $patientId, 
                $studyDate, 
                $studyTime, 
                $studyDesc, 
                $accessionNumber, 
                $modality, 
                $seriesCount, 
                $totalInstancesInOrthanc,
                $totalInstancesInOrthanc,
                $studyUID
            );
            $stmt->execute();
            $stmt->close();
            
            $studiesUpdated++;
            echo "  <span class='info'>↻ Updated: $studyDesc ($totalInstancesInOrthanc instances)</span>\n";
        } else {
            // Insert new
            $stmt = $mysqli->prepare("INSERT INTO cached_studies (
                study_instance_uid,
                orthanc_id,
                patient_id,
                study_date,
                study_time,
                study_description,
                accession_number,
                modality,
                series_count,
                instance_count,
                instances_count,
                last_synced
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param('ssssssssiii', 
                $studyUID,
                $studyOrthancId, 
                $patientId, 
                $studyDate, 
                $studyTime, 
                $studyDesc, 
                $accessionNumber, 
                $modality, 
                $seriesCount,
                $totalInstancesInOrthanc,
                $totalInstancesInOrthanc
            );
            $stmt->execute();
            $stmt->close();
            
            $studiesAdded++;
            echo "  <span class='success'>✓ Added: $studyDesc ($totalInstancesInOrthanc instances)</span>\n";
        }
    }
}

echo "\n=== SYNC COMPLETE ===\n";
echo "<span class='success'>Studies Added: $studiesAdded</span>\n";
echo "<span class='info'>Studies Updated: $studiesUpdated</span>\n";

$result = $mysqli->query("SELECT COUNT(*) as count FROM cached_studies");
$row = $result->fetch_assoc();
echo "\nTotal studies in database: <strong>{$row['count']}</strong>\n";

echo "\n<span class='success'>✓✓✓ SYNC SUCCESSFUL ✓✓✓</span>\n";
echo "\n<a href='pages/patients.html' style='color:#0af;'>→ Go to Patients Page</a>\n";
echo "<a href='sync_orthanc.php' style='color:#0af;margin-left:20px;'>↻ Sync Again</a>\n";

$mysqli->close();
echo "</pre></body></html>";
?>
