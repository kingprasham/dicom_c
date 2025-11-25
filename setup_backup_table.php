<?php
/**
 * Setup backup_history table
 */
define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';

$db = getDbConnection();

$sql = "CREATE TABLE IF NOT EXISTS backup_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_filename VARCHAR(255),
    gdrive_file_id VARCHAR(255),
    backup_size_bytes BIGINT,
    backup_date DATETIME,
    backup_type VARCHAR(50) COMMENT 'manual or scheduled',
    status VARCHAR(50) COMMENT 'completed, failed, in_progress',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (backup_date),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql)) {
    echo "✓ Table 'backup_history' created successfully\n";
} else {
    echo "✗ Error creating table: " . $db->error . "\n";
}

$db->close();
?>
