<?php
/**
 * Final fix for backup_history table schema
 * Based on diagnostic results
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

echo "FIXING BACKUP_HISTORY TABLE\n";
echo "============================\n\n";

$success = true;

// Fix 1: Rename file_size_bytes to backup_size_bytes
echo "1. Checking backup_size_bytes column...\n";
$check = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_size_bytes'");
if ($check->num_rows == 0) {
    // Check if file_size_bytes exists
    $check2 = $db->query("SHOW COLUMNS FROM backup_history LIKE 'file_size_bytes'");
    if ($check2->num_rows > 0) {
        echo "   Renaming file_size_bytes to backup_size_bytes...\n";
        $sql = "ALTER TABLE backup_history CHANGE COLUMN file_size_bytes backup_size_bytes BIGINT";
        if ($db->query($sql)) {
            echo "   ✓ Renamed successfully\n";
        } else {
            echo "   ✗ Error: " . $db->error . "\n";
            $success = false;
        }
    } else {
        echo "   Adding backup_size_bytes column...\n";
        $sql = "ALTER TABLE backup_history ADD COLUMN backup_size_bytes BIGINT AFTER gdrive_file_id";
        if ($db->query($sql)) {
            echo "   ✓ Added successfully\n";
        } else {
            echo "   ✗ Error: " . $db->error . "\n";
            $success = false;
        }
    }
} else {
    echo "   ✓ backup_size_bytes already exists\n";
}

// Fix 2: Add backup_date column
echo "\n2. Checking backup_date column...\n";
$check = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_date'");
if ($check->num_rows == 0) {
    echo "   Adding backup_date column...\n";
    $sql = "ALTER TABLE backup_history ADD COLUMN backup_date DATETIME AFTER backup_size_bytes";
    if ($db->query($sql)) {
        echo "   ✓ Added successfully\n";
    } else {
        echo "   ✗ Error: " . $db->error . "\n";
        $success = false;
    }
} else {
    echo "   ✓ backup_date already exists\n";
}

echo "\n============================\n";
if ($success) {
    echo "✓ ALL FIXES COMPLETE!\n";
} else {
    echo "✗ SOME FIXES FAILED\n";
}
echo "============================\n\n";

// Show final structure
echo "Final table structure:\n";
$result = $db->query("DESCRIBE backup_history");
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . "\n";
}

$db->close();
?>
