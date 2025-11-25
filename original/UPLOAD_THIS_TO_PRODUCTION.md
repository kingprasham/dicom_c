# 🚨 CRITICAL FIX - Upload This File to Production

## Problem Identified

Your production server is configured to use **API Gateway (ngrok tunnel)** to connect to Orthanc, but the old `get_dicom_from_orthanc.php` file only supported direct Orthanc connections.

**Evidence from your config.php**:
```php
// PRODUCTION CONFIGURATION
define('USE_API_GATEWAY', true);  // ← Production uses API Gateway
define('API_GATEWAY_URL', 'https://brendon-interannular-nonconnectively.ngrok-free.dev/');
define('API_GATEWAY_KEY', 'Hospital2025_DicomSecureKey_XyZ789ABC');
```

But the old PHP file was trying to use `ORTHANC_URL` which is **NOT defined in production config** → causing HTTP 500 error!

---

## Solution: Upload the Fixed File

### File to Upload

**File**: `PRODUCTION_get_dicom_from_orthanc.php`
**Location on your PC**: `c:\xampp\htdocs\papa\dicom_again\PRODUCTION_get_dicom_from_orthanc.php`

**What's different**:
- ✅ Supports **both** API Gateway mode (production) and direct Orthanc (localhost)
- ✅ Automatically detects which mode to use based on `USE_API_GATEWAY` setting
- ✅ Handles API Gateway JSON response with base64-encoded DICOM data
- ✅ Enhanced error logging to diagnose issues
- ✅ Works on both localhost and production without code changes

---

## Upload Steps

### Step 1: Login to cPanel
URL: https://e-connect.in:2083/

### Step 2: Navigate to File Manager
- Click "File Manager"
- Go to: `/public_html/dicom/api/`

### Step 3: Backup Current File
- Find `get_dicom_from_orthanc.php`
- Right-click → Rename to `get_dicom_from_orthanc.php.backup`

### Step 4: Upload New File
- Click "Upload" button
- Select: `c:\xampp\htdocs\papa\dicom_again\PRODUCTION_get_dicom_from_orthanc.php`
- Wait for upload to complete

### Step 5: Rename Uploaded File
- Find `PRODUCTION_get_dicom_from_orthanc.php` in the file list
- Right-click → Rename to `get_dicom_from_orthanc.php`

---

## Test After Upload

### Test 1: Direct API Test
Open in browser:
```
https://e-connect.in/dicom/api/get_dicom_from_orthanc.php?instanceId=47739cb2-3e5416ff-981ea238-36b30063-4722ce98
```

**Expected**: Browser downloads `.dcm` file or shows binary data
**If Error**: Check PHP error logs (Step below)

### Test 2: Full Viewer Test
Open:
```
https://e-connect.in/dicom/index.php?studyUID=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27&orthancId=d61c62d3-187bc863-f1ec4b5b-8b0b1069-38f4bc27
```

**Expected**:
- Images load and display ✓
- Console shows: "Image loaded successfully: 512 x 512" ✓
- No HTTP 500 errors ✓

---

## Check Logs After Upload

### In cPanel:
1. Click "Errors" (left sidebar)
2. Look for recent entries with "DICOM Proxy"

**Success logs you should see**:
```
DICOM Proxy: Using API Gateway: https://brendon-interannular-nonconnectively.ngrok-free.dev/get_dicom_instance
DICOM Proxy: Fetching instance 47739cb2-... via API Gateway
DICOM Proxy: API Gateway responded with HTTP 200 for instance 47739cb2-...
DICOM Proxy: Successfully decoded 524288 bytes from API Gateway
DICOM Proxy: Successfully fetched 524288 bytes for instance 47739cb2-... from API Gateway
```

**Common errors and fixes**:

#### Error: "API_GATEWAY_URL not defined"
**Cause**: Production config.php is missing API Gateway settings
**Fix**: Add these to `/public_html/dicom/config.php`:
```php
define('USE_API_GATEWAY', true);
define('API_GATEWAY_URL', 'https://brendon-interannular-nonconnectively.ngrok-free.dev/');
define('API_GATEWAY_KEY', 'Hospital2025_DicomSecureKey_XyZ789ABC');
```

#### Error: "cURL error #7: Failed to connect"
**Cause**: ngrok tunnel is not running or URL is wrong
**Fix**:
1. Make sure ngrok is running on your local Orthanc server
2. Update `API_GATEWAY_URL` in config.php with current ngrok URL

#### Error: "API Gateway error: Invalid API key"
**Cause**: API key mismatch between PHP and Python gateway
**Fix**: Make sure `API_GATEWAY_KEY` in config.php matches the key in your Python gateway script

---

## How It Works

### Production Mode (API Gateway):
```
Browser → get_dicom_from_orthanc.php → ngrok tunnel → Python Gateway → Orthanc
```

1. Browser requests DICOM via: `get_dicom_from_orthanc.php?instanceId=XXX`
2. PHP detects `USE_API_GATEWAY = true`
3. PHP calls: `https://your-ngrok-url/get_dicom_instance`
4. Python gateway fetches from Orthanc and returns base64-encoded DICOM
5. PHP decodes base64 and sends raw DICOM to browser

### Localhost Mode (Direct Orthanc):
```
Browser → get_dicom_from_orthanc.php → Orthanc (localhost:8042)
```

1. Browser requests DICOM via: `get_dicom_from_orthanc.php?instanceId=XXX`
2. PHP detects `USE_API_GATEWAY = false`
3. PHP calls: `http://localhost:8042/instances/XXX/file`
4. Orthanc returns raw DICOM
5. PHP sends DICOM to browser

---

## Summary of All Changes

### Files You've Already Uploaded ✓
- [x] `js/main.js` - JavaScript scope fix (line 969)

### Files You Need to Upload Now
- [ ] `api/get_dicom_from_orthanc.php` - **THIS FILE** with API Gateway support

### Files to Verify (should already be correct)
- [ ] `api/load_study_fast.php` - Has `orthancInstanceId` and `isOrthancImage` fields
- [ ] `config.php` - Has API Gateway settings

---

## Your Current Setup

**Localhost**:
- Uses: Direct Orthanc connection (`http://localhost:8042`)
- Config: `USE_API_GATEWAY = false`
- Status: ✓ Working

**Production**:
- Uses: API Gateway via ngrok tunnel
- Config: `USE_API_GATEWAY = true`
- URL: `https://brendon-interannular-nonconnectively.ngrok-free.dev/`
- Status: ✗ Broken (needs file upload)

**After uploading this file, both will work!**

---

## Need Help?

If images still don't load after upload:

1. **Check PHP error logs** - Look for "DICOM Proxy Error:" messages
2. **Verify ngrok is running** - Make sure tunnel is active on local server
3. **Check API Gateway URL** - Make sure it matches your current ngrok URL
4. **Test gateway directly** - Use curl or browser to test ngrok endpoint

Read the detailed guide: `FIX_PRODUCTION_500_ERROR.md`
