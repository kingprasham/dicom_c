# Backup API Endpoints

## Overview
This directory contains all API endpoints for the Google Drive Backup System.

## Authentication
All endpoints require:
- Valid session (admin role only)
- Access via `isLoggedIn()` and `isAdmin()` checks

## Endpoints

### 1. Configure Google Drive Settings
**Endpoint**: `POST /api/backup/configure-gdrive.php`

**Request Body**:
```json
{
  "client_id": "string (required)",
  "client_secret": "string (required)",
  "folder_name": "string (optional, default: DICOM_Viewer_Backups)",
  "backup_enabled": "boolean (optional)",
  "backup_schedule": "daily|weekly|monthly (optional)",
  "backup_time": "HH:MM (optional)",
  "retention_days": "integer (optional)",
  "backup_database": "boolean (optional)",
  "backup_php_files": "boolean (optional)",
  "backup_js_files": "boolean (optional)",
  "backup_config_files": "boolean (optional)"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Google Drive backup configuration updated successfully",
  "data": {
    "updated_fields": 8
  }
}
```

---

### 2. Create Backup Now
**Endpoint**: `POST /api/backup/backup-now.php`

**Request**: No body required

**Response**:
```json
{
  "success": true,
  "message": "Backup created successfully",
  "data": {
    "backup_name": "dicom_viewer_backup_2025-11-22_14-30-00",
    "size_bytes": 15728640,
    "size_formatted": "15 MB",
    "gdrive_file_id": "1abc123xyz789",
    "local_path": "/path/to/backup.zip"
  }
}
```

**Error Response**:
```json
{
  "success": false,
  "error": "Backup failed: Error message here"
}
```

---

### 3. List All Backups
**Endpoint**: `GET /api/backup/list-backups.php`

**Request**: No parameters

**Response**:
```json
{
  "success": true,
  "message": "Backups retrieved successfully",
  "data": {
    "backups": [
      {
        "id": 1,
        "backup_name": "dicom_viewer_backup_2025-11-22_02-00-00",
        "backup_type": "scheduled",
        "gdrive_file_id": "1abc123xyz",
        "size_bytes": 15728640,
        "size_formatted": "15 MB",
        "status": "success",
        "created_at": "2025-11-22 02:00:00",
        "includes_database": true,
        "includes_php": true,
        "includes_js": true,
        "includes_config": true
      }
    ],
    "total": 10,
    "statistics": {
      "total_backups": 10,
      "successful_backups": 9,
      "failed_backups": 1,
      "total_size_bytes": 157286400,
      "total_size_formatted": "150 MB"
    }
  }
}
```

---

### 4. Restore from Backup
**Endpoint**: `POST /api/backup/restore.php`

**Request Body**:
```json
{
  "backup_id": 123
}
```

**Response**:
```json
{
  "success": true,
  "message": "Backup restored successfully",
  "data": {
    "backup_name": "dicom_viewer_backup_2025-11-22_02-00-00"
  }
}
```

**Note**: This operation restores the database only by default for safety.

---

### 5. Get Backup Status
**Endpoint**: `GET /api/backup/status.php`

**Request**: No parameters

**Response**:
```json
{
  "success": true,
  "message": "Backup status retrieved successfully",
  "data": {
    "configuration": {
      "backup_enabled": true,
      "backup_schedule": "daily",
      "backup_time": "02:00:00",
      "retention_days": 30,
      "folder_name": "DICOM_Viewer_Backups",
      "has_credentials": true,
      "is_authenticated": true,
      "last_backup_at": "2025-11-22 02:00:00",
      "next_backup_at": "2025-11-23 02:00:00",
      "backup_database": true,
      "backup_php_files": true,
      "backup_js_files": true,
      "backup_config_files": true
    },
    "last_backup": {
      "id": 10,
      "backup_name": "dicom_viewer_backup_2025-11-22_02-00-00",
      "backup_type": "scheduled",
      "status": "success",
      "size_bytes": 15728640,
      "size_formatted": "15 MB",
      "created_at": "2025-11-22 02:00:00",
      "error_message": null
    },
    "statistics": {
      "total_backups": 10,
      "successful_backups": 9,
      "failed_backups": 1,
      "total_size_bytes": 157286400,
      "total_size_formatted": "150 MB"
    }
  }
}
```

---

### 6. Test Google Drive Connection
**Endpoint**: `POST /api/backup/test-connection.php`

**Request**: No body required

**Response** (Success):
```json
{
  "success": true,
  "message": "Connection test successful",
  "data": {
    "authenticated": true
  }
}
```

**Response** (Failure):
```json
{
  "success": false,
  "error": "Connection failed: Error details here"
}
```

---

### 7. OAuth2 Callback Handler
**Endpoint**: `GET /api/backup/oauth-callback.php?code=AUTH_CODE`

**Request**: Query parameter `code` from Google OAuth redirect

**Response**: Redirects to admin backup settings page with success/error message

**Success Redirect**: `/admin/backup-settings.html?success=authenticated`

**Error Redirect**: `/admin/backup-settings.html?error=error_message`

---

### 8. Delete Backup
**Endpoint**: `DELETE /api/backup/delete.php`

**Request Body**:
```json
{
  "backup_id": 123
}
```

**Response**:
```json
{
  "success": true,
  "message": "Backup deleted successfully",
  "data": {
    "backup_id": 123,
    "backup_name": "dicom_viewer_backup_2025-11-20_02-00-00"
  }
}
```

---

### 9. Cleanup Old Backups
**Endpoint**: `POST /api/backup/cleanup-old.php`

**Request**: No body required (uses retention_days from config)

**Response**:
```json
{
  "success": true,
  "message": "Cleanup completed successfully",
  "data": {
    "deleted_count": 5,
    "errors": []
  }
}
```

## Error Responses

All endpoints return standard error format:

```json
{
  "success": false,
  "error": "Error message description"
}
```

HTTP Status Codes:
- `200` - Success
- `400` - Bad Request (invalid input)
- `403` - Forbidden (not authenticated or not admin)
- `404` - Not Found (backup not found)
- `405` - Method Not Allowed (wrong HTTP method)
- `500` - Internal Server Error

## Security

- All endpoints check `isLoggedIn()` and `isAdmin()`
- All backup operations are logged to `audit_logs` table
- All operations logged to backup log files
- Session-based authentication required
- CSRF protection recommended for production

## Usage Examples

### JavaScript (Frontend)

```javascript
// Create backup
async function createBackup() {
  const response = await fetch('/api/backup/backup-now.php', {
    method: 'POST',
    credentials: 'include'
  });
  const result = await response.json();
  return result;
}

// Get status
async function getBackupStatus() {
  const response = await fetch('/api/backup/status.php', {
    credentials: 'include'
  });
  const result = await response.json();
  return result;
}

// Restore backup
async function restoreBackup(backupId) {
  const response = await fetch('/api/backup/restore.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    credentials: 'include',
    body: JSON.stringify({ backup_id: backupId })
  });
  const result = await response.json();
  return result;
}
```

### cURL (Command Line)

```bash
# Create backup
curl -X POST http://localhost/api/backup/backup-now.php \
  -b "DICOM_VIEWER_SESSION=your_session_id"

# List backups
curl -X GET http://localhost/api/backup/list-backups.php \
  -b "DICOM_VIEWER_SESSION=your_session_id"

# Restore backup
curl -X POST http://localhost/api/backup/restore.php \
  -H "Content-Type: application/json" \
  -b "DICOM_VIEWER_SESSION=your_session_id" \
  -d '{"backup_id": 123}'
```

## Files in this Directory

- `configure-gdrive.php` - Configure Google Drive credentials and settings
- `backup-now.php` - Trigger immediate backup
- `list-backups.php` - List all backups with statistics
- `restore.php` - Restore from backup
- `status.php` - Get current status and configuration
- `test-connection.php` - Test Google Drive connection
- `oauth-callback.php` - Handle OAuth2 redirect from Google
- `delete.php` - Delete specific backup
- `cleanup-old.php` - Cleanup old backups based on retention policy
- `README.md` - This file
