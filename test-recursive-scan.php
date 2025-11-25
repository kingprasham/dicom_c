<?php
/**
 * Test script to verify recursive DICOM scanning
 */

// Test directory path
$testDirectory = 'C:\\Users\\prash\\Downloads\\dicom\\';

echo "<h2>Testing Recursive DICOM File Scanner</h2>";
echo "<p><strong>Directory:</strong> $testDirectory</p>";

if (!is_dir($testDirectory)) {
    die("<p style='color:red;'>Error: Directory does not exist!</p>");
}

echo "<h3>Scanning for DICOM files (recursive)...</h3>";

$files = [];
$startTime = microtime(true);

scanDicomFiles($testDirectory, $files);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "<p><strong>Found:</strong> " . count($files) . " DICOM files</p>";
echo "<p><strong>Scan time:</strong> {$duration} seconds</p>";

if (count($files) > 0) {
    echo "<h3>Files by Directory:</h3>";

    // Group files by directory
    $byDir = [];
    foreach ($files as $file) {
        $dir = dirname($file['path']);
        if (!isset($byDir[$dir])) {
            $byDir[$dir] = [];
        }
        $byDir[$dir][] = $file;
    }

    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<thead><tr><th>Directory</th><th>File Count</th><th>Total Size</th></tr></thead>";
    echo "<tbody>";

    foreach ($byDir as $dir => $dirFiles) {
        $totalSize = array_sum(array_column($dirFiles, 'size'));
        $sizeFormatted = formatBytes($totalSize);
        $count = count($dirFiles);

        echo "<tr>";
        echo "<td><code>$dir</code></td>";
        echo "<td style='text-align:center;'>$count files</td>";
        echo "<td style='text-align:right;'>$sizeFormatted</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";

    echo "<h3>Sample Files (first 10):</h3>";
    echo "<ul>";
    foreach (array_slice($files, 0, 10) as $file) {
        $size = formatBytes($file['size']);
        echo "<li><code>{$file['path']}</code> <small>({$size})</small></li>";
    }
    if (count($files) > 10) {
        echo "<li><em>... and " . (count($files) - 10) . " more files</em></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:orange;'>No DICOM files found. Make sure:</p>";
    echo "<ul>";
    echo "<li>The directory contains .dcm files</li>";
    echo "<li>The files have the DICM marker at byte 128</li>";
    echo "<li>PHP has read permissions on the directory</li>";
    echo "</ul>";
}

function scanDicomFiles($dir, &$files) {
    $items = @scandir($dir);
    if ($items === false) {
        echo "<p style='color:red;'>Warning: Cannot read directory: $dir</p>";
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            // Recursive scan
            scanDicomFiles($path, $files);
        } elseif (is_file($path)) {
            // Check if it's a DICOM file
            $handle = @fopen($path, 'rb');
            if ($handle) {
                fseek($handle, 128);
                $marker = fread($handle, 4);
                fclose($handle);

                // Check for DICM marker or .dcm extension
                if ($marker === 'DICM' || strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'dcm') {
                    $files[] = [
                        'path' => $path,
                        'name' => basename($path),
                        'size' => filesize($path),
                        'directory' => dirname($path)
                    ];
                }
            }
        }
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
