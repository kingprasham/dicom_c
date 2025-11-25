<?php
/**
 * Database Migration: Fix backup_history Column Name
 * 
 * Purpose: Rename 'backup_name' to 'backup_filename' in backup_history table
 * to match the expectations of BackupManager.php
 * 
 * Issue: Unknown column 'backup_filename' in 'field list'
 * Cause: Schema uses 'backup_name' but code expects 'backup_filename'
 */

// Load database configuration without triggering shutdown function
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_USER = $_ENV['DB_USER'] ?? 'root';
$DB_PASSWORD = $_ENV['DB_PASSWORD'] ?? '';
$DB_NAME = $_ENV['DB_NAME'] ?? 'dicom_viewer_v2_production';

echo "==============================================\n";
echo "Database Migration: backup_history Column Fix\n";
echo "==============================================\n\n";

try {
    // Create direct database connection
    $db = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    $db->set_charset("utf8mb4");
    echo "✓ Connected to database: $DB_NAME\n\n";
    
    // Check if backup_history table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'backup_history'");
    if ($tableCheck->num_rows == 0) {
        echo "⚠️  Warning: backup_history table does not exist.\n";
        echo "Creating table with correct schema...\n\n";
        
        $createTableSQL = "CREATE TABLE IF NOT EXISTS backup_history (
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
        
        if ($db->query($createTableSQL)) {
            echo "✓ Table 'backup_history' created successfully with 'backup_filename' column\n";
        } else {
            throw new Exception("Failed to create table: " . $db->error);
        }
    } else {
        // Check current column structure
        $columnsResult = $db->query("SHOW COLUMNS FROM backup_history");
        $columns = [];
        while ($row = $columnsResult->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        echo "Current columns in backup_history table:\n";
        foreach ($columns as $col) {
            echo "  - $col\n";
        }
        echo "\n";
        
        $hasBackupName = in_array('backup_name', $columns);
        $hasBackupFilename = in_array('backup_filename', $columns);
        
        if ($hasBackupFilename) {
            echo "✓ Column 'backup_filename' already exists.\n";
            echo "✓ No migration needed - database is already correct!\n";
        } elseif ($hasBackupName) {
            echo "Found 'backup_name' column. Renaming to 'backup_filename'...\n";
            
            // Rename the column
            $renameSQL = "ALTER TABLE backup_history 
                          CHANGE COLUMN backup_name backup_filename VARCHAR(255)";
            
            if ($db->query($renameSQL)) {
                echo "✓ Successfully renamed column 'backup_name' to 'backup_filename'\n";
                
                // Verify the change
                $verifyResult = $db->query("SHOW COLUMNS FROM backup_history LIKE 'backup_filename'");
                if ($verifyResult->num_rows > 0) {
                    echo "✓ Verification passed: 'backup_filename' column confirmed\n";
                } else {
                    echo "⚠️  Warning: Could not verify column rename\n";
                }
            } else {
                throw new Exception("Failed to rename column: " . $db->error);
            }
        } else {
            echo "⚠️  Warning: Neither 'backup_name' nor 'backup_filename' found.\n";
            echo "Adding 'backup_filename' column...\n";
            
            $addColumnSQL = "ALTER TABLE backup_history 
                             ADD COLUMN backup_filename VARCHAR(255) AFTER id";
            
            if ($db->query($addColumnSQL)) {
                echo "✓ Successfully added 'backup_filename' column\n";
            } else {
                throw new Exception("Failed to add column: " . $db->error);
            }
        }
    }
    
    echo "\n==============================================\n";
    echo "✓ Migration Completed Successfully!\n";
    echo "==============================================\n";
    echo "\nYou can now test the backup functionality:\n";
    echo "1. Login to the admin panel\n";
    echo "2. Go to Google Drive Backup Configuration\n";
    echo "3. Click 'Backup to All Accounts Now'\n";
    echo "4. The backup should complete without errors\n\n";
    
    $db->close();
    
} catch (Exception $e) {
    echo "\n❌ Migration Failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>

