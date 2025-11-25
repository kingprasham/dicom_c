<?php
define('DICOM_VIEWER', true);
require_once __DIR__ . '/includes/config.php';

$instanceId = 'a68a0e64-81314e1f-9635070f-73e9190d-eccb4271';

echo "Testing ZIP extraction...\n\n";

// Step 1: Download ZIP from Orthanc
$orthancUrl = ORTHANC_URL . "/tools/create-archive";
$ch = curl_init($orthancUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Resources' => [$instanceId]]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

$zipData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Step 1: Download ZIP from Orthanc\n";
echo "  HTTP Code: $httpCode\n";
echo "  ZIP Size: " . strlen($zipData) . " bytes\n\n";

if ($httpCode !== 200) {
    die("Failed to download ZIP\n");
}

// Step 2: Save to temp file
$tempZip = tempnam(sys_get_temp_dir(), 'dcm_');
file_put_contents($tempZip, $zipData);
echo "Step 2: Saved to temp file\n";
echo "  File: $tempZip\n";
echo "  File exists: " . (file_exists($tempZip) ? 'YES' : 'NO') . "\n\n";

// Step 3: Check ZipArchive
echo "Step 3: Check ZipArchive\n";
echo "  class_exists: " . (class_exists('ZipArchive') ? 'YES' : 'NO') . "\n";

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $openResult = $zip->open($tempZip);
    echo "  open() result: " . ($openResult === true ? 'TRUE' : $openResult) . "\n";

    if ($openResult === true) {
        echo "  numFiles: " . $zip->numFiles . "\n";
        echo "  Files in ZIP:\n";
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $filesize = $zip->statIndex($i)['size'];
            echo "    [$i] $filename ($filesize bytes)\n";

            if (stripos($filename, '.dcm') !== false) {
                $content = $zip->getFromIndex($i);
                echo "      -> Extracted: " . strlen($content) . " bytes\n";
                echo "      -> First 4 bytes: " . bin2hex(substr($content, 0, 4)) . "\n";

                // Check if it's a valid DICOM file (should start with DICM at byte 128)
                if (strlen($content) > 132) {
                    $dicmTag = substr($content, 128, 4);
                    echo "      -> DICOM tag at 128: $dicmTag\n";
                }
            }
        }
        $zip->close();
    }
}

unlink($tempZip);
echo "\nTest complete!\n";
