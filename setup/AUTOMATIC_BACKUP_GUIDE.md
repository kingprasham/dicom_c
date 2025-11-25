# Automatic Backup Schedule - How It Works

## Overview
Your DICOM Viewer can automatically backup to Dropbox (or Google Drive) at configured intervals.

---

## ✅ Fixed Issues

### 1. Backed Up KPI Now Shows Correct Count
- **Before:** Showed 0 (was counting from wrong table)
- **After:** Shows actual number of successful backups from `backup_history` table
- **Refresh the page** to see the updated count!

### 2. Dropbox Backups Working
- ✅ Files are successfully uploading to Dropbox
- ✅ Backups are being recorded in database
- ✅ KPI counter will update after each backup

---

## How to Configure Automatic Backups

### Step 1: Set Schedule (Hospital Config Page)

1. Login to admin panel
2. Go to **Hospital Configuration**
3. Find **"Google Drive & Dropbox Backup"** section
4. Click **"Configure Schedule"** button
5. Choose frequency:
   - Every 1 hour
   - Every 3 hours
   - Every 6 hours (Recommended)
   - Every 12 hours
   - Every 24 hours (Daily)
6. Enable **"Enable Automatic Backups"** checkbox
7. Click **"Save Schedule"**

### Step 2: Set Up Windows Task Scheduler

For automatic backups to run, you need Windows to execute the scheduler script every hour.

**Option A: Use the Setup Script (Recommended)**

I can create an automated setup script for you. Just run:
```
setup/setup_backup_scheduler.bat
```

**Option B: Manual Setup**

1. Open **Task Scheduler** (Start → search "Task Scheduler")
2. Click **"Create Basic Task"**
3. Name: "DICOM Backup Scheduler"
4. Trigger: **Daily**, start at midnight, recur every day
5. **Advanced** → Repeat task every **1 hour** for duration of **1 day**
6. Action: **Start a program**
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\papa\dicom_again\claude\backup-scheduler.php`
7. Finish

---

## How It Works

### The Process:

1. **Every Hour:** Windows Task Scheduler runs `backup-scheduler.php`
2. **Script Checks:** Is it time for a backup? (based on your configured interval)
3. **If Yes:** Creates backup and uploads to all active accounts (Dropbox, Google Drive)
4. **Updates:** Last backup time is recorded
5. **Next Backup:** Calculated based on your interval (e.g., 6 hours from now)

### Example with 6-Hour Interval:

- **12:00 PM** - Backup runs, next scheduled for 6:00 PM
- **1:00 PM** - Task Scheduler runs, but skips (not time yet)
- **2:00 PM** - Skips
- **3:00 PM** - Skips
- **4:00 PM** - Skips
- **5:00 PM** - Skips
- **6:00 PM** - Backup runs again! Next scheduled for 12:00 AM

---

## Monitoring Your Backups

### Check Backup Status:

1. **Hospital Config Page KPIs:**
   - "Backed Up" shows total successful backups
   - Refreshes every time you load the page

2. **Backup Accounts List:**
   - Shows "Last backup" date/time for each account
   - Shows status (success/failed)

3. **Dropbox Web Interface:**
   - Go to https://www.dropbox.com/
   - Navigate to `/DICOM_Backups/`
   - You'll see all backup ZIP files with timestamps

### Backup Files Look Like:
```
dicom_backup_2025-11-25_21-49-24.zip
dicom_backup_2025-11-25_15-30-12.zip
dicom_backup_2025-11-25_09-15-45.zip
```

---

## Storage Management

### Automatic Cleanup:
- Old backups are automatically deleted after **30 days**
- This prevents filling up your Dropbox storage

### Manual Cleanup:
If you need to delete old backups:
1. Go to Dropbox web interface
2. Navigate to `/DICOM_Backups/`
3. Delete old ZIP files

---

## Troubleshooting

### Backups Not Running Automatically?

1. **Check Task Scheduler:**
   - Open Task Scheduler
   - Find "DICOM Backup Scheduler" task
   - Check "Last Run Result" (should show "Success")

2. **Check Schedule Configuration:**
   - Hospital Config → Configure Schedule
   - Verify "Enable Automatic Backups" is checked
   - Note the "Next Backup" time

3. **Check Logs:**
   - Look in `logs/backup_scheduler.log` (if logging is enabled)

### KPI Still Shows 0?

- Refresh the page (Ctrl+F5)
- Check database: Run this query in phpMyAdmin:
  ```sql
  SELECT COUNT(*) FROM backup_history WHERE status='completed';
  ```

### Backup Failed?

Check the error message in:
- Backup accounts list (shows last error)
- `backup_history` table → `error_message` column

---

## Best Practices

1. **Start with 6-hour interval** - Good balance between data protection and storage
2. **Monitor for first 24 hours** - Make sure backups are running
3. **Check Dropbox storage** - Ensure you have enough space
4. **Test restore** - Periodically verify backup files work

---

## What Gets Backed Up?

Each backup ZIP contains:
- ✅ **Database** - All your data (SQL dump)
- ✅ **DICOM Files** - Patient studies (if they exist in `dicom_files/`)
- ❌Configuration files (for security)
- ❌ PHP code (not needed for restore)

### Typical Backup Size:
- **Database only:** ~1-10 MB
- **With DICOM files:** Can be larger depending on studies

---

Your backups are now fully automated! 🎉
