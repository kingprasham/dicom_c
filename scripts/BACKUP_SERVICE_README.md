# Google Drive Backup Service - Setup Guide

## Overview

The Hospital DICOM Viewer Pro v2.0 includes an automated backup system that backs up your database and application files to Google Drive.

## Components

### 1. PHP Class: `GoogleDriveBackup.php`
Location: `/includes/classes/GoogleDriveBackup.php`

**Features:**
- Database backup using mysqldump
- PHP, JavaScript, and config files backup
- Upload to Google Drive
- Download and restore backups
- Automatic cleanup of old backups
- Transaction support for safe restores

### 2. API Endpoints
Location: `/api/backup/`

- `configure-gdrive.php` - Configure Google Drive credentials and backup settings
- `backup-now.php` - Trigger immediate manual backup
- `list-backups.php` - List all available backups
- `restore.php` - Restore from a specific backup
- `status.php` - Get current backup status and statistics
- `test-connection.php` - Test Google Drive API connection
- `oauth-callback.php` - OAuth2 callback handler
- `delete.php` - Delete a specific backup
- `cleanup-old.php` - Manually trigger cleanup of old backups

### 3. Background Service
Location: `/scripts/backup-service.php`

Automated script that runs on schedule to create backups and clean up old ones.

## Setup Instructions

### Step 1: Install Google API Client Library

```bash
cd c:\xampp\htdocs\papa\dicom_again\claude
composer require google/apiclient
```

### Step 2: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google Drive API:
   - Go to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "Enable"

### Step 3: Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Application type: "Web application"
4. Name: "Hospital DICOM Viewer Backup"
5. Authorized redirect URIs:
   ```
   http://localhost/api/backup/oauth-callback.php
   https://yourdomain.com/api/backup/oauth-callback.php
   ```
6. Save the Client ID and Client Secret

### Step 4: Configure Backup Settings

1. Login to admin panel
2. Navigate to Backup Settings page
3. Enter your Google Client ID and Client Secret
4. Click "Authenticate with Google Drive"
5. Grant permissions when redirected to Google
6. Configure backup schedule:
   - **Schedule**: Daily, Weekly, or Monthly
   - **Time**: When to run backups (e.g., 02:00 AM)
   - **Retention**: How many days to keep backups (e.g., 30 days)
7. Select what to backup:
   - Database (recommended)
   - PHP files (recommended)
   - JavaScript files (optional)
   - Config files (recommended)
8. Save configuration

### Step 5: Windows Task Scheduler Setup

1. Open Windows Task Scheduler (`taskschd.msc`)
2. Click "Create Basic Task"
3. Configure task:
   - **Name**: DICOM Viewer Daily Backup
   - **Description**: Automated backup of DICOM Viewer to Google Drive
   - **Trigger**: Daily at 2:00 AM (or your configured time)
   - **Action**: Start a program
   - **Program**: `C:\xampp\htdocs\papa\dicom_again\claude\scripts\run-backup-service.bat`
4. Advanced settings:
   - Run whether user is logged on or not
   - Run with highest privileges
   - Configure for Windows 10/Server
5. Click "Finish"

### Alternative: Manual Cron Setup (Linux/Mac)

```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 2:00 AM)
0 2 * * * /usr/bin/php /path/to/scripts/backup-service.php >> /path/to/logs/backup-cron.log 2>&1
```

## Manual Backup

To create a backup immediately:

1. Login as admin
2. Go to Backup Settings
3. Click "Backup Now" button

Or via API:

```bash
curl -X POST http://localhost/api/backup/backup-now.php \
  -H "Content-Type: application/json" \
  -b "DICOM_VIEWER_SESSION=your_session_id"
```

## Restore from Backup

### Via Admin Panel:
1. Login as admin
2. Go to Backup Settings
3. Click "View Backups"
4. Select backup to restore
5. Click "Restore"
6. Confirm restoration

### Via API:
```bash
curl -X POST http://localhost/api/backup/restore.php \
  -H "Content-Type: application/json" \
  -b "DICOM_VIEWER_SESSION=your_session_id" \
  -d '{"backup_id": 123}'
```

## Testing

### Test Google Drive Connection:

```bash
curl -X POST http://localhost/api/backup/test-connection.php \
  -H "Content-Type: application/json" \
  -b "DICOM_VIEWER_SESSION=your_session_id"
```

### Run Backup Service Manually:

```bash
# Windows
cd c:\xampp\htdocs\papa\dicom_again\claude\scripts
php backup-service.php

# Linux/Mac
cd /path/to/scripts
php backup-service.php
```

## Logs

All backup operations are logged to:
- **Backup logs**: `/logs/gdrive-backup.log`
- **Service logs**: `/logs/backup-service.log`
- **General logs**: `/logs/backup.log`

## Troubleshooting

### Issue: "Google Drive not authenticated"
**Solution**: Complete OAuth authentication in admin panel

### Issue: "mysqldump command not found"
**Solution**: The system will automatically fall back to PHP-based backup

### Issue: "Insufficient permissions"
**Solution**: Ensure PHP has write permissions to `/backups/temp/` directory

### Issue: "Upload failed"
**Solution**:
- Check Google Drive quota
- Verify OAuth token is valid
- Check network connectivity

### Issue: "Scheduled backup not running"
**Solution**:
- Verify Task Scheduler task is enabled
- Check task runs with correct user permissions
- Review logs in `/logs/backup-service.log`

## Security Considerations

1. **Credentials Storage**: Client ID, Client Secret, and Refresh Token are stored in database
2. **Access Control**: All backup APIs require admin authentication
3. **Audit Logging**: All backup operations are logged to `audit_logs` table
4. **HTTPS**: Use HTTPS in production for OAuth redirects
5. **Token Encryption**: Consider encrypting refresh token in database (future enhancement)

## Backup Structure

```
backup_YYYY-MM-DD_HH-MM-SS.zip
├── database/
│   └── database.sql          # Full database dump
├── includes/                 # PHP includes
├── api/                      # API endpoints
├── admin/                    # Admin pages
├── auth/                     # Authentication
├── public/                   # Public files
├── scripts/                  # Scripts
├── js/                       # JavaScript files (if enabled)
├── assets/js/                # Asset JavaScript files (if enabled)
└── config/                   # Configuration files
    ├── .env
    ├── .env.example
    └── .htaccess
```

## API Endpoints Documentation

### 1. Configure Google Drive
**POST** `/api/backup/configure-gdrive.php`

```json
{
  "client_id": "your-client-id",
  "client_secret": "your-client-secret",
  "folder_name": "DICOM_Viewer_Backups",
  "backup_enabled": true,
  "backup_schedule": "daily",
  "backup_time": "02:00",
  "retention_days": 30,
  "backup_database": true,
  "backup_php_files": true,
  "backup_js_files": true,
  "backup_config_files": true
}
```

### 2. Create Backup Now
**POST** `/api/backup/backup-now.php`

Response:
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "backup_name": "dicom_viewer_backup_2025-11-22_14-30-00",
    "size_bytes": 15728640,
    "size_formatted": "15 MB",
    "gdrive_file_id": "1abc123xyz"
  }
}
```

### 3. List Backups
**GET** `/api/backup/list-backups.php`

Response:
```json
{
  "success": true,
  "data": {
    "backups": [...],
    "total": 10,
    "statistics": {
      "total_backups": 10,
      "successful_backups": 9,
      "failed_backups": 1,
      "total_size_formatted": "150 MB"
    }
  }
}
```

### 4. Get Status
**GET** `/api/backup/status.php`

### 5. Restore Backup
**POST** `/api/backup/restore.php`

```json
{
  "backup_id": 123
}
```

### 6. Delete Backup
**DELETE** `/api/backup/delete.php`

```json
{
  "backup_id": 123
}
```

### 7. Test Connection
**POST** `/api/backup/test-connection.php`

### 8. Cleanup Old Backups
**POST** `/api/backup/cleanup-old.php`

## Database Tables

### gdrive_backup_config
Stores Google Drive configuration and backup settings

### backup_history
Stores history of all backup operations with metadata

## Support

For issues or questions, check:
1. Application logs in `/logs/`
2. Database `audit_logs` table
3. Google Cloud Console for API quotas

## Version
Hospital DICOM Viewer Pro v2.0
Google Drive Backup System
Created: November 2025
