# Production Fix - Image Loading Error

## Problem on Production
URL: `https://e-connect.in/dicom/index.php?studyUID=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27&orthancId=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27`

Error: "image load error undefined"

## Root Cause
JavaScript variable `image` was declared with `const` inside try block, making it inaccessible outside the block where it's needed.

---

## SOLUTION: Upload These 3 Files to Production

### File 1: api/get_dicom_from_orthanc.php (NEW FILE)
**Purpose**: PHP proxy to fetch DICOM files from Orthanc server
**Location**: Upload to `/public_html/dicom/api/`

```php
<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/dicom');
header('Access-Control-Allow-Origin: *');

$instanceId = $_GET['instanceId'] ?? '';

if (empty($instanceId)) {
    http_response_code(400);
    die('Instance ID required');
}

// Fetch DICOM file from Orthanc
$orthancUrl = ORTHANC_URL . "/instances/{$instanceId}/file";

$ch = curl_init($orthancUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, ORTHANC_USER . ':' . ORTHANC_PASS);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$dicomData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$dicomData) {
    http_response_code($httpCode);
    die('Failed to fetch DICOM file from Orthanc');
}

header('Content-Length: ' . strlen($dicomData));
echo $dicomData;
?>
```

---

### File 2: api/load_study_fast.php (MODIFY EXISTING)
**Purpose**: Add required fields for Orthanc image identification
**Location**: `/public_html/dicom/api/load_study_fast.php`

**Find this section** (around line 188-199):
```php
if ($instanceData) {
    $images[] = [
        'instanceId' => $instanceId,
        'seriesInstanceUID' => $seriesUID,
        'sopInstanceUID' => $instanceData['MainDicomTags']['SOPInstanceUID'] ?? $instanceId,
        'instanceNumber' => intval($instanceData['MainDicomTags']['InstanceNumber'] ?? 0),
        'seriesDescription' => $seriesDesc,
        'seriesNumber' => intval($seriesNumber),
        'patientName' => $studyInfo['patient_name'],
        'useApiGateway' => false
    ];
}
```

**Replace with** (add 2 lines):
```php
if ($instanceData) {
    $images[] = [
        'instanceId' => $instanceId,
        'orthancInstanceId' => $instanceId,  // ← ADD THIS LINE
        'seriesInstanceUID' => $seriesUID,
        'sopInstanceUID' => $instanceData['MainDicomTags']['SOPInstanceUID'] ?? $instanceId,
        'instanceNumber' => intval($instanceData['MainDicomTags']['InstanceNumber'] ?? 0),
        'seriesDescription' => $seriesDesc,
        'seriesNumber' => intval($seriesNumber),
        'patientName' => $studyInfo['patient_name'],
        'useApiGateway' => false,
        'isOrthancImage' => true  // ← ADD THIS LINE
    ];
}
```

---

### File 3: js/main.js (MODIFY EXISTING - CRITICAL FIX)
**Purpose**: Fix JavaScript variable scope issue
**Location**: `/public_html/dicom/js/main.js`

**Find this section** (around line 960-1000):
```javascript
// Create image ID for Orthanc instance using absolute URL
const baseUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1)}`;
const imageId = `wadouri:${baseUrl}api/get_dicom_from_orthanc.php?instanceId=${currentImage.orthancInstanceId}`;

console.log('Base URL:', baseUrl);
console.log('Loading image with ID:', imageId);
console.log('Instance ID:', currentImage.orthancInstanceId);

try {  // ← LOOK FOR THIS LINE
    const image = await cornerstone.loadImage(imageId);  // ← WRONG - const inside try
    console.log('Image loaded successfully:', image.width, 'x', image.height);
    cornerstone.displayImage(targetViewport, image);
} catch (error) {
    console.error('ERROR loading Orthanc image:', error);
    throw new Error(`Failed to load DICOM image: ${error.message}`);
}

// Update UI only during non-cine operations
if (!state.isPlaying) {
    window.DICOM_VIEWER.updateViewportInfo();
    const orthancPatientInfo = {
        patient_name: currentImage.patient_name || 'Unknown',
        study_description: currentImage.study_description || 'PACS Study',
        series_description: currentImage.series_description || 'PACS Series',
        modality: 'CT',
        columns: image.columns,  // ← ERROR HERE - image is undefined!
        rows: image.rows  // ← ERROR HERE - image is undefined!
    };
    window.DICOM_VIEWER.updatePatientInfo(orthancPatientInfo);
}
```

**Replace with** (move `let image;` before try):
```javascript
// Create image ID for Orthanc instance using absolute URL
const baseUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1)}`;
const imageId = `wadouri:${baseUrl}api/get_dicom_from_orthanc.php?instanceId=${currentImage.orthancInstanceId}`;

console.log('Base URL:', baseUrl);
console.log('Loading image with ID:', imageId);
console.log('Instance ID:', currentImage.orthancInstanceId);

let image;  // ← ADD THIS LINE - declare before try
try {
    image = await cornerstone.loadImage(imageId);  // ← REMOVE 'const' - just assign
    console.log('Image loaded successfully:', image.width, 'x', image.height);
    cornerstone.displayImage(targetViewport, image);
} catch (error) {
    console.error('ERROR loading Orthanc image:', error);
    console.error('Image ID was:', imageId);
    console.error('Instance ID was:', currentImage.orthancInstanceId);
    throw new Error(`Failed to load DICOM image: ${error.message}`);
}

// Update UI only during non-cine operations
if (!state.isPlaying) {
    window.DICOM_VIEWER.updateViewportInfo();
    const orthancPatientInfo = {
        patient_name: currentImage.patient_name || 'Unknown',
        study_description: currentImage.study_description || 'PACS Study',
        series_description: currentImage.series_description || 'PACS Series',
        modality: 'CT',
        columns: image.columns,  // ← NOW WORKS - image is accessible
        rows: image.rows  // ← NOW WORKS - image is accessible
    };
    window.DICOM_VIEWER.updatePatientInfo(orthancPatientInfo);
}
```

---

## Upload Instructions

### Using cPanel File Manager:

1. **Login to cPanel** at https://e-connect.in:2083/

2. **File Manager** → Navigate to `/public_html/dicom/`

3. **Upload File 1** (New):
   - Go to `/api/` folder
   - Click "Upload"
   - Upload `get_dicom_from_orthanc.php`

4. **Edit File 2**:
   - Go to `/api/` folder
   - Right-click `load_study_fast.php` → "Edit"
   - Find line ~188-199
   - Add the 2 lines as shown above
   - Save

5. **Edit File 3** (CRITICAL):
   - Go to `/js/` folder
   - Right-click `main.js` → "Edit"
   - Find line ~960-1000 (search for "orthancInstanceId")
   - Make the scope fix: add `let image;` before try, remove `const` from assignment
   - Save

### Verify Production Config:

Make sure `/public_html/dicom/config.php` has:
```php
define('ORTHANC_URL', 'http://your-orthanc-server:8042');
define('ORTHANC_USER', 'your-username');
define('ORTHANC_PASS', 'your-password');
```

---

## Testing

1. Open: `https://e-connect.in/dicom/index.php?studyUID=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27&orthancId=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27`

2. Press F12 (Developer Console)

3. You should see:
   ```
   ✓ Image loaded successfully: 1280 x 1024
   ✓ Orthanc image loaded and displayed successfully
   ```

4. Image should display without errors

---

## What Changed From Original?

**Only 3 small changes needed:**

1. **New PHP proxy file** to fetch DICOM from Orthanc
2. **Two new fields** in load_study_fast.php response
3. **One line moved** in main.js (`let image;` before try block)

All other files (index.php, reporting-system.js, print-manager.js) are OPTIONAL and not needed for basic image loading to work.
