<?php
require_once __DIR__ . '/includes/config.php';

echo "Setting up database tables...\n";

$mysqli = getDbConnection();

// Create cached_patients table
$sqlPatients = "CREATE TABLE IF NOT EXISTS cached_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orthanc_id VARCHAR(255) NOT NULL,
    patient_id VARCHAR(255) NOT NULL,
    patient_name VARCHAR(255),
    patient_birth_date DATE,
    patient_sex VARCHAR(10),
    study_count INT DEFAULT 0,
    last_study_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (patient_id),
    INDEX (orthanc_id)
)";

if ($mysqli->query($sqlPatients)) {
    echo "Table 'cached_patients' created or already exists.\n";
} else {
    echo "Error creating 'cached_patients': " . $mysqli->error . "\n";
}

// Create cached_studies table
$sqlStudies = "CREATE TABLE IF NOT EXISTS cached_studies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    study_instance_uid VARCHAR(255) NOT NULL,
    orthanc_id VARCHAR(255) NOT NULL,
    patient_id VARCHAR(255) NOT NULL,
    study_date DATE,
    study_time VARCHAR(20),
    study_description VARCHAR(255),
    accession_number VARCHAR(255),
    modality VARCHAR(50),
    series_count INT DEFAULT 0,
    instance_count INT DEFAULT 0,
    instances_count INT DEFAULT 0,
    last_synced DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (study_instance_uid),
    INDEX (patient_id),
    INDEX (orthanc_id)
)";

if ($mysqli->query($sqlStudies)) {
    echo "Table 'cached_studies' created or already exists.\n";
} else {
    echo "Error creating 'cached_studies': " . $mysqli->error . "\n";
}

// Add is_starred column to dicom_files table if the table exists
$checkTable = $mysqli->query("SHOW TABLES LIKE 'dicom_files'");
if ($checkTable && $checkTable->num_rows > 0) {
    // Check if column already exists
    $checkColumn = $mysqli->query("SHOW COLUMNS FROM dicom_files LIKE 'is_starred'");
    if ($checkColumn && $checkColumn->num_rows == 0) {
        // Column doesn't exist, add it
        $alterSql = "ALTER TABLE dicom_files ADD COLUMN is_starred INT DEFAULT 0";
        if ($mysqli->query($alterSql)) {
            echo "Added 'is_starred' column to 'dicom_files' table.\n";
        } else {
            echo "Error adding 'is_starred' column: " . $mysqli->error . "\n";
        }
    } else {
        echo "'is_starred' column already exists in 'dicom_files' table.\n";
    }
} else {
    echo "Note: 'dicom_files' table does not exist yet. Run this script after uploading DICOM files.\n";
}

// Add is_starred column to cached_studies table if it exists
$checkStudiesTable = $mysqli->query("SHOW TABLES LIKE 'cached_studies'");
if ($checkStudiesTable && $checkStudiesTable->num_rows > 0) {
    $checkStudiesColumn = $mysqli->query("SHOW COLUMNS FROM cached_studies LIKE 'is_starred'");
    if ($checkStudiesColumn && $checkStudiesColumn->num_rows == 0) {
        $alterStudiesSql = "ALTER TABLE cached_studies ADD COLUMN is_starred INT DEFAULT 0";
        if ($mysqli->query($alterStudiesSql)) {
            echo "Added 'is_starred' column to 'cached_studies' table.\n";
        } else {
            echo "Error adding 'is_starred' column to cached_studies: " . $mysqli->error . "\n";
        }
    } else {
        echo "'is_starred' column already exists in 'cached_studies' table.\n";
    }
}

// Note: Database connection is automatically closed by the shutdown function in config.php
echo "Database setup complete.\n";
?>
