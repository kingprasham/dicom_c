# 🧪 DICOM Viewer Pro - Complete Testing & Setup Guide

## ✅ **Composer Installation Complete!**

All dependencies have been successfully installed:
- ✅ Google API Client (`google/apiclient`)
- ✅ PHP DotEnv (`vlucas/phpdotenv`)
- ✅ Deep Copy (`myclabs/deep-copy`)
- ✅ PHPUnit and testing libraries

---

## 🔧 **1. Orthanc Port Configuration**

### **Q: If I upload a new port for Orthanc, will it change in C:/ORTHANC?**

**Answer**: Partially. Here's how it works:

1. **DICOM files are stored** in `C:\Orthanc\OrthancStorage` (Orthanc's storage)
2. **Your web app connects** to Orthanc via the URL in `.env` file

### **To Change Orthanc Port:**

**Step 1:** Update Orthanc's configuration file (wherever Orthanc is installed)
```json
{
  "HttpPort": 8042,  // Change this to your new port
  ...
}
```

**Step 2:** Update your `.env` file:
```bash
# Edit: c:\xampp\htdocs\papa\dicom_again\claude\config\.env
ORTHANC_URL=http://localhost:YOUR_NEW_PORT
```

**Step 3:** Restart Orthanc service

---

## 🤖 **2. Automatic Backup System (NO Task Scheduler Needed!)**

### **What I Created:**

I've implemented an **automatic backup trigger** that runs:
- ✅ **Every time you load any page** (patients.html)
- ✅ **Checks every 30 minutes** while page is open
- ✅ **Triggers backup automatically** when due (daily/weekly/monthly)
- ✅ **Runs silently in background** - no interruption

### **How It Works:**

```javascript
// Added to patients.html:
function autoTriggerBackup() {
    // Checks if backup is due
    // If yes, triggers it in background
    // If no, does nothing
}

// Runs immediately on page load
autoTriggerBackup();

// Then checks every 30 minutes
setInterval(autoTriggerBackup, 1800000);
```

### **Files Created/Modified:**

1. ✅ **NEW**: `api/backup/auto-trigger.php` - Auto-trigger endpoint
2. ✅ **MODIFIED**: `pages/patients.html` - Added auto-trigger JavaScript

### **No More Manual Work!**

- ❌ No Task Scheduler setup needed
- ❌ No cron jobs needed
- ✅ Just keep your browser open or visit the page regularly
- ✅ Backup runs automatically when due

---

## 📋 **3. Complete Testing Checklist**

### **TEST 1: Verify Composer Dependencies** ✅

```bash
cd c:\xampp\htdocs\papa\dicom_again\claude
php -r "require 'vendor/autoload.php'; echo 'Vendor OK!';"
```

**Expected Output:** `Vendor OK!`

---

### **TEST 2: Configure Google Drive Backup**

#### **Option A: Using Service Account (RECOMMENDED)**

1. **Visit Google Cloud Console**: https://console.developers.google.com

2. **Create New Project**:
   - Project Name: "DICOM Backup Service"
   - Click "CREATE"

3. **Enable Google Drive API**:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "ENABLE"

4. **Create Service Account**:
   - Go to "IAM & Admin" > "Service Accounts"
   - Click "CREATE SERVICE ACCOUNT"
   - Name: `dicom-backup-service`
   - Role: "Basic" > "Editor" (optional)
   - Click "DONE"

5. **Create JSON Key**:
   - Click on your service account email
   - Go to "KEYS" tab
   - Click "ADD KEY" > "Create new key"
   - Select "JSON"
   - Click "CREATE"
   - **Save the JSON file** (e.g., `dicom-backup-credentials.json`)

6. **Share Google Drive Folder**:
   - Open Google Drive: https://drive.google.com
   - Create folder: "DICOM_Viewer_Backups"
   - Right-click folder > "Share"
   - Copy the **service account email** from JSON file (looks like: `xxx@xxx.iam.gserviceaccount.com`)
   - Paste email and give "Editor" permission
   - Click "Send"

7. **Configure in Your App**:
   - Visit: http://localhost/papa/dicom_again/claude/admin/gdrive-guide.php
   - Follow the on-screen instructions
   - Upload your JSON credentials file

#### **Option B: Using OAuth 2.0**

1. Visit: http://localhost/papa/dicom_again/claude/admin/gdrive-guide.php
2. Follow the OAuth 2.0 setup instructions
3. Authorize your Google account

---

### **TEST 3: Test Backup System**

#### **3A: Check Current Configuration**

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root dicom_viewer_v2_production -e "SELECT backup_enabled, backup_schedule, last_backup_at FROM gdrive_backup_config;"
```

#### **3B: Enable Backup**

Visit: http://localhost/papa/dicom_again/claude/admin/settings.php
- Go to "Backup Settings" section
- Enable "Automatic Backups"
- Set schedule: **Daily**
- Set time: **02:00** (or any time)
- Click "Save Settings"

#### **3C: Test Manual Backup**

**Method 1: Via API** (Requires Google Drive configured)
```bash
curl -X POST http://localhost/papa/dicom_again/claude/api/backup/backup-now.php \
  -H "Content-Type: application/json" \
  -H "Cookie: YOUR_SESSION_COOKIE"
```

**Method 2: Via Browser**
- Login as admin
- Open: http://localhost/papa/dicom_again/claude/admin/settings.php
- Find "Backup" section
- Click "Backup Now" button

#### **3D: Test Auto-Trigger**

1. Open browser
2. Visit: http://localhost/papa/dicom_again/claude/pages/patients.html
3. Open Developer Console (F12)
4. Look for message: "Auto-backup check (silent)"
5. **Note**: Backup only triggers if it's DUE based on schedule

---

### **TEST 4: Verify Automatic Trigger**

#### **Test Auto-Trigger Endpoint Directly**

```bash
curl http://localhost/papa/dicom_again/claude/api/backup/auto-trigger.php
```

**Expected Response** (if not due):
```json
{
  "success": true,
  "data": {
    "triggered": false,
    "reason": "Backup not due yet",
    "last_backup": "2025-11-25 10:00:00",
    "next_backup": "2025-11-26 10:00:00",
    "schedule": "daily"
  }
}
```

**Expected Response** (if due):
```json
{
  "success": true,
  "data": {
    "triggered": true,
    "reason": "Backup was due",
    "schedule": "daily",
    "message": "Backup process started in background"
  }
}
```

---

### **TEST 5: Check Logs**

```bash
# Check backup logs
type "c:\xampp\htdocs\papa\dicom_again\claude\logs\backup.log"

# Check auto-backup logs
type "c:\xampp\htdocs\papa\dicom_again\claude\logs\auto-backup.log"

# Check app logs
type "c:\xampp\htdocs\papa\dicom_again\claude\logs\app.log"
```

---

### **TEST 6: Verify Backup in Google Drive**

1. Open Google Drive: https://drive.google.com
2. Find folder: "DICOM_Viewer_Backups"
3. Check for ZIP files: `dicom_viewer_backup_YYYY-MM-DD_HH-MM-SS.zip`

---

### **TEST 7: Database Verification**

```bash
# Check backup history
"C:\xampp\mysql\bin\mysql.exe" -u root dicom_viewer_v2_production -e "SELECT * FROM backup_history ORDER BY created_at DESC LIMIT 5;"

# Check backup configuration
"C:\xampp\mysql\bin\mysql.exe" -u root dicom_viewer_v2_production -e "SELECT * FROM gdrive_backup_config;"
```

---

## 🎯 **4. Quick Start Guide**

### **Minimum Steps to Get Everything Working:**

1. ✅ **Composer** - Already done! (`composer install`)

2. **Configure Google Drive** (15 minutes):
   - Create Google Cloud project
   - Enable Drive API
   - Create Service Account
   - Download JSON key
   - Share Drive folder with service account email
   - Upload credentials to your app

3. **Enable Backup** (2 minutes):
   - Visit: http://localhost/papa/dicom_again/claude/admin/settings.php
   - Enable "Automatic Backups"
   - Set schedule: Daily at 02:00
   - Save

4. **Done!**
   - Backup will trigger automatically every 24 hours
   - Just keep PC on and visit the patients page once a day
   - Or leave browser tab open (checks every 30 min)

---

## 🔍 **5. Troubleshooting**

### **Error: "Unexpected token '<' JSON error"**

**Status**: ✅ FIXED
- Cause: Broken Composer dependencies
- Solution: Ran `composer install`
- Verification: `php -r "require 'vendor/autoload.php'; echo 'OK';"`

### **Backup Not Triggering**

**Check:**
1. Is backup enabled? (Check admin settings)
2. Has enough time passed? (Daily = 24 hours)
3. Is Google Drive configured? (Check `gdrive_backup_config` table)
4. Check logs: `logs/backup.log` and `logs/auto-backup.log`

### **Google Drive Authentication Failed**

**Solutions:**
1. Re-download service account JSON key
2. Make sure you shared the Drive folder with service account email
3. Verify email is EXACTLY as shown in JSON file
4. Try OAuth 2.0 method instead

---

## 📊 **6. Monitoring Dashboard**

Visit: http://localhost/papa/dicom_again/claude/admin/settings.php

**You can see:**
- Last backup time
- Next scheduled backup
- Backup history
- Storage usage
- Error logs

---

## 🎉 **Summary of What's Fixed/Added**

### **✅ FIXED:**
1. Composer dependencies (Google API client restored)
2. JSON parse error (was caused by missing dependencies)
3. Backup system now functional

### **✅ ADDED:**
1. Automatic backup trigger system (no Task Scheduler!)
2. Auto-check every 30 minutes when page is open
3. Background execution (doesn't block user)
4. New API endpoint: `api/backup/auto-trigger.php`

### **📝 NEEDS CONFIGURATION:**
1. Google Drive API credentials (one-time setup)
2. Enable backup in admin settings

---

## 📞 **Need Help?**

**Common URLs:**
- Admin Settings: http://localhost/papa/dicom_again/claude/admin/settings.php
- Google Drive Guide: http://localhost/papa/dicom_again/claude/admin/gdrive-guide.php
- Patient List: http://localhost/papa/dicom_again/claude/pages/patients.html

**Log Files:**
- Backup: `c:\xampp\htdocs\papa\dicom_again\claude\logs\backup.log`
- Auto-Backup: `c:\xampp\htdocs\papa\dicom_again\claude\logs\auto-backup.log`
- App: `c:\xampp\htdocs\papa\dicom_again\claude\logs\app.log`

---

**Last Updated**: 2025-11-25
**System Status**: ✅ Ready for Testing