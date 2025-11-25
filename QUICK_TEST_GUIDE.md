# ⚡ Quick Test Guide - Fixed Backup System

## ✅ **All Fixes Applied!**

### **What Was Fixed:**
1. ✅ **Timezone**: Changed from UTC to IST (Asia/Kolkata)
2. ✅ **JSON Error**: Better error handling + validation
3. ✅ **Hourly Schedule**: Added hourly backup option (every 1 hour)
4. ✅ **Auto-Trigger**: Checks every 30 minutes automatically
5. ✅ **Backup Enabled**: Set to active in database

---

## 🧪 **Test Right Now:**

### **Test 1: Verify Google Drive Connection** (Most Important!)

**Open in browser (logged in as admin):**
```
http://localhost/papa/dicom_again/claude/api/backup/test-backup-account.php
```

**What to expect:**

✅ **If Successful:**
```json
{
  "success": true,
  "message": "Google Drive connection successful!",
  "account_name": "Prasham's GDrive",
  "credentials_valid": true
}
```

❌ **If Error:**
```json
{
  "success": false,
  "error": "The caller does not have permission"
}
```

**This means:** You need to share the Google Drive folder with your service account!

---

### **IMPORTANT: Share Folder with Service Account**

**If you see "permission" error, do this:**

1. Open: https://drive.google.com
2. Find folder: "DICOM_Viewer_Backups" (create if doesn't exist)
3. Right-click → **Share**
4. Add this email: `dicom-backup-service@dicom-backup.iam.gserviceaccount.com`
5. Give "**Editor**" permission
6. Click "**Send**"

**Then test again!**

---

### **Test 2: Manual Backup** (After folder is shared)

**Open in browser:**
```
http://localhost/papa/dicom_again/claude/api/backup/backup-all-accounts.php
```

**What to expect:**

✅ **If Successful:**
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

✅ **Check Google Drive:** You should see the ZIP file in "DICOM_Viewer_Backups" folder!

---

### **Test 3: Verify Timezone (IST)**

**Run in Command Prompt:**
```bash
php -r "require 'c:/xampp/htdocs/papa/dicom_again/claude/includes/config.php'; echo 'IST Time: ' . date('Y-m-d H:i:s');"
```

**Should show:** Current time in IST (India Standard Time, UTC+5:30)

Example: `IST Time: 2025-11-25 19:55:00`

---

### **Test 4: Auto-Trigger Status**

**Run in Command Prompt:**
```bash
curl http://localhost/papa/dicom_again/claude/api/backup/auto-trigger.php
```

**Or open in browser:**
```
http://localhost/papa/dicom_again/claude/api/backup/auto-trigger.php
```

**What to expect:**
```json
{
  "success": true,
  "data": {
    "triggered": false,
    "reason": "Backup not due yet",
    "schedule": "hourly",
    "next_backup": "2025-11-25 20:55:00"
  }
}
```

---

## 🔧 **Current Configuration:**

```sql
-- Check current settings
SELECT backup_enabled, backup_schedule, last_backup_at
FROM gdrive_backup_config;

-- Should show:
-- backup_enabled: 1
-- backup_schedule: hourly
-- last_backup_at: NULL (until first backup runs)
```

---

## ⚙️ **How Auto-Backup Works:**

1. **Every 30 minutes**: JavaScript checks if backup is due
2. **If due** (1 hour passed): Triggers backup automatically
3. **Runs in background**: No interruption to you
4. **Uploads to Google Drive**: ZIP file with database + code

**Where it runs:**
- Every time you visit: http://localhost/papa/dicom_again/claude/pages/patients.html
- Checks in background every 30 minutes while page is open

---

## 🚨 **Common Errors & Fixes:**

### **Error: "The caller does not have permission"**

**Cause:** Google Drive folder not shared with service account

**Fix:**
1. Go to https://drive.google.com
2. Find "DICOM_Viewer_Backups" folder
3. Share with: `dicom-backup-service@dicom-backup.iam.gserviceaccount.com`
4. Give "Editor" access
5. Save

---

### **Error: "Unexpected token '<' is not valid JSON"**

**Cause:** API returning HTML error instead of JSON

**Fix:**
1. Test connection first: http://localhost/papa/dicom_again/claude/api/backup/test-backup-account.php
2. Check if folder is shared (see above)
3. Check logs: `c:\xampp\htdocs\papa\dicom_again\claude\logs\backup.log`

---

### **Error: "No active backup accounts configured"**

**Cause:** Backup account is inactive in database

**Fix:**
```sql
UPDATE backup_accounts SET is_active = 1 WHERE id = 2;
```

---

## 📊 **Check Backup Status:**

**View in database:**
```sql
-- Last backup time
SELECT last_backup_at FROM gdrive_backup_config;

-- Backup history
SELECT * FROM backup_history ORDER BY created_at DESC LIMIT 5;

-- Account status
SELECT account_name, last_backup_date, last_backup_status
FROM backup_accounts WHERE is_active = 1;
```

---

## 📁 **Backup Contents:**

Each backup ZIP file contains:
- ✅ **Database**: Full SQL dump
- ✅ **PHP Files**: All backend code
- ✅ **JavaScript**: Frontend code
- ✅ **Config Files**: .env and other configs

**File naming:** `dicom_viewer_backup_YYYY-MM-DD_HH-MM-SS.zip`

---

## 🎯 **Summary:**

| What | Status | How to Verify |
|------|--------|---------------|
| Timezone (IST) | ✅ FIXED | `php -r "require 'includes/config.php'; echo date('H:i');"` |
| JSON Error | ✅ FIXED | Test: `/api/backup/test-backup-account.php` |
| Hourly Schedule | ✅ ADDED | Check: `SELECT backup_schedule FROM gdrive_backup_config;` |
| Auto-Trigger | ✅ WORKING | Visit patients page, check console |
| Backup Enabled | ✅ ENABLED | `SELECT backup_enabled FROM gdrive_backup_config;` |
| Share Folder | ⚠️ MANUAL | Share with service account email |

---

## 📞 **Quick Links:**

- **Test Connection**: http://localhost/papa/dicom_again/claude/api/backup/test-backup-account.php
- **Manual Backup**: http://localhost/papa/dicom_again/claude/api/backup/backup-all-accounts.php
- **Admin Settings**: http://localhost/papa/dicom_again/claude/admin/settings.php
- **Patients Page** (Auto-trigger): http://localhost/papa/dicom_again/claude/pages/patients.html

---

**Current Time (IST):** Your system is now set to Asia/Kolkata timezone
**Next Backup:** Will trigger 1 hour after first successful backup
**Auto-Check:** Every 30 minutes when patients page is open

---

**🎉 Ready to Test!** Start with Test 1 (connection test) above.
