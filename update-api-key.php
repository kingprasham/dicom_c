<?php
/**
 * Update Gemini API Key
 * Use this to safely update your API key after the old one was leaked/disabled
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$envPath = __DIR__ . '/config/.env';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_api_key'])) {
    $newKey = trim($_POST['new_api_key']);
    
    if (!empty($newKey) && preg_match('/^AIza[A-Za-z0-9_-]{35}$/', $newKey)) {
        // Read current .env
        $envContent = file_get_contents($envPath);
        
        // Replace the API key
        $envContent = preg_replace(
            '/GEMINI_API_KEY=.*/',
            'GEMINI_API_KEY=' . $newKey,
            $envContent
        );
        
        // Write back
        if (file_put_contents($envPath, $envContent)) {
            echo "<div style='padding: 20px; background: #d4edda; color: #155724; border-radius: 5px; margin: 20px;'>";
            echo "<h3>✓ API Key Updated Successfully!</h3>";
            echo "<p>The new API key has been saved. You can now <a href='dashboard.php'>return to the dashboard</a> and try AI Analysis again.</p>";
            echo "</div>";
        } else {
            echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>";
            echo "<h3>✗ Error Writing File</h3>";
            echo "<p>Could not write to .env file. Check file permissions.</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>";
        echo "<h3>✗ Invalid API Key Format</h3>";
        echo "<p>The API key should start with 'AIza' and be 39 characters long.</p>";
        echo "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Gemini API Key</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .error-box { background: #f8d7da; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .info-box { background: #d1ecf1; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .form-group { margin: 20px 0; }
        input[type="text"] { width: 100%; padding: 10px; font-size: 16px; font-family: monospace; }
        button { background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        ol { line-height: 2; }
    </style>
</head>
<body>
    <h1>🔑 Update Gemini API Key</h1>
    
    <div class="error-box">
        <h3>⚠️ Your API Key Has Been Disabled</h3>
        <p>Google detected that your API key was leaked (possibly committed to a public repository) and disabled it for security.</p>
        <p>Error message: <code>"Your API key was reported as leaked. Please use another API key."</code></p>
    </div>
    
    <div class="info-box">
        <h3>How to Get a New API Key:</h3>
        <ol>
            <li>Go to <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
            <li>Sign in with your Google account</li>
            <li>Click <strong>"Create API Key"</strong> or <strong>"Get API Key"</strong></li>
            <li>Copy the new API key</li>
            <li>Paste it below and click Update</li>
        </ol>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="new_api_key"><strong>New Gemini API Key:</strong></label><br><br>
            <input type="text" name="new_api_key" id="new_api_key" placeholder="AIzaSy..." required>
        </div>
        <button type="submit">Update API Key</button>
    </form>
    
    <hr style="margin-top: 40px;">
    
    <h3>⚠️ Preventing Future Leaks</h3>
    <ul>
        <li>Never commit API keys to public repositories</li>
        <li>Add <code>.env</code> to your <code>.gitignore</code> file</li>
        <li>Use environment variables in production</li>
        <li>Regularly rotate API keys</li>
    </ul>
    
    <p><a href="dashboard.php">← Back to Dashboard</a></p>
</body>
</html>
