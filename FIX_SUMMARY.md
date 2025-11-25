# 🔧 FIXES APPLIED - Backup System Issues

## ✅ **Issues Fixed:**

### **1. Timezone Fixed to IST (India Standard Time)** ✅

**Changes Made:**
- ✅ Updated `.env` file: `APP_TIMEZONE=Asia/Kolkata`
- ✅ Added timezone setting in `includes/config.php`
- ✅ Set backup schedule to "hourly" for easier testing

**Verify:**
```bash
php -r "require 'includes/config.php'; echo date('Y-m-d H:i:s');"
```

---

### **2. JSON Parse Error Fixed** ✅

**Root Cause:** The "Unexpected token '<'" error occurs when:
1. An API returns HTML error page instead of JSON
2. Missing or invalid Google Drive credentials
3. PHP errors being output before JSON response

**Fixes Applied:**

#### **A. Better Error Handling in `backup-all-accounts.php`:**
```php
// Added JSON validation
$credentials = json_decode($account['credentials_json'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Invalid credentials JSON: ' . json_last_error_msg());
}

// Added database query error checking
if (!$accountsResult) {
    throw new Exception('Database query failed: ' . $db->error);
}
```

#### **B. Created Test Endpoint:**
- **NEW FILE**: `api/backup/test-backup-account.php`
- Tests Google Drive connection
- Validates credentials
- Returns detailed error messages

---

### **3. Hourly Backup Schedule Added** ✅

**Changes:**
- ✅ Added "hourly" option to auto-trigger (triggers every 1 hour)
- ✅ Set default schedule to hourly for testing
- ✅ Auto-trigger checks every 30 minutes

**Database Updated:**
```sql
UPDATE gdrive_backup_config
SET backup_schedule = 'hourly', backup_time = '00:00:00'
WHERE id = 1;
```

---

## 🧪 **Testing Steps:**

### **Step 1: Test Google Drive Connection**

Visit: http://localhost/papa/dicom_again/claude/api/backup/test-backup-account.php

**Expected Response (Success):**
```json
{
  "success": true,
  "message": "Google Drive connection successful!",
  "account_name": "Prasham's GDrive",
  "service_account_email": "dicom-backup-service@dicom-backup.iam.gserviceaccount.com",
  "credentials_valid": true
}
```

**Expected Response (Error):**
```json
{
  "success": false,
  "error": "Detailed error message here",
  "trace": "Stack trace for debugging"
}
```

---

### **Step 2: Test Backup All Accounts**

**Using Browser (Logged in as Admin):**

Visit: http://localhost/papa/dicom_again/claude/api/backup/backup-all-accounts.php

**Expected Response (Success):**
```json
{
  "success": true,
  "successful": 1,
  "failed": 0,
  "results": [
    {
      "account": "Prasham's GDrive",
      "status": "success",
      "filename": "dicom_viewer_backup_2025-11-25_19-50-00.zip"
    }
  ]
}
```

---

### **Step 3: Verify Timezone**

```bash
php -r "require 'c:/xampp/htdocs/papa/dicom_again/claude/includes/config.php'; echo 'Current IST Time: ' . date('Y-m-d H:i:s');"
```

Should show IST time (UTC+5:30)

---

### **Step 4: Test Auto-Trigger**

```bash
curl http://localhost/papa/dicom_again/claude/api/backup/auto-trigger.php
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "triggered": false,
    "reason": "Backup not due yet",
    "last_backup": null,
    "next_backup": "2025-11-25 20:47:00",
    "schedule": "hourly"
  }
}
```

---

## 🔍 **Common Errors & Solutions:**

### **Error: "Google Drive not configured"**

**Solution:**
1. Check if credentials exist in database:
```sql
SELECT * FROM backup_accounts WHERE is_active = 1;
```

2. If empty, you need to configure Google Drive:
   - Visit: http://localhost/papa/dicom_again/claude/admin/hospital-config.php
   - Add your service account credentials

---

### **Error: "No active backup accounts configured"**

**Solution:**
```sql
-- Check if account exists but is inactive
SELECT * FROM backup_accounts;

-- Activate it
UPDATE backup_accounts SET is_active = 1 WHERE id = 2;
```

---

### **Error: "The caller does not have permission"**

**This is the MOST COMMON error!**

**Solution:**
1. Open Google Drive: https://drive.google.com
2. Find folder: "DICOM_Viewer_Backups"
3. Right-click → Share
4. Add email: `dicom-backup-service@dicom-backup.iam.gserviceaccount.com`
5. Give "Editor" permission
6. Click "Send"

**Verify the folder is shared:**
- Go to folder in Google Drive
- Click "Manage access" (top right)
- Service account email should be listed with "Editor" access

---

### **Error: "Unexpected token '<' is not valid JSON"**

**Causes:**
1. HTML error page returned instead of JSON
2. PHP error/warning output before JSON
3. Missing/invalid credentials

**Debug:**
1. Check PHP error logs:
```bash
type "c:\xampp\htdocs\papa\dicom_again\claude\logs\backup.log"
```

2. Test the endpoint directly in browser (logged in as admin):
http://localhost/papa/dicom_again/claude/api/backup/backup-all-accounts.php

3. Check browser console (F12) for actual response

4. Use test endpoint:
http://localhost/papa/dicom_again/claude/api/backup/test-backup-account.php

---

## 📊 **Current Configuration:**

**Database Check:**
```sql
SELECT backup_enabled, backup_schedule, last_backup_at
FROM gdrive_backup_config;
```

**Should show:**
- backup_enabled: 0 (you need to enable it)
- backup_schedule: hourly
- last_backup_at: NULL (no backups yet)

**To Enable Backup:**
```sql
UPDATE gdrive_backup_config
SET backup_enabled = 1
WHERE id = 1;
```

---

## 🎯 **Quick Fix Summary:**

| Issue | Status | Solution |
|-------|--------|----------|
| Timezone wrong (UTC instead of IST) | ✅ FIXED | Changed to Asia/Kolkata |
| JSON parse error | ✅ FIXED | Better error handling + validation |
| No hourly option | ✅ ADDED | Added hourly schedule (1 hour) |
| Backup not triggering | ✅ FIXED | Auto-trigger every 30 min |
| Need to share folder | ⚠️ MANUAL | Share folder with service account |

---

## 📖 **References:**

Based on research, the "Unexpected token '<'" error in PHP/Google Drive API context typically means:

1. **HTML error page returned**: The API returned an HTML error (like 503 gateway error) instead of JSON
   - Source: [Stack Overflow - Google Drive API HTTP Response](https://stackoverflow.com/questions/39340374/php-google-drive-api-http-response)

2. **Authentication issues**: Invalid or expired access tokens
   - Source: [Google Developers - Handle Errors](https://developers.google.com/workspace/drive/api/guides/handle-errors)

3. **Wrong URL format**: Using sharing link instead of download link
   - Source: [Stack Overflow - Parse JSON from Google Drive](https://stackoverflow.com/questions/54492018/how-to-parse-json-response-from-google-drive-api-get-files-in-php)

---

**Last Updated**: 2025-11-25 19:48 IST
**System Status**: ✅ Ready for Testing

**Next Steps:**
1. Run test endpoint to verify Google Drive connection
2. Share folder with service account (if not done)
3. Enable backup in database
4. Test backup manually
5. Auto-trigger will handle rest
