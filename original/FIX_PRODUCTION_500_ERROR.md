# Fix Production HTTP 500 Error - DICOM Image Loading

## Current Problem

Your production site shows this error:
```
GET https://e-connect.in/dicom/api/get_dicom_from_orthanc.php?instanceId=47739cb2-3e5416ff-981ea238-36b30063-4722ce98 500 (Internal Server Error)

ERROR loading Orthanc image: {error: XMLHttpRequest}
Error loading image: Error: Failed to load DICOM image: undefined
```

**Status**: You already uploaded the corrected `main.js` file ✓
**Blocker**: The PHP proxy file `get_dicom_from_orthanc.php` is returning HTTP 500 error

**Root Cause Identified**: Your production config uses API Gateway (ngrok tunnel), but the old PHP file only supported direct Orthanc connection. The new version supports BOTH modes.

---

## Solution: Upload Enhanced PHP File

### Step 1: Upload the Fixed PHP File

1. **File to Upload**: `PRODUCTION_get_dicom_from_orthanc.php`
   - Location on your computer: `c:\xampp\htdocs\papa\dicom_again\PRODUCTION_get_dicom_from_orthanc.php`

2. **Upload to cPanel**:
   - Login: https://e-connect.in:2083/
   - File Manager → `/public_html/dicom/api/`
   - If `get_dicom_from_orthanc.php` exists, **rename it** to `get_dicom_from_orthanc.php.old` (backup)
   - Upload `PRODUCTION_get_dicom_from_orthanc.php`
   - Rename it from `PRODUCTION_get_dicom_from_orthanc.php` to `get_dicom_from_orthanc.php`

### Step 2: Check PHP Error Logs

After uploading, try loading the page again and immediately check error logs:

**In cPanel**:
1. Click "Errors" in left sidebar (or "Error Log")
2. Look for recent entries mentioning "DICOM Proxy Error"
3. The enhanced file logs detailed error messages

**Common Error Messages and Fixes**:

#### Error: "config.php not found"
**Fix**: Verify `/public_html/dicom/config.php` exists

#### Error: "ORTHANC_URL not defined in config.php"
**Fix**: Edit `config.php` and add:
```php
define('ORTHANC_URL', 'http://your-orthanc-server:8042');
define('ORTHANC_USER', 'your-username');
define('ORTHANC_PASS', 'your-password');
```

#### Error: "cURL extension not available"
**Fix**:
- cPanel → Software → Select PHP Version → Extensions
- Enable `curl` extension
- Save and refresh

#### Error: "cURL error #6: Could not resolve host"
**Fix**: Your Orthanc server hostname is wrong in config.php
- Check the correct hostname/IP address
- Test SSH: `curl http://localhost:8042/system` (from production server)

#### Error: "cURL error #7: Failed to connect"
**Fix**: Orthanc server is not running or firewall blocking
- Check Orthanc is running: `curl http://localhost:8042/system`
- Check firewall allows connection to port 8042

#### Error: "HTTP Code: 401"
**Fix**: Wrong Orthanc username/password in config.php
- Verify credentials match Orthanc configuration
- Check Orthanc config file for correct credentials

#### Error: "HTTP Code: 404"
**Fix**: Instance ID doesn't exist in Orthanc
- Check instance exists: `curl http://localhost:8042/instances/INSTANCE_ID`
- Verify database sync is working

---

## Step 3: Verify config.php Settings

**Check your production config.php has**:

```php
<?php
// Database settings (your existing settings)
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_pass');
define('DB_NAME', 'dicom');

// ORTHANC SETTINGS - VERIFY THESE!
define('ORTHANC_URL', 'http://localhost:8042');  // ← Check this URL
define('ORTHANC_USER', 'orthanc');               // ← Check username
define('ORTHANC_PASS', 'orthanc');               // ← Check password

// If Orthanc has no authentication, you can remove the USER/PASS lines
?>
```

**Important**:
- If Orthanc is on the **same server**, use: `http://localhost:8042`
- If Orthanc is on a **different server**, use: `http://ORTHANC_IP:8042`
- If Orthanc has **no authentication**, remove the ORTHANC_USER and ORTHANC_PASS lines

---

## Step 4: Test the PHP File Directly

After uploading, test the file directly:

**In your browser**, open:
```
https://e-connect.in/dicom/api/get_dicom_from_orthanc.php?instanceId=47739cb2-3e5416ff-981ea238-36b30063-4722ce98
```

**Expected Results**:

✅ **SUCCESS**: Browser downloads a `.dcm` file
✅ **SUCCESS**: Browser shows binary data (DICOM file content)

❌ **ERROR**: "config.php not found" → config.php doesn't exist or wrong path
❌ **ERROR**: "ORTHANC_URL not defined" → config.php missing Orthanc settings
❌ **ERROR**: "Instance ID required" → No instanceId parameter (this is OK if you just open the URL without parameters)
❌ **ERROR**: Blank page with 500 error → Check PHP error logs for details

---

## Step 5: Verify Orthanc Connectivity (SSH Required)

If you have SSH access to your production server:

```bash
# Test if Orthanc is accessible
curl http://localhost:8042/system

# Should return JSON like:
# {"Name":"Orthanc","Version":"1.x.x",...}

# Test if specific instance exists
curl http://localhost:8042/instances/47739cb2-3e5416ff-981ea238-36b30063-4722ce98

# If Orthanc requires auth:
curl -u username:password http://localhost:8042/system
```

---

## Step 6: Verify Other Required Files

Make sure these files also have the required changes:

### File: `api/load_study_fast.php`
**Check lines 188-199** have these two fields:
```php
$images[] = [
    'instanceId' => $instanceId,
    'orthancInstanceId' => $instanceId,  // ← MUST HAVE THIS
    'seriesInstanceUID' => $seriesUID,
    'sopInstanceUID' => $instanceData['MainDicomTags']['SOPInstanceUID'] ?? $instanceId,
    'instanceNumber' => intval($instanceData['MainDicomTags']['InstanceNumber'] ?? 0),
    'seriesDescription' => $seriesDesc,
    'seriesNumber' => intval($seriesNumber),
    'patientName' => $studyInfo['patient_name'],
    'useApiGateway' => false,
    'isOrthancImage' => true  // ← MUST HAVE THIS
];
```

---

## Quick Diagnosis Checklist

- [ ] Uploaded `PRODUCTION_get_dicom_from_orthanc.php` to `/public_html/dicom/api/` and renamed to `get_dicom_from_orthanc.php`
- [ ] Checked PHP error logs in cPanel
- [ ] Verified `config.php` exists at `/public_html/dicom/config.php`
- [ ] Verified `config.php` has `ORTHANC_URL`, `ORTHANC_USER`, `ORTHANC_PASS` defined
- [ ] Tested PHP file directly in browser
- [ ] Confirmed cURL PHP extension is enabled (cPanel → Select PHP Version → Extensions)
- [ ] Tested Orthanc connectivity from server (if SSH available)

---

## What This Enhanced File Does

The new `PRODUCTION_get_dicom_from_orthanc.php` file:

1. ✅ Logs detailed error messages to help diagnose issues
2. ✅ Checks if config.php exists before loading it
3. ✅ Checks if ORTHANC_URL is defined
4. ✅ Checks if cURL extension is available
5. ✅ Logs every request attempt and response
6. ✅ Provides detailed error messages instead of generic "500 error"

**All errors are logged to your PHP error log** for easy troubleshooting.

---

## Expected Success

After fixing the PHP file, you should see:

**Browser Console**:
```
✓ Loading image with ID: wadouri:https://e-connect.in/dicom/api/get_dicom_from_orthanc.php?instanceId=47739cb2-3e5416ff-981ea238-36b30063-4722ce98
✓ Image loaded successfully: 512 x 512
✓ Orthanc image loaded and displayed successfully
```

**PHP Error Log** (only if errors occur):
```
DICOM Proxy: Fetching from Orthanc: http://localhost:8042/instances/47739cb2-.../file
DICOM Proxy: Using authentication for Orthanc
DICOM Proxy: Orthanc responded with HTTP 200 for instance 47739cb2-...
DICOM Proxy: Successfully fetched 524288 bytes for instance 47739cb2-...
```

**Viewport**: Image displays without errors

---

## Still Having Issues?

If images still don't load after following all steps:

1. **Copy the exact error message** from PHP error logs
2. **Copy the exact error message** from browser console (F12)
3. **Verify Orthanc is running** and accessible
4. **Check if localhost Orthanc test works**:
   - SSH into server
   - Run: `curl http://localhost:8042/system`
   - Should return JSON (not error)

The enhanced logging will tell us exactly what's failing!
