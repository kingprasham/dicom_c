<?php
/**
 * Comprehensive fix for backup_history table schema
 * Aligns table structure with BackupManager.php expectations
 */

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_USER = $_ENV['DB_USER'] ?? 'root';
$DB_PASSWORD = $_ENV['DB_PASSWORD'] ?? '';
$DB_NAME = $_ENV['DB_NAME'] ?? 'dicom_viewer_v2_production';

echo "============================================\n";
echo "Comprehensive backup_history Schema Fix\n";
echo "============================================\n\n";

try {
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    $db->set_charset("utf8mb4");
    
    // Get current columns
    $result = $db->query("SHOW COLUMNS FROM backup_history");
    $existingColumns = [];
    
    echo "Current columns:\n";
    while ($row = $result->fetch_assoc()) {
        $existingColumns[] = $row['Field'];
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";
    
    // Expected columns based on BackupManager.php and setup_backup_table.php
    $expectedColumns = [
        'id' => 'exists',
        'backup_filename' => 'exists',
        'gdrive_file_id' => 'exists', 
        'backup_size_bytes' => 'exists',
        'backup_date' => 'DATETIME',
        'backup_type' => 'exists',
        'status' => 'exists',
        'error_message' => 'exists',
        'created_at' => 'exists'
    ];
    
    $needsFix = false;
    $missingColumns = [];
    
    echo "Checking required columns:\n";
    foreach ($expectedColumns as $col => $type) {
        if (!in_array($col, $existingColumns)) {
            echo "  ✗ Missing: $col\n";
            $missingColumns[$col] = $type;
            $needsFix = true;
        } else {
            echo "  ✓ Found: $col\n";
        }
    }
    echo "\n";
    
    if (!$needsFix) {
        echo "✓ All required columns exist - no fix needed!\n";
        $db->close();
        exit(0);
    }
    
    echo "Adding missing columns...\n\n";
    
    // Add backup_date if missing
    if (isset($missingColumns['backup_date'])) {
        echo "Adding 'backup_date' column...\n";
        $sql = "ALTER TABLE backup_history ADD COLUMN backup_date DATETIME AFTER backup_size_bytes";
        if ($db->query($sql)) {
            echo "  ✓ Added backup_date\n";
        } else {
            throw new Exception("Failed to add backup_date: " . $db->error);
        }
    }
    
    echo "\n============================================\n";
    echo "✓ Schema Fix Complete!\n";
    echo "============================================\n\n";
    
    // Show final structure
    echo "Final table structure:\n";
    $result = $db->query("SHOW COLUMNS FROM backup_history");
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "\n❌ Fix Failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
