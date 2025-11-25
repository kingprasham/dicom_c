<?php
/**
 * Login Debugging Script
 */

define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';

echo "=== LOGIN DEBUG SCRIPT ===\n\n";

// Get database connection
$db = getDbConnection();

// Get admin user
$result = $db->query("SELECT id, username, email, password_hash, is_active FROM users WHERE username = 'admin'");
$user = $result->fetch_assoc();

echo "User Info:\n";
echo "  ID: " . $user['id'] . "\n";
echo "  Username: " . $user['username'] . "\n";
echo "  Email: " . $user['email'] . "\n";
echo "  Is Active: " . $user['is_active'] . "\n";
echo "  Password Hash Length: " . strlen($user['password_hash']) . " chars\n";
echo "  Password Hash: " . $user['password_hash'] . "\n\n";

// Test password
$testPassword = 'Admin@123';
echo "Testing Password: '$testPassword'\n";
echo "Result: " . (password_verify($testPassword, $user['password_hash']) ? '✓ MATCH' : '✗ NO MATCH') . "\n\n";

// Generate fresh hash
$newHash = password_hash($testPassword, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Fresh Hash Generated: $newHash\n";
echo "Test Fresh Hash: " . (password_verify($testPassword, $newHash) ? '✓ MATCH' : '✗ NO MATCH') . "\n\n";

// Try different passwords
$passwords = ['Admin@123', 'admin@123', 'Admin123', 'admin'];
echo "Testing various passwords:\n";
foreach ($passwords as $pass) {
    $match = password_verify($pass, $user['password_hash']) ? '✓ MATCH' : '✗ NO MATCH';
    echo "  '$pass': $match\n";
}

echo "\n=== END DEBUG ===\n";
