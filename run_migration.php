<?php
require_once __DIR__ . '/includes/config.php';

echo "Running AI tables migration...\n";

$mysqli = getDbConnection();
$sqlFile = __DIR__ . '/database/migrations/001_create_ai_tables.sql';

if (!file_exists($sqlFile)) {
    die("Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

// Execute multi-query
if ($mysqli->multi_query($sql)) {
    do {
        /* store first result set */
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->next_result());
    
    if ($mysqli->errno) {
        echo "Error executing migration: " . $mysqli->error . "\n";
    } else {
        echo "Migration executed successfully.\n";
    }
} else {
    echo "Error executing migration: " . $mysqli->error . "\n";
}

echo "Done.\n";
?>
