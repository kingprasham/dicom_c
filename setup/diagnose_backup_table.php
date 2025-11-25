<?php
// Diagnostic script - show exact table structure
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

echo "BACKUP_HISTORY TABLE STRUCTURE\n";
echo "================================\n";

$result = $db->query("DESCRIBE backup_history");

if (!$result) {
    die("Query failed: " . $db->error . "\n");
}

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    printf("%-20s %-30s %-10s %-10s %-20s\n", 
        $row['Field'], 
        $row['Type'], 
        $row['Null'], 
        $row['Key'],
        $row['Default'] ?? 'NULL'
    );
}

echo "\n";
echo "REQUIRED BY CODE:\n";
echo "=================\n";
$required = ['backup_filename', 'gdrive_file_id', 'backup_size_bytes', 'backup_date', 'backup_type', 'status', 'error_message'];

foreach ($required as $col) {
    $status = in_array($col, $columns) ? '✓ EXISTS' : '✗ MISSING';
    echo "$col: $status\n";
}

$db->close();
?>
