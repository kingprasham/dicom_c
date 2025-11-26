<?php
/**
 * Run Hospital Settings Migration
 * Execute this script once to add hospital settings to the database
 */

define('DICOM_VIEWER', true);
require_once __DIR__ . '/auth/session.php';

$db = getDbConnection();

echo "<h2>Hospital Settings Migration</h2>\n";

try {
    // Check if system_settings table exists
    $result = $db->query("SHOW TABLES LIKE 'system_settings'");
    if ($result->num_rows === 0) {
        echo "<p>Error: system_settings table does not exist. Please run the main database setup first.</p>";
        exit;
    }
    
    // Read the migration SQL
    $sqlFile = __DIR__ . '/database/migrations/002_hospital_settings.sql';
    if (!file_exists($sqlFile)) {
        echo "<p>Error: Migration file not found at $sqlFile</p>";
        exit;
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $stmt) {
        if (empty($stmt) || strpos($stmt, '--') === 0) continue;
        
        try {
            if ($db->query($stmt)) {
                $successCount++;
                echo "<p style='color: green;'>✓ Statement executed successfully</p>\n";
            }
        } catch (Exception $e) {
            // Ignore duplicate key errors (settings already exist)
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "<p style='color: orange;'>⚠ Setting already exists, skipping</p>\n";
            } else {
                $errorCount++;
                echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
    }
    
    echo "<hr>";
    echo "<p><strong>Migration Complete</strong></p>";
    echo "<p>Successful: $successCount, Errors: $errorCount</p>";
    
    // Verify settings were added
    $checkResult = $db->query("SELECT * FROM system_settings WHERE category = 'hospital' ORDER BY setting_key");
    echo "<h3>Current Hospital Settings:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Key</th><th>Value</th><th>Description</th></tr>";
    
    while ($row = $checkResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['setting_key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['setting_value'] ?: '(empty)') . "</td>";
        echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><br><a href='pages/settings.php'>Go to Settings to configure hospital information</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
