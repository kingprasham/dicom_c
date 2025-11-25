<?php
// Simple direct fix for backup_history table
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
    die("Connection failed");
}

// Check if backup_date exists
$check = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_date'");

if ($check->num_rows == 0) {
    // Add backup_date column
    $sql = "ALTER TABLE backup_history ADD COLUMN backup_date DATETIME AFTER backup_size_bytes";
    if ($db->query($sql)) {
        echo "SUCCESS: Added backup_date column\n";
    } else {
        echo "ERROR: " . $db->error . "\n";
    }
} else {
    echo "OK: backup_date column already exists\n";
}

// Show final columns
echo "\nFinal columns:\n";
$result = $db->query("SHOW COLUMNS FROM backup_history");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

$db->close();
?>
