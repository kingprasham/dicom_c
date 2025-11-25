# Google Drive Backup System - Installation & Configuration Guide
## Hospital DICOM Viewer Pro v2.0

---

## Table of Contents
1. [System Overview](#system-overview)
2. [Prerequisites](#prerequisites)
3. [Installation Steps](#installation-steps)
4. [Google Cloud Setup](#google-cloud-setup)
5. [Application Configuration](#application-configuration)
6. [Automated Backup Setup](#automated-backup-setup)
7. [Testing](#testing)
8. [API Usage](#api-usage)
9. [Troubleshooting](#troubleshooting)

---

## System Overview

The Google Drive Backup System provides:
- **Automated daily/weekly/monthly backups** of database and application files
- **Secure storage** on Google Drive
- **One-click restore** functionality
- **Automatic cleanup** of old backups based on retention policy
- **Admin-only access** with full audit logging
- **Progress tracking** and notifications

### Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Panel (Web UI)                     │
│          Configure, Monitor, Backup, Restore                 │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│                  API Endpoints (/api/backup/)                │
│  configure-gdrive | backup-now | list-backups | restore     │
│  status | test-connection | oauth-callback | delete         │
└─────────────────────┬───────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────┐
│          GoogleDriveBackup Class (PHP)                       │
│  Database Backup | File Backup | Upload | Download          │
│  Restore | Cleanup | Authentication                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
┌───────▼────────┐         ┌────────▼────────┐
│  MySQL Database│         │  Google Drive   │
│  Local Storage │         │  Cloud Storage  │
└────────────────┘         └─────────────────┘
```

---

## Prerequisites

### 1. Server Requirements
- PHP 8.2 or higher
- MySQL/MariaDB
- Composer
- Web server (Apache/Nginx)

### 2. PHP Extensions
```
✓ mysqli
✓ zip
✓ curl
✓ json
✓ mbstring
```

Verify with:
```bash
php -m | grep -E "mysqli|zip|curl|json|mbstring"
```

### 3. Optional Tools
- `mysqldump` (for faster database backups)
- System falls back to PHP-based backup if not available

---

## Installation Steps

### Step 1: Install Google API Client Library

```bash
cd c:\xampp\htdocs\papa\dicom_again\claude
composer require google/apiclient:^2.15
```

Expected output:
```
Installing google/apiclient (v2.15.0)
...
Package manifest generated successfully.
```

### Step 2: Verify Installation

Run the test script:
```bash
cd c:\xampp\htdocs\papa\dicom_again\claude\scripts
php test-backup.php
```

Expected output:
```
=== Hospital DICOM Viewer Pro v2.0 - Backup System Test ===

[TEST 1] Testing database connection...
✓ Database connection successful

[TEST 2] Checking backup configuration...
✓ Backup configuration found
  - Backup Enabled: No
  - Schedule: daily
  ...

[TEST 6] Checking Google API library...
✓ Google API client library is installed
```

### Step 3: Create Required Directories

The system will auto-create these, but you can manually ensure they exist:

```bash
# Windows
mkdir c:\xampp\htdocs\papa\dicom_again\claude\backups\temp
mkdir c:\xampp\htdocs\papa\dicom_again\claude\logs

# Linux/Mac
mkdir -p /path/to/backups/temp
mkdir -p /path/to/logs
```

Set permissions (Linux/Mac):
```bash
chmod 755 backups/temp
chmod 755 logs
```

---

## Google Cloud Setup

### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "Create Project"
3. Project name: `Hospital DICOM Viewer Backup`
4. Click "Create"

### Step 2: Enable Google Drive API

1. In your project, go to **APIs & Services** > **Library**
2. Search for "Google Drive API"
3. Click "Google Drive API"
4. Click "Enable"
5. Wait for activation (usually instant)

### Step 3: Create OAuth 2.0 Credentials

1. Go to **APIs & Services** > **Credentials**
2. Click **"Create Credentials"** > **"OAuth client ID"**
3. If prompted, configure OAuth consent screen:
   - User Type: **Internal** (for organization) or **External**
   - App name: `Hospital DICOM Viewer Backup`
   - User support email: Your email
   - Developer contact: Your email
   - Scopes: Add `../auth/drive.file` (if asked)
   - Save and continue

4. Create OAuth Client ID:
   - Application type: **Web application**
   - Name: `DICOM Viewer Backup Client`
   - Authorized JavaScript origins:
     ```
     http://localhost
     https://yourdomain.com
     ```
   - Authorized redirect URIs:
     ```
     http://localhost/api/backup/oauth-callback.php
     https://yourdomain.com/api/backup/oauth-callback.php
     ```
   - Click "Create"

5. **IMPORTANT**: Copy your credentials:
   - Client ID: `1234567890-abcdefg.apps.googleusercontent.com`
   - Client Secret: `GOCSPX-abcd1234efgh5678`
   - Save these securely!

### Step 4: Configure OAuth Consent Screen (if External)

1. Add test users (if using External type):
   - Go to OAuth consent screen
   - Add your email as test user
   - This allows testing before publishing

---

## Application Configuration

### Step 1: Login to Admin Panel

1. Navigate to: `http://localhost/admin/login.html`
2. Login credentials:
   - Username: `admin`
   - Password: `Admin@123`

### Step 2: Configure Google Drive Settings

1. Go to **Settings** > **Backup Settings** (or navigate to `/admin/backup-settings.html`)

2. Enter Google Drive Credentials:
   ```
   Client ID: [Paste your Client ID]
   Client Secret: [Paste your Client Secret]
   Folder Name: DICOM_Viewer_Backups
   ```

3. Click **"Save Configuration"**

### Step 3: Authenticate with Google

1. Click **"Authenticate with Google Drive"** button
2. You'll be redirected to Google OAuth page
3. Select your Google account
4. Grant permissions:
   - ✓ See, edit, create, and delete only the specific Google Drive files you use with this app
5. Click **"Allow"**
6. You'll be redirected back to admin panel with success message

### Step 4: Configure Backup Schedule

```
Backup Schedule:
├─ Schedule Type: Daily / Weekly / Monthly
├─ Backup Time: 02:00 (2:00 AM)
└─ Retention Days: 30

What to Backup:
├─ ✓ Database
├─ ✓ PHP Files
├─ ✓ JavaScript Files
└─ ✓ Config Files
```

Click **"Save Configuration"**

### Step 5: Test Connection

1. Click **"Test Connection"** button
2. Expected result:
   ```json
   {
     "success": true,
     "message": "Connection successful",
     "authenticated": true
   }
   ```

---

## Automated Backup Setup

### Windows Task Scheduler Setup

#### Method 1: Using Batch File (Recommended)

1. Open **Task Scheduler** (`Win + R` → type `taskschd.msc`)

2. Click **"Create Basic Task"**

3. Configure Task:
   ```
   Name: DICOM Viewer Daily Backup
   Description: Automated backup of Hospital DICOM Viewer to Google Drive
   ```

4. Trigger:
   ```
   ○ Daily
   Start: Today
   Time: 02:00:00 (2:00 AM)
   Recur every: 1 days
   ```

5. Action:
   ```
   ○ Start a program
   Program/script: C:\xampp\htdocs\papa\dicom_again\claude\scripts\run-backup-service.bat
   ```

6. Advanced Settings (click "Open Properties"):
   ```
   General tab:
   ☑ Run whether user is logged on or not
   ☑ Run with highest privileges
   ☐ Hidden

   Conditions tab:
   ☐ Start only if computer is on AC power (uncheck for laptops)
   ☑ Wake the computer to run this task

   Settings tab:
   ☑ Allow task to be run on demand
   ☑ Run task as soon as possible after scheduled start is missed
   ☐ Stop task if it runs longer than: 3 days (or set to 2 hours)
   ```

7. Click **"OK"** and enter Windows credentials if prompted

#### Method 2: Using PowerShell (Alternative)

```powershell
# Run as Administrator
$action = New-ScheduledTaskAction -Execute "C:\xampp\php\php.exe" -Argument "C:\xampp\htdocs\papa\dicom_again\claude\scripts\backup-service.php"
$trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -WakeToRun

Register-ScheduledTask -TaskName "DICOM Viewer Daily Backup" -Action $action -Trigger $trigger -Principal $principal -Settings $settings
```

### Linux/Mac Cron Setup

1. Edit crontab:
```bash
crontab -e
```

2. Add entry (runs daily at 2:00 AM):
```bash
0 2 * * * /usr/bin/php /path/to/scripts/backup-service.php >> /path/to/logs/backup-cron.log 2>&1
```

3. Save and exit

4. Verify cron job:
```bash
crontab -l
```

---

## Testing

### Test 1: Manual Backup

#### Via Admin Panel:
1. Go to Backup Settings
2. Click **"Backup Now"**
3. Wait for completion (may take 1-5 minutes depending on data size)
4. Check results in backup list

#### Via API:
```bash
curl -X POST http://localhost/api/backup/backup-now.php \
  -H "Cookie: DICOM_VIEWER_SESSION=your_session_id"
```

Expected response:
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "backup_name": "dicom_viewer_backup_2025-11-22_14-30-00",
    "size_bytes": 15728640,
    "size_formatted": "15 MB",
    "gdrive_file_id": "1abc123xyz789"
  }
}
```

### Test 2: List Backups

```bash
curl -X GET http://localhost/api/backup/list-backups.php \
  -H "Cookie: DICOM_VIEWER_SESSION=your_session_id"
```

### Test 3: Run Backup Service Manually

```bash
cd c:\xampp\htdocs\papa\dicom_again\claude\scripts
php backup-service.php
```

Expected output:
```
=== Backup Service Started ===
PHP Version: 8.2.x
Database connection established
Backup configuration loaded
Starting scheduled backup creation...
Backup created successfully!
Backup Name: dicom_viewer_backup_2025-11-22_02-00-00
Backup Size: 15 MB
Duration: 12.5 seconds
Cleanup completed
Backups deleted: 2
=== Backup Service Completed Successfully ===
```

### Test 4: Verify in Google Drive

1. Go to [Google Drive](https://drive.google.com)
2. Find folder: `DICOM_Viewer_Backups`
3. Verify backup ZIP file exists
4. Check file size matches

---

## API Usage

### Complete API Reference

See detailed documentation: `/api/backup/README.md`

#### Quick Examples:

**Get Backup Status:**
```javascript
fetch('/api/backup/status.php', { credentials: 'include' })
  .then(r => r.json())
  .then(data => console.log(data));
```

**Create Backup:**
```javascript
fetch('/api/backup/backup-now.php', {
  method: 'POST',
  credentials: 'include'
})
  .then(r => r.json())
  .then(data => console.log(data));
```

**Restore Backup:**
```javascript
fetch('/api/backup/restore.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({ backup_id: 123 })
})
  .then(r => r.json())
  .then(data => console.log(data));
```

---

## Troubleshooting

### Issue: "Google API client library not found"

**Solution:**
```bash
composer require google/apiclient:^2.15
composer dump-autoload
```

### Issue: "Failed to create backup directory"

**Solution:**
```bash
# Windows
mkdir c:\xampp\htdocs\papa\dicom_again\claude\backups\temp
icacls "c:\xampp\htdocs\papa\dicom_again\claude\backups" /grant Users:F

# Linux/Mac
mkdir -p /path/to/backups/temp
chmod 755 /path/to/backups/temp
chown www-data:www-data /path/to/backups/temp
```

### Issue: "mysqldump command not found"

**Solution:** System will automatically use PHP-based backup. No action needed.

To add mysqldump to PATH (optional):
```bash
# Windows
set PATH=%PATH%;C:\xampp\mysql\bin

# Linux/Mac
export PATH=$PATH:/usr/local/mysql/bin
```

### Issue: "Access Token Expired"

**Solution:** The system automatically refreshes tokens. If it fails:
1. Go to Backup Settings
2. Click "Re-authenticate with Google Drive"
3. Complete OAuth flow again

### Issue: "Insufficient storage quota"

**Solution:**
- Check Google Drive storage quota
- Clean up old backups
- Reduce retention period
- Exclude JavaScript files from backup

### Issue: "Scheduled backup not running"

**Solution:**
1. Check Task Scheduler is enabled:
   ```bash
   # Windows
   Get-ScheduledTask -TaskName "DICOM Viewer Daily Backup"
   ```

2. Check task history in Task Scheduler

3. Manually run task to test

4. Review logs:
   ```
   c:\xampp\htdocs\papa\dicom_again\claude\logs\backup-service.log
   ```

### Issue: "Restore Failed"

**Solution:**
1. Ensure database connection is active
2. Check user has database restoration privileges
3. Review error in `/logs/gdrive-backup.log`
4. Try restoring manually:
   ```bash
   mysql -u root -p dicom_viewer_v2_production < backup.sql
   ```

---

## File Locations

### Core Files:
```
/includes/classes/GoogleDriveBackup.php    # Main backup class
/api/backup/*.php                           # API endpoints
/scripts/backup-service.php                 # Automated service
/scripts/run-backup-service.bat             # Windows launcher
/scripts/test-backup.php                    # Test script
```

### Directories:
```
/backups/temp/                              # Temporary backup files
/logs/                                      # Log files
  ├─ gdrive-backup.log                      # Backup operations log
  ├─ backup-service.log                     # Service execution log
  └─ backup.log                             # General backup log
```

### Database Tables:
```
gdrive_backup_config                        # Configuration
backup_history                              # Backup history with metadata
audit_logs                                  # Audit trail
```

---

## Security Best Practices

1. **HTTPS in Production**: Always use HTTPS for OAuth redirects
2. **Secure Credentials**: Store Client Secret securely
3. **Admin Only**: All backup APIs require admin role
4. **Audit Logging**: All operations logged to audit_logs table
5. **Token Encryption**: Consider encrypting refresh_token in database
6. **Regular Testing**: Test restore process regularly
7. **Offsite Backup**: Google Drive provides offsite redundancy

---

## Support & Maintenance

### Log Files to Check:
- `/logs/gdrive-backup.log` - Backup class operations
- `/logs/backup-service.log` - Scheduled service runs
- `/logs/backup.log` - API endpoint operations
- `/logs/app.log` - General application logs

### Monitoring Checklist:
- [ ] Verify scheduled backups run daily
- [ ] Check Google Drive storage quota monthly
- [ ] Test restore process quarterly
- [ ] Review audit logs for unauthorized access
- [ ] Update Google API credentials before expiry

---

## Version Information

- **System**: Hospital DICOM Viewer Pro v2.0
- **Component**: Google Drive Backup System
- **Created**: November 2025
- **PHP Version**: 8.2+
- **Google API Client**: v2.15+

---

## Quick Start Checklist

- [ ] Install Google API library (`composer require google/apiclient`)
- [ ] Create Google Cloud project
- [ ] Enable Google Drive API
- [ ] Create OAuth 2.0 credentials
- [ ] Configure credentials in admin panel
- [ ] Authenticate with Google
- [ ] Test connection
- [ ] Create manual backup test
- [ ] Setup Windows Task Scheduler
- [ ] Verify scheduled backup runs
- [ ] Test restore process
- [ ] Configure monitoring/alerts

---

**System is ready for production use once all checklist items are complete!**
