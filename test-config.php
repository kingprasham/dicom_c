<?php
/**
 * Quick diagnostic test for config loading
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Configuration Diagnostic Test</h2>";

// Step 1: Check .env file
echo "<h3>Step 1: Check .env file</h3>";
$envFile = __DIR__ . '/config/.env';
if (file_exists($envFile)) {
    echo "<p style='color: green;'>✓ .env file exists at: $envFile</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($envFile)) . "</pre>";
} else {
    echo "<p style='color: red;'>✗ .env file NOT FOUND at: $envFile</p>";
    exit;
}

// Step 2: Check vendor/autoload.php
echo "<h3>Step 2: Check Composer Autoloader</h3>";
$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "<p style='color: green;'>✓ Autoload file exists at: $autoloadFile</p>";
} else {
    echo "<p style='color: red;'>✗ Autoload file NOT FOUND at: $autoloadFile</p>";
    exit;
}

// Step 3: Try loading autoloader
echo "<h3>Step 3: Load Composer Autoloader</h3>";
try {
    require_once $autoloadFile;
    echo "<p style='color: green;'>✓ Autoloader loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Autoloader error: " . $e->getMessage() . "</p>";
    exit;
}

// Step 4: Check if Dotenv class exists
echo "<h3>Step 4: Check Dotenv Class</h3>";
if (class_exists('Dotenv\Dotenv')) {
    echo "<p style='color: green;'>✓ Dotenv class exists</p>";
} else {
    echo "<p style='color: red;'>✗ Dotenv class NOT FOUND</p>";
    exit;
}

// Step 5: Try loading .env
echo "<h3>Step 5: Load .env file</h3>";
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/config');
    $dotenv->load();
    echo "<p style='color: green;'>✓ .env file loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ .env loading error: " . $e->getMessage() . "</p>";
    echo "<p>Full error:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

// Step 6: Check loaded variables
echo "<h3>Step 6: Check Loaded Variables</h3>";
$testVars = ['DB_HOST', 'DB_NAME', 'ORTHANC_URL', 'GEMINI_API_KEY'];
foreach ($testVars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    echo "<p>$var = " . htmlspecialchars($value) . "</p>";
}

// Step 7: Test database connection
echo "<h3>Step 7: Test Database Connection</h3>";
try {
    $db = new mysqli(
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        $_ENV['DB_NAME'] ?? 'dicom_viewer_v2_production'
    );
    
    if ($db->connect_error) {
        echo "<p style='color: red;'>✗ Database connection error: " . $db->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Database connected successfully</p>";
        $db->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<h3>All Tests Complete</h3>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
