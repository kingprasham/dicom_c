<?php
/**
 * Add backup_provider column to backup_accounts table
 */

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

$db = new mysqli(
    $_ENV['DB_HOST'] ?? 'localhost',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    $_ENV['DB_NAME'] ?? 'dicom_viewer_v2_production'
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "Adding backup provider support to backup_accounts table...\n";
echo "=========================================================\n\n";

// Check if columns already exist
$check = $db->query("SHOW COLUMNS FROM backup_accounts LIKE 'backup_provider'");

if ($check->num_rows == 0) {
    echo "1. Adding backup_provider column...\n";
    $sql = "ALTER TABLE backup_accounts 
            ADD COLUMN backup_provider ENUM('google_drive', 'dropbox') DEFAULT 'google_drive' AFTER folder_name";
    
    if ($db->query($sql)) {
        echo "   ✓ Column added\n";
    } else {
        echo "   ✗ Error: " . $db->error . "\n";
    }
} else {
    echo "1. backup_provider column already exists ✓\n";
}

// Check for dropbox_access_token
$check2 = $db->query("SHOW COLUMNS FROM backup_accounts LIKE 'dropbox_access_token'");

if ($check2->num_rows == 0) {
    echo "2. Adding dropbox_access_token column...\n";
    $sql = "ALTER TABLE backup_accounts 
            ADD COLUMN dropbox_access_token TEXT AFTER backup_provider";
    
    if ($db->query($sql)) {
        echo "   ✓ Column added\n";
    } else {
        echo "   ✗ Error: " . $db->error . "\n";
    }
} else {
    echo "2. dropbox_access_token column already exists ✓\n";
}

echo "\n=========================================================\n";
echo "✓ Migration complete!\n";
echo "=========================================================\n";

$db->close();
?>
