<?php
/**
 * Add missing backup_date column to backup_history table
 */

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_USER = $_ENV['DB_USER'] ?? 'root';
$DB_PASSWORD = $_ENV['DB_PASSWORD'] ?? '';
$DB_NAME = $_ENV['DB_NAME'] ?? 'dicom_viewer_v2_production';

echo "==========================================\n";
echo "Adding backup_date column to backup_history\n";
echo "==========================================\n\n";

try {
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    $db->set_charset("utf8mb4");
    echo "✓ Connected to database: $DB_NAME\n\n";
    
    // Check if backup_date column exists
    $result = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_date'");
    
    if ($result->num_rows > 0) {
        echo "✓ Column 'backup_date' already exists - no migration needed!\n";
    } else {
        echo "Adding 'backup_date' column...\n";
        
        // Add backup_date column after backup_size_bytes
        $sql = "ALTER TABLE backup_history 
                ADD COLUMN backup_date DATETIME AFTER backup_size_bytes";
        
        if ($db->query($sql)) {
            echo "✓ Successfully added 'backup_date' column\n";
            
            // Verify
            $verify = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_date'");
            if ($verify->num_rows > 0) {
                echo "✓ Verification passed\n";
            }
        } else {
            throw new Exception("Failed to add column: " . $db->error);
        }
    }
    
    echo "\n==========================================\n";
    echo "✓ Migration Complete!\n";
    echo "==========================================\n\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "\n❌ Migration Failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
