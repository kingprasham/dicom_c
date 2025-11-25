# Hospital DICOM Viewer Pro v2.0 - Production Deployment Guide

## 🎯 Complete Setup Guide for Hospital Deployment

This guide will take you from zero to a fully functional, production-ready DICOM viewing system capable of handling 1000+ images daily.

---

## 📋 Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Deployment Checklist](#pre-deployment-checklist)
3. [Step 1: XAMPP Installation](#step-1-xampp-installation)
4. [Step 2: Orthanc Installation](#step-2-orthanc-installation)
5. [Step 3: Database Setup](#step-3-database-setup)
6. [Step 4: Application Installation](#step-4-application-installation)
7. [Step 5: NSSM Services Setup](#step-5-nssm-services-setup)
8. [Step 6: GoDaddy Sync Configuration](#step-6-godaddy-sync-configuration)
9. [Step 7: Google Drive Backup Setup](#step-7-google-drive-backup-setup)
10. [Step 8: Testing](#step-8-testing)
11. [Step 9: Production Deployment](#step-9-production-deployment)
12. [Troubleshooting](#troubleshooting)
13. [Maintenance](#maintenance)

---

## System Requirements

### Hospital PC (Local Server)

**Minimum:**
- **OS:** Windows 10/11 or Windows Server 2016+
- **CPU:** Intel Core i5 or equivalent
- **RAM:** 8 GB
- **Storage:** 500 GB SSD (for DICOM storage)
- **Network:** 100 Mbps

**Recommended:**
- **OS:** Windows 11 Pro or Windows Server 2022
- **CPU:** Intel Core i7 or AMD Ryzen 7
- **RAM:** 16 GB or more
- **Storage:** 1 TB NVMe SSD
- **Network:** Gigabit Ethernet

### Software Requirements

- XAMPP 8.2+ (PHP 8.2, MySQL 8.0, Apache 2.4)
- Orthanc 1.11+ with DICOMweb plugin
- NSSM (Non-Sucking Service Manager)
- Web browser (Chrome/Edge recommended)
- Composer (PHP dependency manager)

### Network Requirements

- Static IP address for hospital PC
- Port 8042 open for Orthanc (localhost only)
- Port 4242 open for DICOM C-STORE (from MRI/CT machines)
- Internet access for GoDaddy sync and Google Drive backup
- FTP access to GoDaddy server

---

## Pre-Deployment Checklist

**Before starting, ensure you have:**

- [ ] Administrator access to hospital PC
- [ ] MySQL root password
- [ ] GoDaddy FTP credentials
- [ ] Google Cloud Console account
- [ ] Domain name (if using GoDaddy)
- [ ] MRI/CT machine network details (IP, AE Title)

---

## Step 1: XAMPP Installation

### 1.1 Download XAMPP

1. Visit: https://www.apachefriends.org/
2. Download XAMPP for Windows (PHP 8.2+)
3. Run installer as Administrator

### 1.2 Installation Settings

- Install location: `C:\xampp`
- Components: ✅ Apache, ✅ MySQL, ✅ PHP, ✅ phpMyAdmin
- Skip: Mercury Mail, FileZilla, Perl, Tomcat

### 1.3 Configure PHP

Edit `C:\xampp\php\php.ini`:

```ini
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
post_max_size = 500M
upload_max_filesize = 500M
```

### 1.4 Start Services

1. Open XAMPP Control Panel
2. Start Apache
3. Start MySQL
4. Configure as Windows services (auto-start)

---

## Step 2: Orthanc Installation

### 2.1 Download Orthanc

1. Visit: https://www.orthanc-server.com/download.php
2. Download:
   - Orthanc for Windows (latest stable)
   - DICOMweb plugin

### 2.2 Installation

1. Extract to `C:\Orthanc`
2. Create directories:
   ```
   C:\Orthanc\
   ├── OrthancStorage\
   ├── OrthancDatabase\
   ├── Plugins\
   └── Configuration\
   ```

3. Copy DICOMweb plugin DLL to `C:\Orthanc\Plugins\`

### 2.3 Configuration

1. Copy provided configuration:
   ```batch
   copy "C:\xampp\htdocs\papa\dicom_again\claude\orthanc-config\orthanc.json" "C:\Orthanc\Configuration\orthanc.json"
   ```

2. Edit `C:\Orthanc\Configuration\orthanc.json`:
   - Update storage paths if different
   - Change default passwords
   - Configure DICOM AE Title

### 2.4 Test Orthanc

1. Start Orthanc:
   ```batch
   C:\Orthanc\Orthanc.exe C:\Orthanc\Configuration\orthanc.json
   ```

2. Open browser: http://localhost:8042
3. Login: orthanc / orthanc
4. Verify DICOMweb plugin: http://localhost:8042/dicom-web/

### 2.5 Install as Windows Service

```batch
sc create OrthancService binPath= "C:\Orthanc\Orthanc.exe C:\Orthanc\Configuration\orthanc.json" start= auto
sc start OrthancService
```

---

## Step 3: Database Setup

### 3.1 Access phpMyAdmin

1. Open: http://localhost/phpmyadmin
2. Login: root / (empty password by default)

### 3.2 Create Database

1. Click "New" in left sidebar
2. Database name: `dicom_viewer_v2_production`
3. Collation: `utf8mb4_unicode_ci`
4. Click "Create"

### 3.3 Import Schema

1. Select `dicom_viewer_v2_production` database
2. Click "Import" tab
3. Choose file: `C:\xampp\htdocs\papa\dicom_again\claude\setup\schema_v2_production.sql`
4. Click "Go"
5. Verify: Should see "Import has been successfully finished"

### 3.4 Verify Tables

Check that all tables were created:

```sql
SHOW TABLES;
```

Should show 18 tables including:
- users
- sessions
- medical_reports
- measurements
- sync_configuration
- backup_history
- etc.

### 3.5 Verify Default Users

```sql
SELECT username, role, email FROM users;
```

Should show 3 default users:
- admin (role: admin)
- radiologist (role: radiologist)
- technician (role: technician)

**Default passwords** (see `setup/DEFAULT_CREDENTIALS.md`):
- admin: Admin@123
- radiologist: Radio@123
- technician: Tech@123

⚠️ **CHANGE THESE PASSWORDS IMMEDIATELY AFTER FIRST LOGIN!**

---

## Step 4: Application Installation

### 4.1 Copy Application Files

Application is already at: `C:\xampp\htdocs\papa\dicom_again\claude\`

If deploying to different location:
```batch
xcopy "C:\xampp\htdocs\papa\dicom_again\claude" "C:\xampp\htdocs\dicom_viewer" /E /I /H
```

### 4.2 Install PHP Dependencies

```batch
cd C:\xampp\htdocs\papa\dicom_again\claude
composer install
```

This installs:
- google/apiclient (Google Drive API)
- vlucas/phpdotenv (Environment management)

### 4.3 Configure Environment

Edit `config\.env`:

```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=dicom_viewer_v2_production

# Orthanc
ORTHANC_URL=http://localhost:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc
ORTHANC_STORAGE_PATH=C:\Orthanc\OrthancStorage

# Application
APP_ENV=production
APP_URL=http://localhost
APP_NAME=Hospital DICOM Viewer Pro v2.0

# FTP (configure in Step 6)
FTP_HOST=
FTP_USERNAME=
FTP_PASSWORD=
FTP_PATH=/public_html/dicom_viewer/

# Google Drive (configure in Step 7)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

### 4.4 Set Permissions

```batch
REM Create logs directory
mkdir logs

REM Set writable permissions
icacls logs /grant "Everyone:(OI)(CI)F"
```

### 4.5 Access Application

Open browser: http://localhost/papa/dicom_again/claude/

Should see login page.

**Login with:**
- Username: admin
- Password: Admin@123

---

## Step 5: NSSM Services Setup

NSSM (Non-Sucking Service Manager) runs PHP scripts as Windows services.

### 5.1 Download NSSM

1. Visit: https://nssm.cc/download
2. Download latest version
3. Extract `win64\nssm.exe` to: `C:\xampp\htdocs\papa\dicom_again\claude\scripts\`

### 5.2 Run Setup Script

**As Administrator:**

```batch
cd C:\xampp\htdocs\papa\dicom_again\claude\scripts
setup-nssm-services.bat
```

This installs 3 Windows services:
1. **DicomViewer_Data_Monitor** - Monitors hospital data directory
2. **DicomViewer_FTP_Sync** - Syncs to GoDaddy via FTP
3. **DicomViewer_GDrive_Backup** - Daily Google Drive backups

### 5.3 Verify Services

Open Services (`services.msc`):

- Look for "DICOM Data Monitor"
- Look for "DICOM FTP Sync"
- Look for "DICOM GDrive Backup"

All should be:
- **Status:** Running
- **Startup Type:** Automatic

### 5.4 Check Service Logs

```batch
type C:\xampp\htdocs\papa\dicom_again\claude\logs\monitor-service.log
type C:\xampp\htdocs\papa\dicom_again\claude\logs\sync-service.log
type C:\xampp\htdocs\papa\dicom_again\claude\logs\backup-service.log
```

---

## Step 6: GoDaddy Sync Configuration

This enables automatic syncing of DICOM files to your GoDaddy hosted website.

### 6.1 Prepare GoDaddy

**In GoDaddy cPanel:**

1. Create MySQL database
2. Create FTP account
3. Note credentials

### 6.2 Configure Sync

**In application (as admin):**

1. Navigate to: Admin > Sync Configuration
2. Fill in:
   - **Orthanc Storage Path:** `C:\Orthanc\OrthancStorage`
   - **FTP Host:** `ftp.yourdomain.com`
   - **FTP Username:** Your FTP username
   - **FTP Password:** Your FTP password
   - **FTP Port:** 21
   - **FTP Path:** `/public_html/dicom_viewer/`
   - **Sync Enabled:** ✅ Yes
   - **Sync Interval:** 120 (2 minutes)
3. Click "Test Connection"
4. If successful, click "Save Configuration"

### 6.3 Test Manual Sync

1. Click "Sync Now"
2. Wait for completion
3. Check logs: `logs\sync-service.log`
4. Verify files uploaded to GoDaddy via FTP client

### 6.4 Upload Application to GoDaddy

**Via FTP:**

Upload entire application folder to:
```
/public_html/dicom_viewer/
```

**Update GoDaddy `.env`:**

Edit `/public_html/dicom_viewer/config/.env`:
- Update database credentials (GoDaddy MySQL)
- Update `ORTHANC_URL` to hospital public IP (if accessible)
- Set `APP_ENV=production`

---

## Step 7: Google Drive Backup Setup

### 7.1 Create Google Cloud Project

1. Visit: https://console.cloud.google.com
2. Create new project: "DICOM Viewer Backup"
3. Enable Google Drive API
4. Create OAuth 2.0 credentials:
   - Application type: Web application
   - Redirect URI: `http://localhost/papa/dicom_again/claude/api/backup/oauth-callback.php`
5. Note **Client ID** and **Client Secret**

### 7.2 Configure Backup

**In application (as admin):**

1. Navigate to: Admin > Backup Configuration
2. Fill in:
   - **Client ID:** (from Google Cloud Console)
   - **Client Secret:** (from Google Cloud Console)
   - **Folder Name:** DICOM_Viewer_Backups
   - **Backup Enabled:** ✅ Yes
   - **Backup Schedule:** Daily
   - **Backup Time:** 02:00
   - **Retention:** 30 days
   - **Include Database:** ✅ Yes
   - **Include PHP Files:** ✅ Yes
   - **Include JS Files:** ✅ Yes
   - **Include Config:** ✅ Yes
3. Click "Authorize with Google Drive"
4. Complete OAuth flow
5. Click "Save Configuration"

### 7.3 Test Manual Backup

1. Click "Backup Now"
2. Wait for completion
3. Check Google Drive for backup file
4. Verify backup history in application

---

## Step 8: Testing

### 8.1 Test Authentication

- [ ] Login with admin account
- [ ] Login with radiologist account
- [ ] Logout
- [ ] Verify session timeout

### 8.2 Test DICOM Upload

1. Upload test DICOM file
2. Verify appears in patient list
3. Check Orthanc: http://localhost:8042
4. Verify file in `C:\Orthanc\OrthancStorage\`

### 8.3 Test Viewer

- [ ] Open study
- [ ] Images load correctly
- [ ] Pan/Zoom works
- [ ] Window/Level adjustments work
- [ ] MPR reconstruction works
- [ ] Measurements work
- [ ] Annotations save

### 8.4 Test Reporting

- [ ] Create new report
- [ ] Save report
- [ ] Load report
- [ ] Export to PDF

### 8.5 Test Sync

- [ ] Verify sync service running
- [ ] Upload new DICOM
- [ ] Wait 2 minutes
- [ ] Check GoDaddy FTP for file

### 8.6 Test Backup

- [ ] Trigger manual backup
- [ ] Verify backup in Google Drive
- [ ] Download backup
- [ ] Test restore (on test database)

---

## Step 9: Production Deployment

### 9.1 Security Hardening

**Change Default Passwords:**
```sql
UPDATE users SET password_hash = ? WHERE username = 'admin';
```

**Configure Firewall:**
- Block port 8042 from internet (Orthanc - localhost only)
- Allow port 4242 from MRI/CT machines only
- Allow port 80/443 for web access

**SSL Certificate (Optional but Recommended):**
- Get SSL certificate (Let's Encrypt)
- Configure XAMPP for HTTPS

### 9.2 Configure MRI/CT Machines

**DICOM Settings:**
- **Destination AE Title:** HOSPITAL_ORTHANC
- **Destination IP:** [Hospital PC IP]
- **Destination Port:** 4242

**Test Send:**
Send test image from MRI/CT to verify reception.

### 9.3 Monitor Performance

**Check Logs Daily:**
```batch
type logs\app.log
type logs\sync-service.log
type logs\backup-service.log
type logs\monitor-service.log
```

**Database Maintenance:**
```sql
-- Run weekly
CALL sp_cleanup_expired_sessions();
CALL sp_cleanup_old_audit_logs();
```

### 9.4 Create Backup Schedule

**Windows Task Scheduler:**
- Daily: Database backup at 2 AM
- Weekly: Full system backup
- Monthly: Archive old backups

---

## Troubleshooting

### Images Not Loading

**Check:**
1. Orthanc is running: http://localhost:8042
2. DICOMweb plugin enabled
3. CORS headers configured
4. Browser console for errors

**Solution:**
```batch
REM Restart Orthanc service
net stop OrthancService
net start OrthancService
```

### Sync Not Working

**Check:**
1. FTP credentials correct
2. Sync service running: `sc query DicomViewer_FTP_Sync`
3. Log file: `logs\sync-service.log`

**Solution:**
```batch
REM Restart sync service
net stop DicomViewer_FTP_Sync
net start DicomViewer_FTP_Sync
```

### Database Connection Failed

**Check:**
1. MySQL is running
2. Credentials in `config\.env` are correct
3. Database exists

**Solution:**
```batch
REM Restart MySQL
net stop MySQL
net start MySQL
```

### Backup Failing

**Check:**
1. Google OAuth tokens valid
2. Internet connectivity
3. Google Drive storage available
4. Log file: `logs\backup-service.log`

**Solution:**
Re-authorize Google Drive in Admin > Backup Configuration.

---

## Maintenance

### Daily Tasks

- Check service status
- Review error logs
- Verify backup completed

### Weekly Tasks

- Review audit logs
- Clean up old sessions
- Check disk space

### Monthly Tasks

- Review user accounts
- Archive old data
- Update software
- Test disaster recovery

---

## Support & Documentation

**Documentation Files:**
- `README.md` - Overview
- `ORIGINAL_SYSTEM_ANALYSIS.md` - Issues fixed
- `BUILD_PROGRESS.md` - Development status
- `setup/DEFAULT_CREDENTIALS.md` - Login credentials

**Log Files:**
- `logs/app.log` - Application logs
- `logs/auth.log` - Authentication logs
- `logs/audit.log` - Audit trail
- `logs/sync-service.log` - Sync operations
- `logs/backup-service.log` - Backup operations

**Database Documentation:**
- `setup/schema_v2_production.sql` - Complete database schema

---

## Success Criteria

✅ **Deployment is successful when:**

1. Login page accessible
2. DICOM files upload successfully
3. Images display in viewer
4. All tools functional (Pan, Zoom, W/L, MPR, Measurements)
5. Reports save and load
6. Sync service running and uploading to GoDaddy
7. Backup service creating daily backups
8. MRI/CT machines sending to Orthanc
9. No errors in logs
10. All users can access based on roles

---

## Production Checklist

- [ ] XAMPP installed and configured
- [ ] Orthanc installed with DICOMweb plugin
- [ ] Database created and schema imported
- [ ] Application files deployed
- [ ] Composer dependencies installed
- [ ] Environment configured (.env)
- [ ] NSSM services installed and running
- [ ] GoDaddy sync configured and tested
- [ ] Google Drive backup configured and tested
- [ ] Default passwords changed
- [ ] Firewall configured
- [ ] MRI/CT machines configured
- [ ] Test uploads successful
- [ ] All features tested
- [ ] Documentation reviewed
- [ ] Training completed

---

**Congratulations! Your Hospital DICOM Viewer Pro v2.0 is now production-ready!**

For support, check logs first, then review troubleshooting section.
