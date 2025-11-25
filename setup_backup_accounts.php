<?php
/**
 * Setup backup accounts and scheduling tables
 */
define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';

$db = getDbConnection();

echo "Creating backup management tables...\n\n";

// Create backup_accounts table
$sql1 = "CREATE TABLE IF NOT EXISTS backup_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL COMMENT 'Friendly name for the account',
    credentials_json TEXT NOT NULL COMMENT 'Google service account credentials',
    service_account_email VARCHAR(255) NOT NULL,
    folder_name VARCHAR(255) DEFAULT 'DICOM_Viewer_Backups',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=active, 0=disabled',
    last_backup_date DATETIME NULL,
    last_backup_status VARCHAR(50) NULL COMMENT 'success, failed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (is_active),
    INDEX (last_backup_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql1)) {
    echo "✓ Table 'backup_accounts' created successfully\n";
} else {
    echo "✗ Error: " . $db->error . "\n";
}

// Create backup_schedule_config table
$sql2 = "CREATE TABLE IF NOT EXISTS backup_schedule_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_enabled TINYINT(1) DEFAULT 1,
    interval_hours INT DEFAULT 6 COMMENT 'Backup every X hours',
    next_backup_time DATETIME NULL,
    last_run_time DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($db->query($sql2)) {
    echo "✓ Table 'backup_schedule_config' created successfully\n";
} else {
    echo "✗ Error: " . $db->error . "\n";
}

// Insert default schedule config
$sql3 = "INSERT INTO backup_schedule_config (schedule_enabled, interval_hours, next_backup_time)
SELECT 1, 6, DATE_ADD(NOW(), INTERVAL 6 HOUR)
WHERE NOT EXISTS (SELECT 1 FROM backup_schedule_config LIMIT 1)";

if ($db->query($sql3)) {
    echo "✓ Default schedule configuration initialized\n";
} else {
    echo "✗ Error: " . $db->error . "\n";
}

echo "\nSetup complete!\n";
?>
