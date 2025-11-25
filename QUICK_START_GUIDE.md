# Hospital DICOM Viewer Pro v2.0 - Quick Start Guide

## 🚀 Get Running in 15 Minutes!

**Status:** ✅ All errors fixed, system ready to deploy

---

## Step 1: Database Setup (5 minutes)

### 1.1 Create Database

1. Open phpMyAdmin: **http://localhost/phpmyadmin**
2. Click "New" in left sidebar
3. Database name: `dicom_viewer_v2_production`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"

### 1.2 Import Schema

1. Select `dicom_viewer_v2_production` database
2. Click "Import" tab
3. Choose file: `C:\xampp\htdocs\papa\dicom_again\claude\setup\schema_v2_production.sql`
4. Click "Go"
5. Wait for "Import has been successfully finished" message

### 1.3 Verify Installation

Run this SQL query:
```sql
SELECT COUNT(*) as table_count FROM information_schema.tables
WHERE table_schema = 'dicom_viewer_v2_production';
```

**Expected result:** 18 tables

---

## Step 2: Install PHP Dependencies (2 minutes)

Open Command Prompt **as Administrator**:

```batch
cd C:\xampp\htdocs\papa\dicom_again\claude
composer install
```

**What this installs:**
- google/apiclient (for Google Drive backup)
- vlucas/phpdotenv (for environment management)

**If composer not installed:**
1. Download: https://getcomposer.org/download/
2. Install Composer
3. Restart Command Prompt
4. Run command above

---

## Step 3: Configure Environment (2 minutes)

### 3.1 Verify Configuration

File `config/.env` should already exist with these settings:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=dicom_viewer_v2_production

ORTHANC_URL=http://localhost:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc
```

### 3.2 Update if Needed

If your MySQL password is different from `root`, edit `config/.env`:
```env
DB_PASSWORD=your_actual_password
```

---

## Step 4: Access the Application (1 minute)

### 4.1 Open in Browser

**URL:** http://localhost/papa/dicom_again/claude/

or

**URL:** http://localhost/papa/dicom_again/claude/login.php

### 4.2 Login with Default Credentials

**Username:** `admin`
**Password:** `Admin@123`

**Other Test Accounts:**
- **Radiologist:** `radiologist` / `Radio@123`
- **Technician:** `technician` / `Tech@123`

⚠️ **IMPORTANT:** Change these passwords immediately after first login!

---

## Step 5: Verify Everything Works (5 minutes)

### 5.1 Check Dashboard

After login, you should see:
- ✅ System status
- ✅ Environment info
- ✅ Database connection status
- ✅ Orthanc status (may show disconnected if Orthanc not running yet)

### 5.2 Try the Viewer

1. Click "Open DICOM Viewer" button
2. You should see the viewer interface
3. If no studies available, that's normal (upload DICOM files later)

---

## What to Configure Next (Optional - Do Later)

### Hospital Data Import
1. Login as admin
2. Navigate to: **Admin > Hospital Data Import**
3. Set your hospital DICOM directory path
4. Click "Scan Directory"
5. Click "Start Import"

### FTP Sync to GoDaddy
1. Navigate to: **Admin > Sync Configuration**
2. Fill in FTP details:
   - FTP Host: `ftp.yourdomain.com`
   - Username: Your FTP username
   - Password: Your FTP password
   - Path: `/public_html/dicom_viewer/`
3. Click "Test Connection"
4. If successful, enable "Auto-sync"

### Google Drive Backup
1. Navigate to: **Admin > Backup Configuration**
2. Enter Google API credentials (see setup guide)
3. Complete OAuth authorization
4. Enable automated backups

---

## Install NSSM Services (Optional - For Production)

**Only needed for production deployment with automated sync/backup.**

### Prerequisites
1. Download NSSM: https://nssm.cc/download
2. Extract `nssm.exe` to: `C:\xampp\htdocs\papa\dicom_again\claude\scripts\`

### Installation

Run **as Administrator**:
```batch
cd C:\xampp\htdocs\papa\dicom_again\claude\scripts
setup-nssm-services.bat
```

This installs 3 Windows services:
- **DicomViewer_Data_Monitor** - Monitors hospital data directory
- **DicomViewer_FTP_Sync** - Syncs to GoDaddy every 2 minutes
- **DicomViewer_GDrive_Backup** - Daily Google Drive backups

---

## Testing Checklist

### Basic Tests
- [ ] Can access login page
- [ ] Can login with admin credentials
- [ ] Dashboard loads without errors
- [ ] Can access DICOM viewer
- [ ] Can logout

### Advanced Tests (After Orthanc Setup)
- [ ] Can upload DICOM file
- [ ] Images load in viewer
- [ ] Pan/Zoom works
- [ ] Window/Level works
- [ ] Can create report
- [ ] Can save measurements

---

## Troubleshooting

### "Database connection failed"
**Solution:**
1. Check XAMPP MySQL is running
2. Verify credentials in `config/.env`
3. Ensure database `dicom_viewer_v2_production` exists

### "Page not found" errors
**Solution:**
1. Ensure XAMPP Apache is running
2. Check URL: http://localhost/papa/dicom_again/claude/
3. Verify folder exists in correct location

### "Composer not found"
**Solution:**
1. Download Composer: https://getcomposer.org/download/
2. Install globally
3. Restart Command Prompt
4. Try again

### Images not loading in viewer
**Solution:**
1. Orthanc must be running on port 8042
2. Check: http://localhost:8042
3. See "Orthanc Setup" section below

---

## Orthanc Setup (Required for DICOM Viewing)

### Quick Setup

1. **Download Orthanc:**
   - Visit: https://www.orthanc-server.com/download.php
   - Download Windows installer

2. **Install:**
   - Run installer
   - Install to: `C:\Orthanc`

3. **Configure:**
   ```batch
   copy "C:\xampp\htdocs\papa\dicom_again\claude\orthanc-config\orthanc.json" "C:\Orthanc\Configuration\orthanc.json"
   ```

4. **Start Orthanc:**
   ```batch
   C:\Orthanc\Orthanc.exe C:\Orthanc\Configuration\orthanc.json
   ```

5. **Verify:**
   - Open: http://localhost:8042
   - Login: `orthanc` / `orthanc`
   - Should see Orthanc Explorer

### Install as Windows Service

```batch
sc create OrthancService binPath= "C:\Orthanc\Orthanc.exe C:\Orthanc\Configuration\orthanc.json" start= auto
sc start OrthancService
```

---

## Configuration Summary

### What's Already Configured ✅
- ✅ Database schema
- ✅ Environment variables (.env)
- ✅ Default users with passwords
- ✅ All API endpoints
- ✅ Frontend UI with path fixes
- ✅ Error handling and logging

### What Needs Configuration (Optional)
- ⏳ Orthanc server (for DICOM viewing)
- ⏳ Hospital data path (if you have existing DICOM files)
- ⏳ FTP credentials (for GoDaddy sync)
- ⏳ Google Drive API (for backups)
- ⏳ NSSM services (for automated operations)

---

## File Locations Reference

### Important Files
```
C:\xampp\htdocs\papa\dicom_again\claude\
├── config\.env                     # Environment configuration
├── setup\schema_v2_production.sql  # Database schema
├── setup\DEFAULT_CREDENTIALS.md    # Login info
├── index.php                       # Main viewer
├── login.php                       # Login page
├── dashboard.php                   # Dashboard
└── README.md                       # Full documentation
```

### Log Files
```
C:\xampp\htdocs\papa\dicom_again\claude\logs\
├── app.log              # Application logs
├── auth.log             # Login/logout logs
├── sync-service.log     # FTP sync logs
├── backup-service.log   # Backup logs
└── monitor-service.log  # Data import logs
```

### Configuration
```
C:\xampp\htdocs\papa\dicom_again\claude\
├── config\.env                  # Main config
├── orthanc-config\orthanc.json  # Orthanc config
├── .htaccess                    # Apache config
└── composer.json                # PHP dependencies
```

---

## Default Ports

- **Apache:** 80 (XAMPP)
- **MySQL:** 3306 (XAMPP)
- **Orthanc HTTP:** 8042
- **Orthanc DICOM:** 4242

**Make sure these ports are not blocked by firewall.**

---

## System Requirements Met ✅

- ✅ Windows 10/11
- ✅ XAMPP with PHP 8.2, MySQL 8.0
- ✅ 8 GB RAM (16 GB recommended)
- ✅ 100 GB storage minimum
- ✅ Internet connection (for backups/sync)

---

## Next Steps After Basic Setup

1. **Read Full Documentation:**
   - `README.md` - Complete overview
   - `PRODUCTION_DEPLOYMENT_GUIDE.md` - Production setup
   - `TESTING_CHECKLIST.md` - 128 comprehensive tests

2. **Setup Orthanc:**
   - Install Orthanc server
   - Configure DICOMweb plugin
   - Test with sample DICOM files

3. **Configure MRI/CT Machines:**
   - Set DICOM destination to Orthanc
   - AE Title: `HOSPITAL_ORTHANC`
   - IP: Your PC IP address
   - Port: 4242

4. **Enable Automated Features:**
   - Hospital data monitoring
   - FTP sync to GoDaddy
   - Google Drive backups

5. **Train Hospital Staff:**
   - Radiologists: Viewer and reporting
   - Technicians: Upload and monitoring
   - Admins: System configuration

---

## Support & Documentation

### Documentation Files
- `README.md` - Project overview
- `PRODUCTION_DEPLOYMENT_GUIDE.md` - Complete deployment
- `TESTING_CHECKLIST.md` - 128 tests
- `ORIGINAL_SYSTEM_ANALYSIS.md` - Issues fixed
- `setup/DEFAULT_CREDENTIALS.md` - Login credentials

### Check Logs
```batch
# View application logs
type C:\xampp\htdocs\papa\dicom_again\claude\logs\app.log

# View authentication logs
type C:\xampp\htdocs\papa\dicom_again\claude\logs\auth.log
```

### Common Commands
```batch
# Start XAMPP
C:\xampp\xampp-control.exe

# Check database
mysql -u root -p -e "SHOW DATABASES;"

# Check PHP version
php -v

# Test PHP file syntax
php -l index.php
```

---

## Success Indicators

You'll know everything is working when:

1. ✅ Login page loads without errors
2. ✅ Can login with admin credentials
3. ✅ Dashboard shows system status
4. ✅ DICOM viewer interface loads
5. ✅ No errors in browser console (F12)
6. ✅ No errors in `logs/app.log`

---

## Quick Reference: URLs

- **Login:** http://localhost/papa/dicom_again/claude/login.php
- **Dashboard:** http://localhost/papa/dicom_again/claude/dashboard.php
- **Viewer:** http://localhost/papa/dicom_again/claude/index.php
- **phpMyAdmin:** http://localhost/phpmyadmin
- **Orthanc:** http://localhost:8042

---

## Congratulations! 🎉

Your Hospital DICOM Viewer Pro v2.0 is now set up and ready to use!

**What's Working:**
- ✅ Login system
- ✅ Dashboard
- ✅ DICOM viewer UI
- ✅ Database with 18 tables
- ✅ All backend APIs
- ✅ Error-free codebase

**Configure Later:**
- ⏳ Orthanc integration (for actual DICOM viewing)
- ⏳ Hospital data import
- ⏳ FTP sync
- ⏳ Google Drive backup

---

**Need Help?** Check the full `PRODUCTION_DEPLOYMENT_GUIDE.md` for detailed instructions!
