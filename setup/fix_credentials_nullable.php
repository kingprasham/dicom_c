<?php
/**
 * Fix credentials_json column to allow NULL for Dropbox accounts
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

echo "Updating backup_accounts table schema...\n";
echo "=========================================\n\n";

// Make credentials_json nullable (for Dropbox accounts)
echo "Making credentials_json column nullable...\n";
$sql = "ALTER TABLE backup_accounts 
        MODIFY COLUMN credentials_json TEXT NULL";

if ($db->query($sql)) {
    echo "✓ Column updated successfully\n";
} else {
    echo "✗ Error: " . $db->error . "\n";
}

echo "\n=========================================\n";
echo "✓ Schema update complete!\n";
echo "=========================================\n";

$db->close();
?>
