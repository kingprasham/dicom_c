# Configuration Checklist - Hospital DICOM Viewer Pro v2.0

## 📋 Everything You Need to Configure

This document lists **ALL** configuration items needed to make the system fully operational.

---

## ✅ Already Configured (No Action Needed)

These are already set up and working:

- ✅ **Database schema** - 18 tables created
- ✅ **Default users** - admin, radiologist, technician
- ✅ **Session configuration** - 8-hour sessions
- ✅ **API endpoints** - 50+ endpoints ready
- ✅ **Frontend UI** - Viewer with all tools
- ✅ **Path resolution** - Works on any deployment
- ✅ **Security** - Prepared statements, bcrypt hashing
- ✅ **Logging** - Comprehensive logging system

---

## 🔧 Configurations Needed

### 1. Basic Setup (REQUIRED)

#### 1.1 Database Connection

**File:** `config/.env`

**Current Settings:**
```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=dicom_viewer_v2_production
```

**Action Required:**
- ✅ If using default XAMPP, no changes needed
- ⚠️ If MySQL password is different, update `DB_PASSWORD`
- ⚠️ If using remote MySQL, update `DB_HOST`

**How to Change:**
1. Open `config/.env` in text editor
2. Update the values
3. Save file
4. Restart Apache

---

#### 1.2 Orthanc Connection (REQUIRED for DICOM viewing)

**File:** `config/.env`

**Current Settings:**
```env
ORTHANC_URL=http://localhost:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc
ORTHANC_STORAGE_PATH=C:\Orthanc\OrthancStorage
```

**Action Required:**
1. **Install Orthanc** (if not installed)
2. **Configure Orthanc** with provided config
3. **Update credentials** if you changed Orthanc defaults

**How to Install Orthanc:**
```batch
# 1. Download from: https://www.orthanc-server.com/download.php
# 2. Install to: C:\Orthanc
# 3. Copy configuration:
copy "orthanc-config\orthanc.json" "C:\Orthanc\Configuration\orthanc.json"

# 4. Start Orthanc:
C:\Orthanc\Orthanc.exe C:\Orthanc\Configuration\orthanc.json

# 5. Verify: Open http://localhost:8042
# Login: orthanc / orthanc
```

**Orthanc Configuration File:**
Location: `orthanc-config/orthanc.json`

**Key Settings in Orthanc Config:**
```json
{
  "HttpPort": 8042,
  "DicomPort": 4242,
  "RegisteredUsers": {
    "orthanc": "orthanc"
  },
  "DicomWeb": {
    "Enable": true,
    "Root": "/dicom-web/"
  }
}
```

**What to Update:**
- ⚠️ Change default password `orthanc` / `orthanc`
- ⚠️ Update `StorageDirectory` if different location
- ✅ Keep DICOMweb enabled (critical!)

---

### 2. Hospital Data Import (OPTIONAL - If you have existing DICOM files)

**Configure via Admin UI after login:**
- Navigate to: **Admin > Hospital Data Import**

**Settings:**
1. **Hospital Data Path** - Directory where existing DICOM files are stored
   - Example: `D:\Hospital\DICOM\MRI_CT_Data\`
   - Example: `\\NetworkShare\DICOM\`

2. **Monitoring Enabled** - Auto-import new files
   - ✅ Yes - Checks every 30 seconds for new files
   - ❌ No - Manual import only

3. **File Filters:**
   - ✅ Include .dcm files
   - ✅ Include files without extension (check DICM header)
   - ✅ Recursively scan subdirectories

**How to Configure:**
1. Login as admin
2. Click "Admin" in navigation
3. Click "Hospital Data Import"
4. Enter hospital DICOM directory path
5. Click "Scan Directory" to test
6. Review found files
7. Click "Start Import"
8. Enable "Continuous Monitoring" if desired

**Database Tables Used:**
- `sync_configuration` - Stores hospital_data_path
- `import_jobs` - Tracks import jobs
- `import_history` - Logs each imported file

---

### 3. FTP Sync to GoDaddy (OPTIONAL - For production deployment)

**Configure via Admin UI after login:**
- Navigate to: **Admin > Sync Configuration**

**Settings Required:**

1. **Orthanc Storage Path**
   - Default: `C:\Orthanc\OrthancStorage`
   - Where Orthanc stores DICOM files

2. **FTP Host**
   - Example: `ftp.yourdomain.com`
   - Get from GoDaddy cPanel

3. **FTP Username**
   - Your GoDaddy FTP username

4. **FTP Password**
   - Your GoDaddy FTP password
   - ⚠️ Stored encrypted in database

5. **FTP Port**
   - Default: `21`
   - Standard FTP port

6. **FTP Path**
   - Example: `/public_html/dicom_viewer/`
   - Where to upload on GoDaddy

7. **FTP Passive Mode**
   - ✅ Yes (recommended)
   - Helps with firewalls

8. **Sync Enabled**
   - ✅ Yes - Auto-sync every N minutes
   - ❌ No - Manual sync only

9. **Sync Interval**
   - Default: `120` (2 minutes)
   - How often to sync (in seconds)

**How to Configure:**
1. Login as admin
2. Click "Admin" > "Sync Configuration"
3. Fill in all FTP details
4. Click "Test Connection"
5. If successful, enable "Auto-Sync"
6. Click "Save Configuration"

**Database Tables Used:**
- `sync_configuration` - Stores FTP settings (password encrypted)
- `sync_history` - Logs each sync operation

**NSSM Service:**
Service `DicomViewer_FTP_Sync` must be running for auto-sync.

```batch
# Check service status
sc query DicomViewer_FTP_Sync

# Start service
net start DicomViewer_FTP_Sync
```

---

### 4. Google Drive Backup (OPTIONAL - For automated backups)

**Prerequisites:**
1. **Google Cloud Console Account**
2. **Google Drive API enabled**
3. **OAuth 2.0 credentials created**

**Step-by-Step Setup:**

#### 4.1 Create Google Cloud Project

1. Visit: https://console.cloud.google.com
2. Click "New Project"
3. Name: `DICOM Viewer Backup`
4. Click "Create"

#### 4.2 Enable Google Drive API

1. In your project, click "APIs & Services" > "Library"
2. Search for "Google Drive API"
3. Click "Enable"

#### 4.3 Create OAuth 2.0 Credentials

1. Click "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Application type: **Web application**
4. Name: `DICOM Viewer Backup Client`
5. **Authorized redirect URIs:**
   ```
   http://localhost/papa/dicom_again/claude/api/backup/oauth-callback.php
   ```
   (Update path if different)
6. Click "Create"
7. **Copy Client ID and Client Secret**

#### 4.4 Configure in Application

**Via Admin UI:**
- Navigate to: **Admin > Backup Configuration**

**Settings Required:**

1. **Client ID**
   - From Google Cloud Console
   - Looks like: `123456789-abcdefg.apps.googleusercontent.com`

2. **Client Secret**
   - From Google Cloud Console
   - Random string

3. **Folder Name**
   - Default: `DICOM_Viewer_Backups`
   - Folder created in Google Drive

4. **Backup Enabled**
   - ✅ Yes - Auto-backup daily
   - ❌ No - Manual backup only

5. **Backup Schedule**
   - Daily (recommended)
   - Weekly
   - Monthly

6. **Backup Time**
   - Default: `02:00` (2:00 AM)
   - When to run daily backup

7. **Retention Days**
   - Default: `30` days
   - Auto-delete backups older than this

8. **Backup Contents:**
   - ✅ Database (SQL dump)
   - ✅ PHP Files
   - ✅ JavaScript Files
   - ✅ Configuration Files

**How to Configure:**

1. Login as admin
2. Click "Admin" > "Backup Configuration"
3. Enter Client ID and Client Secret
4. Click "Authorize with Google Drive"
5. Complete OAuth flow (allow access)
6. Configure schedule and retention
7. Select what to backup
8. Click "Save Configuration"
9. Test with "Backup Now"

**Database Tables Used:**
- `gdrive_backup_config` - Stores Google credentials and settings
- `backup_history` - Logs each backup

**NSSM Service:**
Service `DicomViewer_GDrive_Backup` runs daily backups.

```batch
# Check service status
sc query DicomViewer_GDrive_Backup
```

---

### 5. NSSM Services Installation (OPTIONAL - For production)

**What NSSM Does:**
- Runs PHP scripts as Windows services
- Auto-starts on boot
- Auto-restarts on failure
- Better than Task Scheduler

**Services to Install:**

1. **DicomViewer_Data_Monitor**
   - Monitors hospital data directory
   - Imports new DICOM files automatically
   - Checks every 30 seconds

2. **DicomViewer_FTP_Sync**
   - Syncs files to GoDaddy via FTP
   - Runs every 2 minutes (configurable)
   - Requires FTP configuration

3. **DicomViewer_GDrive_Backup**
   - Creates daily Google Drive backups
   - Runs at 2:00 AM (configurable)
   - Requires Google Drive configuration

**Installation Steps:**

1. **Download NSSM:**
   - Visit: https://nssm.cc/download
   - Download latest version
   - Extract `nssm.exe` (64-bit)

2. **Copy to Scripts Folder:**
   ```batch
   copy nssm.exe C:\xampp\htdocs\papa\dicom_again\claude\scripts\
   ```

3. **Run Setup Script (as Administrator):**
   ```batch
   cd C:\xampp\htdocs\papa\dicom_again\claude\scripts
   setup-nssm-services.bat
   ```

4. **Verify Services Installed:**
   - Open Services (`services.msc`)
   - Look for 3 services starting with "DicomViewer"
   - All should be "Running" and "Automatic" startup

**Manual Service Control:**
```batch
# Start all services
net start DicomViewer_Data_Monitor
net start DicomViewer_FTP_Sync
net start DicomViewer_GDrive_Backup

# Stop all services
net stop DicomViewer_Data_Monitor
net stop DicomViewer_FTP_Sync
net stop DicomViewer_GDrive_Backup

# Check status
sc query DicomViewer_Data_Monitor
```

---

### 6. MRI/CT Machine Configuration (For receiving DICOM)

**On your MRI/CT machine:**

Configure DICOM destination:

1. **Destination AE Title:** `HOSPITAL_ORTHANC`
2. **Destination IP:** [Your Hospital PC IP Address]
3. **Destination Port:** `4242`
4. **Protocol:** DICOM C-STORE

**Example Configuration:**
```
AE Title: HOSPITAL_ORTHANC
Host: 192.168.1.100  (your PC's IP)
Port: 4242
```

**Find Your PC IP:**
```batch
ipconfig
# Look for IPv4 Address
```

**Firewall Rule:**
Allow incoming connections on port 4242:
```batch
# Run as Administrator
netsh advfirewall firewall add rule name="Orthanc DICOM" dir=in action=allow protocol=TCP localport=4242
```

**Test DICOM Send:**
1. Send test image from MRI/CT machine
2. Check Orthanc: http://localhost:8042
3. Should see study appear in Orthanc Explorer

---

### 7. Production Deployment to GoDaddy (OPTIONAL)

**Prerequisites:**
- GoDaddy hosting account
- MySQL database created in cPanel
- FTP access

**Steps:**

1. **Create Database in GoDaddy cPanel:**
   - Database name: `dicom_viewer_v2_production`
   - Create user and assign all privileges
   - Note: Database name, username, password

2. **Upload Files via FTP:**
   - Upload entire `claude/` folder to `/public_html/dicom_viewer/`
   - Or use FTP sync feature

3. **Import Database:**
   - Export from localhost: `mysqldump -u root dicom_viewer_v2_production > database.sql`
   - Import to GoDaddy via phpMyAdmin

4. **Update GoDaddy .env:**
   - Edit `/public_html/dicom_viewer/config/.env`
   - Update database credentials
   - Update `ORTHANC_URL` to hospital PC public IP (if accessible)
   - Set `APP_ENV=production`

5. **Install Composer Dependencies:**
   ```bash
   cd /home/username/public_html/dicom_viewer
   composer install
   ```

6. **Test:**
   - Visit: https://yourdomain.com/dicom_viewer/
   - Should see login page
   - Login with admin credentials

---

## ⚙️ Advanced Configuration

### Change Session Timeout

**File:** `config/.env`

```env
SESSION_LIFETIME=28800  # 8 hours in seconds
```

Change to desired value (in seconds):
- 1 hour = 3600
- 4 hours = 14400
- 12 hours = 43200

### Change Default Passwords

**After first login:**
1. Login as admin
2. Navigate to User Management
3. Change password for all default users

**Or via SQL:**
```sql
-- Generate new password hash
-- Password: NewPassword123
UPDATE users
SET password_hash = '$2y$12$...'
WHERE username = 'admin';
```

### Enable HTTPS

**File:** `.htaccess`

Uncomment these lines:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Requires SSL certificate installed on server.**

### Change Sync Interval

**Via Admin UI:**
- Admin > Sync Configuration
- Change "Sync Interval" value
- In seconds (120 = 2 minutes)

**Or via SQL:**
```sql
UPDATE sync_configuration
SET sync_interval = 300  -- 5 minutes
WHERE id = 1;
```

### Change Backup Time

**Via Admin UI:**
- Admin > Backup Configuration
- Change "Backup Time"
- Format: HH:MM (24-hour)

**Or via SQL:**
```sql
UPDATE gdrive_backup_config
SET backup_time = '03:00:00'  -- 3:00 AM
WHERE id = 1;
```

---

## 📊 Configuration Summary

### Minimum Required (To Run Locally)
1. ✅ Database connection (`config/.env`)
2. ✅ Composer dependencies installed
3. ❌ Orthanc (optional if just testing UI)

### For Full DICOM Viewing
1. ✅ Database connection
2. ✅ Composer dependencies
3. ✅ **Orthanc installed and configured**
4. ✅ DICOMweb plugin enabled in Orthanc

### For Production with Auto-Features
1. ✅ All of above
2. ✅ **Hospital data path configured**
3. ✅ **FTP sync configured**
4. ✅ **Google Drive backup configured**
5. ✅ **NSSM services installed**
6. ✅ **MRI/CT machines sending to Orthanc**

---

## ✅ Configuration Checklist

Copy this and check off as you complete:

### Basic Setup
- [ ] Database created and schema imported
- [ ] Composer dependencies installed
- [ ] Can access login page
- [ ] Can login with default credentials
- [ ] Dashboard loads without errors

### Orthanc Integration
- [ ] Orthanc installed
- [ ] Orthanc configuration copied
- [ ] Orthanc running on port 8042
- [ ] DICOMweb plugin enabled
- [ ] Can access http://localhost:8042

### Hospital Data Import (If Applicable)
- [ ] Hospital data path configured
- [ ] Directory scan successful
- [ ] Initial import completed
- [ ] Monitoring enabled
- [ ] Data Monitor service running

### FTP Sync (If Using GoDaddy)
- [ ] FTP credentials obtained
- [ ] FTP connection tested
- [ ] Manual sync successful
- [ ] Auto-sync enabled
- [ ] FTP Sync service running

### Google Drive Backup (If Using)
- [ ] Google Cloud project created
- [ ] Drive API enabled
- [ ] OAuth credentials created
- [ ] OAuth flow completed
- [ ] Test backup successful
- [ ] Backup schedule configured
- [ ] Backup service running

### NSSM Services (If Using)
- [ ] NSSM downloaded
- [ ] Setup script run as admin
- [ ] All 3 services installed
- [ ] All services running
- [ ] Services set to auto-start

### MRI/CT Integration (If Applicable)
- [ ] MRI/CT configured to send to Orthanc
- [ ] Firewall port 4242 open
- [ ] Test send successful
- [ ] Study appears in Orthanc
- [ ] Study viewable in application

---

## 🆘 Need Help?

### Documentation
- `QUICK_START_GUIDE.md` - 15-minute setup
- `PRODUCTION_DEPLOYMENT_GUIDE.md` - Full deployment
- `TESTING_CHECKLIST.md` - 128 tests
- `README.md` - Complete overview

### Logs to Check
```batch
# Application logs
type logs\app.log

# Authentication logs
type logs\auth.log

# Sync service logs
type logs\sync-service.log

# Backup service logs
type logs\backup-service.log

# Monitor service logs
type logs\monitor-service.log
```

### Common Issues
See `QUICK_START_GUIDE.md` troubleshooting section

---

**Configuration complete? Start with the Quick Start Guide!**
