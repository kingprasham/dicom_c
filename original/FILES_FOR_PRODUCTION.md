# Complete Files for Production Upload

## Complete Working Files Created

I've created backup copies of your current LOCAL working files:

### ✅ Complete Working File:
**`ORIGINAL_WORKING_main.js`** (3092 lines)
- This is your current working `js/main.js` file
- Contains the CRITICAL FIX at line 969: `let image;` before try block
- This file is working correctly on your localhost

### 📁 File Locations:
```
c:\xampp\htdocs\papa\dicom_again\
├── ORIGINAL_WORKING_main.js          ← Complete working main.js (USE THIS)
├── PRODUCTION_FIX_FILES.md           ← Step-by-step fix instructions
├── REVERT_GUIDE.md                   ← What to revert guide
└── ORIGINAL_js_main.js.txt           ← Explanation of the fix
```

---

## How to Upload to Production (cPanel)

### Option 1: Upload Complete File (EASIEST)

1. **Download from Local**:
   - File: `c:\xampp\htdocs\papa\dicom_again\ORIGINAL_WORKING_main.js`
   - This is your complete working version

2. **Upload to cPanel**:
   - Login to cPanel: https://e-connect.in:2083/
   - File Manager → `/public_html/dicom/js/`
   - Rename current `main.js` to `main.js.backup` (for safety)
   - Upload `ORIGINAL_WORKING_main.js`
   - Rename uploaded file from `ORIGINAL_WORKING_main.js` to `main.js`

3. **Test**:
   - Open: https://e-connect.in/dicom/index.php?studyUID=...&orthancId=...
   - Images should load without errors

---

### Option 2: Edit Existing File (Manual Fix)

If you prefer to edit the existing production `main.js` instead of replacing it:

**Find line ~960-980** (search for "orthancInstanceId"):
```javascript
const imageId = `wadouri:${baseUrl}api/get_dicom_from_orthanc.php?instanceId=${currentImage.orthancInstanceId}`;

try {
    const image = await cornerstone.loadImage(imageId);  // ← WRONG
```

**Change to**:
```javascript
const imageId = `wadouri:${baseUrl}api/get_dicom_from_orthanc.php?instanceId=${currentImage.orthancInstanceId}`;

let image;  // ← ADD THIS LINE
try {
    image = await cornerstone.loadImage(imageId);  // ← REMOVE 'const'
```

---

## Required Files for Production (Checklist)

### ✅ Must Upload/Modify These 3 Files:

#### 1. **`api/get_dicom_from_orthanc.php`** (NEW FILE)
Location: `/public_html/dicom/api/`

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

#### 2. **`api/load_study_fast.php`** (MODIFY EXISTING)
Location: `/public_html/dicom/api/`

Find line ~188-199 in the `loadFromOrthanc()` function:
```php
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
```

#### 3. **`js/main.js`** (CRITICAL FIX)
Location: `/public_html/dicom/js/`

**Either**:
- Replace with `ORIGINAL_WORKING_main.js` (Option 1 above)
- **Or** manually fix line ~969 (Option 2 above)

---

## Verification Steps

After uploading, verify:

1. **Check URL loads**:
   ```
   https://e-connect.in/dicom/api/get_dicom_from_orthanc.php?instanceId=YOUR_INSTANCE_ID
   ```
   Should download DICOM file (not show 404)

2. **Check study loads**:
   ```
   https://e-connect.in/dicom/index.php?studyUID=...&orthancId=...
   ```
   Should display images without errors

3. **Check Console** (F12):
   Should see:
   ```
   ✓ Image loaded successfully: 1280 x 1024
   ✓ Orthanc image loaded and displayed successfully
   ```

---

## Production Config Check

Make sure `/public_html/dicom/config.php` has:

```php
// Production Orthanc settings
define('ORTHANC_URL', 'http://your-production-orthanc:8042');
define('ORTHANC_USER', 'your-username');
define('ORTHANC_PASS', 'your-password');
```

---

## What Changed Summary

**Only 1 critical JavaScript fix**:
- Line 969 in `main.js`: Changed `const image` inside try to `let image` before try

**2 supporting changes**:
- New file: `api/get_dicom_from_orthanc.php` (proxy to fetch DICOM)
- Modified: `api/load_study_fast.php` (added 2 fields: orthancInstanceId, isOrthancImage)

**All other files are optional**:
- index.php changes (UI cleanup) - NOT needed for image loading
- reporting-system.js changes (report fixes) - NOT needed for image loading
- print-manager.js (new feature) - NOT needed for image loading

---

## Troubleshooting

### If images still don't load after upload:

1. **Check proxy file exists**:
   - URL: https://e-connect.in/dicom/api/get_dicom_from_orthanc.php
   - Should NOT show 404

2. **Check Orthanc connectivity**:
   - SSH into server
   - Test: `curl http://localhost:8042/system` (or your Orthanc URL)
   - Should return JSON with Orthanc info

3. **Check PHP error log**:
   - cPanel → Error Log
   - Look for errors from `get_dicom_from_orthanc.php`

4. **Browser Console** (F12 → Console):
   - Look for specific error messages
   - Check Network tab for failed requests

5. **Compare working localhost vs production**:
   - Your localhost is working: http://localhost/papa/dicom_again/
   - Production should behave identically after upload

---

## Quick Upload Checklist

- [ ] Backup current production files
- [ ] Upload `api/get_dicom_from_orthanc.php` (NEW)
- [ ] Edit `api/load_study_fast.php` (add 2 lines)
- [ ] Replace `js/main.js` with ORIGINAL_WORKING_main.js
- [ ] Check config.php has correct Orthanc credentials
- [ ] Test image loading URL
- [ ] Check browser console for errors
- [ ] Verify images display correctly

---

## Success Criteria

✅ You'll know it's working when:
1. No "image load error undefined" messages
2. Console shows: "Image loaded successfully: [width] x [height]"
3. DICOM images display in the viewer
4. No 404 errors in Network tab

---

## Support Files Reference

All documentation files created for you:
1. **ORIGINAL_WORKING_main.js** - Complete working file (3092 lines)
2. **PRODUCTION_FIX_FILES.md** - Detailed fix instructions
3. **REVERT_GUIDE.md** - What to keep/revert guide
4. **FILES_FOR_PRODUCTION.md** - This file (complete guide)
5. **ORIGINAL_js_main.js.txt** - Explanation of scope fix

Use these files as reference when uploading to production!
