<?php
/**
 * Fix User Passwords
 */

define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';

echo "=== FIXING USER PASSWORDS ===\n\n";

// Generate correct password hashes
$passwords = [
    'admin' => 'Admin@123',
    'radiologist' => 'Radio@123',
    'technician' => 'Tech@123'
];

$db = getDbConnection();

foreach ($passwords as $username => $password) {
    echo "Processing $username...\n";

    // Generate hash
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    echo "  Generated hash: $hash\n";
    echo "  Hash length: " . strlen($hash) . " chars\n";

    // Verify hash works
    if (password_verify($password, $hash)) {
        echo "  ✓ Hash verification: PASS\n";

        // Update database using prepared statement (safer)
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $stmt->bind_param("ss", $hash, $username);

        if ($stmt->execute()) {
            echo "  ✓ Database updated successfully\n";

            // Verify in database
            $checkStmt = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $row = $result->fetch_assoc();

            echo "  Stored hash length: " . strlen($row['password_hash']) . " chars\n";

            if (password_verify($password, $row['password_hash'])) {
                echo "  ✓ Final verification: PASS\n";
            } else {
                echo "  ✗ Final verification: FAILED\n";
            }

            $checkStmt->close();
        } else {
            echo "  ✗ Database update failed\n";
        }

        $stmt->close();
    } else {
        echo "  ✗ Hash verification: FAILED\n";
    }

    echo "\n";
}

echo "=== DONE ===\n";
