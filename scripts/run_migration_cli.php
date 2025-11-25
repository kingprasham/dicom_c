<?php
/**
 * CLI Migration Runner
 */
define('DICOM_VIEWER', true);
require_once __DIR__ . '/../includes/config.php';

// Mock session for DB connection if needed (though config usually doesn't need it)
// But we need to make sure we don't hit session_start issues in CLI
// config.php might start session? No, usually session.php does.

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$sql = file_get_contents(__DIR__ . '/../migration_nodes_printers.txt');
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "Running migration...\n";
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    echo "Executing: " . substr($stmt, 0, 50) . "...\n";
    try {
        if ($db->query($stmt)) {
            echo "SUCCESS\n";
        } else {
            echo "ERROR: " . $db->error . "\n";
        }
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
