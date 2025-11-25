<?php
/**
 * Quick database connection test
 */

echo "Testing Database Connection...\n\n";

// Load configuration
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDbConnection();

    if ($db && $db->ping()) {
        echo "✓ Database connection: SUCCESS\n";
        echo "  Host: " . DB_HOST . "\n";
        echo "  Database: " . DB_NAME . "\n";

        // Check if tables exist
        $result = $db->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "  Tables found: " . $row['table_count'] . "\n";

            if ($row['table_count'] == 18) {
                echo "✓ All 18 tables exist\n";
            } else if ($row['table_count'] == 0) {
                echo "✗ No tables found - Please import setup/schema_v2_production.sql\n";
            } else {
                echo "⚠ Found " . $row['table_count'] . " tables (expected 18)\n";
            }
        }

        // Check users table
        $userCheck = $db->query("SELECT COUNT(*) as user_count FROM users");
        if ($userCheck) {
            $userRow = $userCheck->fetch_assoc();
            echo "  Users in database: " . $userRow['user_count'] . "\n";

            if ($userRow['user_count'] > 0) {
                echo "✓ Default users created\n";
            } else {
                echo "⚠ No users found - Database may need re-import\n";
            }
        }

    } else {
        echo "✗ Database connection: FAILED (no ping response)\n";
    }

} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n\n";
    echo "Common fixes:\n";
    echo "1. Check MySQL is running in XAMPP Control Panel\n";
    echo "2. Update config/.env with correct database credentials\n";
    echo "3. Create database: dicom_viewer_v2_production\n";
    echo "4. Import: setup/schema_v2_production.sql\n";
}

echo "\n";
