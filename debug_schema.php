<?php
require_once __DIR__ . '/includes/config.php';

$db = getDbConnection();
$result = $db->query("DESCRIBE backup_history");

if ($result) {
    echo "Columns in backup_history:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error describing table: " . $db->error;
}
?>
