<?php
// Minimal diagnostic - no dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Minimal Diagnostic</h1>";

// Step 1: Basic PHP
echo "<h3>1. PHP Working</h3>";
echo "<p style='color:green'>✓ PHP is running - Version: " . phpversion() . "</p>";

// Step 2: Check .env file
echo "<h3>2. Check .env file</h3>";
$envPath = __DIR__ . '/config/.env';
if (file_exists($envPath)) {
    echo "<p style='color:green'>✓ .env exists</p>";
    $content = file_get_contents($envPath);
    echo "<pre style='background:#f0f0f0;padding:10px;max-height:200px;overflow:auto'>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<p style='color:red'>✗ .env NOT FOUND at: $envPath</p>";
}

// Step 3: Check vendor autoload
echo "<h3>3. Check Composer Autoload</h3>";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p style='color:green'>✓ vendor/autoload.php exists</p>";
    
    // Try to load it
    echo "<h3>4. Try Loading Autoloader</h3>";
    try {
        require_once $autoloadPath;
        echo "<p style='color:green'>✓ Autoloader loaded successfully</p>";
        
        // Step 5: Check Dotenv
        echo "<h3>5. Check Dotenv Class</h3>";
        if (class_exists('Dotenv\Dotenv')) {
            echo "<p style='color:green'>✓ Dotenv class exists</p>";
            
            // Step 6: Try loading .env
            echo "<h3>6. Try Loading .env with Dotenv</h3>";
            try {
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/config');
                $dotenv->load();
                echo "<p style='color:green'>✓ .env loaded successfully</p>";
                
                // Show some values
                echo "<p>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "</p>";
                echo "<p>GEMINI_API_KEY: " . (isset($_ENV['GEMINI_API_KEY']) ? substr($_ENV['GEMINI_API_KEY'], 0, 10) . '...' : 'NOT SET') . "</p>";
                
            } catch (Throwable $e) {
                echo "<p style='color:red'>✗ Dotenv loading error:</p>";
                echo "<pre style='color:red'>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
            }
        } else {
            echo "<p style='color:red'>✗ Dotenv class NOT found</p>";
        }
        
    } catch (Throwable $e) {
        echo "<p style='color:red'>✗ Autoloader error:</p>";
        echo "<pre style='color:red'>" . $e->getMessage() . "\n" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p style='color:red'>✗ vendor/autoload.php NOT FOUND</p>";
    echo "<p>Run: <code>composer install</code> in the claude directory</p>";
}

// Step 7: Check database
echo "<h3>7. Database Connection Test</h3>";
try {
    $db = new mysqli('localhost', 'root', '', 'dicom_viewer_v2_production');
    if ($db->connect_error) {
        echo "<p style='color:red'>✗ DB Error: " . $db->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>✓ Database connected</p>";
        $db->close();
    }
} catch (Throwable $e) {
    echo "<p style='color:red'>✗ DB Exception: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='dashboard.php'>Try Dashboard</a></p>";
