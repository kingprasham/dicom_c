<?php
// Verify backup_history table structure
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
    die("Connection failed: " . $db->connect_error);
}

echo "backup_history table columns:\n";
echo "==============================\n";

$result = $db->query('SHOW COLUMNS FROM backup_history');
while($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$db->close();
?>
