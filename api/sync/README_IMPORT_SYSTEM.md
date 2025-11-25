# Hospital Data Import System - Documentation

## Overview
The Hospital Data Import System allows administrators to import existing DICOM data from hospital directories into the Orthanc PACS server. The system supports bulk imports, duplicate detection, progress tracking, and monitoring for new files.

## Components Created

### A. PHP Class

**File:** `c:\xampp\htdocs\papa\dicom_again\claude\includes\classes\HospitalDataImporter.php`

**Methods:**
- `scanDirectory($path)` - Recursively scans directory for DICOM files (.dcm and headerless)
- `isDicomFile($filepath)` - Checks for DICM header at byte 128
- `importFileToOrthanc($filepath)` - POSTs DICOM file to Orthanc /instances endpoint
- `batchImport($files, $jobId)` - Imports multiple files with progress tracking
- `scanForNewFiles($path)` - Checks for files not in import_history
- `calculateFileHash($filepath)` - Calculates MD5 hash for duplicate detection
- `updateJobProgress($jobId, $processed, $imported, $failed)` - Updates import_jobs table
- `createImportJob($sourcePath, $jobType, $totalFiles, $totalSizeBytes)` - Creates new import job
- `getJobDetails($jobId)` - Retrieves job information

### B. API Endpoints

All endpoints are located in `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\`

#### 1. scan-directory.php
**Purpose:** Scan hospital data directory for DICOM files

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "path": "C:\\Hospital\\DICOM\\Data"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "path": "C:\\Hospital\\DICOM\\Data",
    "total_files": 1500,
    "total_size_bytes": 2147483648,
    "total_size_formatted": "2.00 GB",
    "file_list": [
      {
        "path": "C:\\Hospital\\DICOM\\Data\\file1.dcm",
        "name": "file1.dcm",
        "size": 524288,
        "size_formatted": "512.00 KB",
        "modified": "2025-11-22 10:30:00"
      }
    ]
  }
}
```

#### 2. start-import.php
**Purpose:** Create a new import job

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "source_path": "C:\\Hospital\\DICOM\\Data",
  "job_type": "manual"
}
```

**Job Types:**
- `initial` - First-time import of all data
- `incremental` - Import only new files
- `manual` - User-initiated import

**Response:**
```json
{
  "success": true,
  "message": "Import job created successfully",
  "data": {
    "job_id": 123,
    "source_path": "C:\\Hospital\\DICOM\\Data",
    "job_type": "manual",
    "total_files": 1500,
    "total_size_bytes": 2147483648,
    "total_size_formatted": "2.00 GB",
    "status": "pending"
  }
}
```

#### 3. process-import.php
**Purpose:** Execute the import process for a job

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "job_id": 123,
  "batch_size": 100
}
```

**Response:**
```json
{
  "success": true,
  "message": "Import job processed successfully",
  "data": {
    "job_id": 123,
    "status": "completed",
    "total_files": 1500,
    "files_processed": 1500,
    "files_imported": 1450,
    "files_failed": 10,
    "duplicates": 40,
    "errors": []
  }
}
```

#### 4. import-status.php
**Purpose:** Get real-time status of import job

**Method:** GET

**Authentication:** Admin required

**Query Parameters:**
- `job_id` - Import job ID (required)

**Example:** `GET /api/sync/import-status.php?job_id=123`

**Response:**
```json
{
  "success": true,
  "data": {
    "job_id": 123,
    "job_type": "manual",
    "source_path": "C:\\Hospital\\DICOM\\Data",
    "status": "running",
    "total_files": 1500,
    "files_processed": 750,
    "files_imported": 720,
    "files_failed": 5,
    "total_size_bytes": 2147483648,
    "total_size_formatted": "2.00 GB",
    "progress_percentage": 50.00,
    "estimated_time_remaining": 300,
    "estimated_time_remaining_formatted": "5 minutes",
    "error_message": null,
    "started_at": "2025-11-22 10:00:00",
    "completed_at": null,
    "created_at": "2025-11-22 09:55:00"
  }
}
```

#### 5. configure-hospital-path.php
**Purpose:** Save hospital data path configuration

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "hospital_data_path": "C:\\Hospital\\DICOM\\Data",
  "monitoring_enabled": true,
  "monitoring_interval": 30
}
```

**Response:**
```json
{
  "success": true,
  "message": "Hospital data path configuration saved successfully",
  "data": {
    "hospital_data_path": "C:\\Hospital\\DICOM\\Data",
    "monitoring_enabled": true,
    "monitoring_interval": 30
  }
}
```

#### 6. get-import-history.php
**Purpose:** Retrieve import history with filtering

**Method:** GET

**Authentication:** Admin required

**Query Parameters:**
- `limit` - Number of records (default: 50, max: 1000)
- `offset` - Offset for pagination (default: 0)
- `job_id` - Filter by job ID (optional)
- `status` - Filter by status: imported, failed, duplicate, skipped (optional)

**Example:** `GET /api/sync/get-import-history.php?limit=50&offset=0&status=imported`

**Response:**
```json
{
  "success": true,
  "data": {
    "history": [
      {
        "id": 1,
        "job_id": 123,
        "job_type": "manual",
        "job_source_path": "C:\\Hospital\\DICOM\\Data",
        "file_path": "C:\\Hospital\\DICOM\\Data\\file1.dcm",
        "file_name": "file1.dcm",
        "file_size_bytes": 524288,
        "file_size_formatted": "512.00 KB",
        "file_hash": "5d41402abc4b2a76b9719d911017c592",
        "orthanc_instance_id": "12345-67890-abcdef",
        "patient_id": "PAT001",
        "study_uid": "1.2.3.4.5",
        "series_uid": "1.2.3.4.5.6",
        "instance_uid": "1.2.3.4.5.6.7",
        "status": "imported",
        "error_message": null,
        "imported_at": "2025-11-22 10:05:00"
      }
    ],
    "pagination": {
      "total": 1500,
      "limit": 50,
      "offset": 0,
      "has_more": true
    },
    "statistics": {
      "total_imports": 1500,
      "successful_imports": 1450,
      "failed_imports": 10,
      "duplicate_imports": 40,
      "skipped_imports": 0,
      "total_size_bytes": 2147483648,
      "total_size_formatted": "2.00 GB"
    }
  }
}
```

#### 7. get-configuration.php
**Purpose:** Get current sync configuration

**Method:** GET

**Authentication:** Admin required

**Response:**
```json
{
  "success": true,
  "data": {
    "hospital_data_path": "C:\\Hospital\\DICOM\\Data",
    "orthanc_storage_path": "C:\\Orthanc\\OrthancStorage",
    "monitoring_enabled": true,
    "monitoring_interval": 30,
    "sync_enabled": false,
    "sync_interval": 120,
    "last_sync_at": "2025-11-22 09:00:00",
    "ftp_configured": false,
    "has_configuration": true,
    "updated_at": "2025-11-22 08:00:00"
  }
}
```

#### 8. cancel-import.php
**Purpose:** Cancel a running or pending import job

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "job_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Import job cancelled successfully",
  "data": {
    "job_id": 123,
    "status": "cancelled"
  }
}
```

#### 9. list-jobs.php
**Purpose:** List all import jobs with filtering

**Method:** GET

**Authentication:** Admin required

**Query Parameters:**
- `limit` - Number of records (default: 20, max: 100)
- `offset` - Offset for pagination (default: 0)
- `status` - Filter by status: pending, running, completed, failed, cancelled (optional)
- `job_type` - Filter by type: initial, incremental, manual (optional)

**Example:** `GET /api/sync/list-jobs.php?limit=20&status=completed`

**Response:**
```json
{
  "success": true,
  "data": {
    "jobs": [
      {
        "id": 123,
        "job_type": "manual",
        "source_path": "C:\\Hospital\\DICOM\\Data",
        "total_files": 1500,
        "files_processed": 1500,
        "files_imported": 1450,
        "files_failed": 10,
        "total_size_bytes": 2147483648,
        "total_size_formatted": "2.00 GB",
        "status": "completed",
        "progress_percentage": 100.00,
        "error_message": null,
        "started_at": "2025-11-22 10:00:00",
        "completed_at": "2025-11-22 10:30:00",
        "created_at": "2025-11-22 09:55:00"
      }
    ],
    "pagination": {
      "total": 45,
      "limit": 20,
      "offset": 0,
      "has_more": true
    }
  }
}
```

#### 10. scan-new-files.php
**Purpose:** Scan directory for new files not yet imported

**Method:** POST

**Authentication:** Admin required

**Request Body:**
```json
{
  "path": "C:\\Hospital\\DICOM\\Data"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "path": "C:\\Hospital\\DICOM\\Data",
    "total_files": 1500,
    "new_files": 50,
    "already_imported": 1450,
    "total_size_bytes": 52428800,
    "total_size_formatted": "50.00 MB",
    "file_list": [
      {
        "path": "C:\\Hospital\\DICOM\\Data\\new_file.dcm",
        "name": "new_file.dcm",
        "size": 524288,
        "size_formatted": "512.00 KB",
        "modified": "2025-11-22 11:00:00"
      }
    ]
  }
}
```

## Database Tables

### import_jobs
Tracks import job metadata and progress.

**Columns:**
- `id` - Job ID (primary key)
- `job_type` - Type: initial, incremental, manual
- `source_path` - Source directory path
- `total_files` - Total files to import
- `files_processed` - Files processed so far
- `files_imported` - Successfully imported files
- `files_failed` - Failed imports
- `total_size_bytes` - Total size of all files
- `status` - Current status: pending, running, completed, failed, cancelled
- `error_message` - Error details if failed
- `started_at` - When job started
- `completed_at` - When job completed
- `created_at` - When job was created

### import_history
Records each file import attempt.

**Columns:**
- `id` - Record ID (primary key)
- `job_id` - Associated job ID
- `file_path` - Full file path
- `file_name` - File name
- `file_size_bytes` - File size
- `file_hash` - MD5 hash (for duplicate detection)
- `orthanc_instance_id` - Orthanc instance ID
- `patient_id` - Patient ID from DICOM
- `study_uid` - Study UID
- `series_uid` - Series UID
- `instance_uid` - Instance UID
- `status` - Status: imported, failed, duplicate, skipped
- `error_message` - Error details if failed
- `imported_at` - Import timestamp

### sync_configuration
Stores system configuration.

**Columns:**
- `id` - Config ID (primary key)
- `hospital_data_path` - Path to hospital DICOM data
- `orthanc_storage_path` - Orthanc storage path
- `monitoring_enabled` - Enable directory monitoring
- `monitoring_interval` - Monitoring interval in seconds
- `sync_enabled` - Enable automatic sync
- `sync_interval` - Sync interval in seconds
- `last_sync_at` - Last sync timestamp
- `ftp_host`, `ftp_username`, `ftp_password`, `ftp_port`, `ftp_path`, `ftp_passive` - FTP configuration
- `updated_at` - Last update timestamp

## Usage Workflow

### Initial Import
1. **Scan Directory**
   ```
   POST /api/sync/scan-directory.php
   { "path": "C:\\Hospital\\DICOM\\Data" }
   ```

2. **Create Import Job**
   ```
   POST /api/sync/start-import.php
   { "source_path": "C:\\Hospital\\DICOM\\Data", "job_type": "initial" }
   ```

3. **Process Import**
   ```
   POST /api/sync/process-import.php
   { "job_id": 123 }
   ```

4. **Monitor Progress**
   ```
   GET /api/sync/import-status.php?job_id=123
   ```

### Incremental Import
1. **Scan for New Files**
   ```
   POST /api/sync/scan-new-files.php
   { "path": "C:\\Hospital\\DICOM\\Data" }
   ```

2. **Create Incremental Job**
   ```
   POST /api/sync/start-import.php
   { "source_path": "C:\\Hospital\\DICOM\\Data", "job_type": "incremental" }
   ```

3. **Process Import**
   ```
   POST /api/sync/process-import.php
   { "job_id": 124 }
   ```

## Features

### Duplicate Detection
- Uses MD5 file hashing
- Checks `import_history` before importing
- Prevents re-importing same files
- Marks duplicates in history

### Progress Tracking
- Real-time progress updates
- Percentage calculation
- Estimated time remaining
- Files processed/imported/failed counts

### Error Handling
- Logs all operations
- Records error messages
- Continues processing on individual failures
- Provides detailed error reports

### Security
- Admin authentication required
- Path validation
- Prepared SQL statements
- Audit logging for all operations

### Performance
- Batch processing
- Configurable batch sizes
- Efficient file scanning
- Optimized database queries

## Logging

All operations are logged to `logs/import.log` with the following levels:
- `info` - Normal operations
- `warning` - Non-critical issues
- `error` - Critical errors

Audit logs are stored in the `audit_logs` database table.

## Error Codes

- `400` - Bad request (invalid input)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (not admin)
- `404` - Not found (job/file not found)
- `405` - Method not allowed
- `500` - Internal server error

## Configuration

Update `.env` file with:
```
HOSPITAL_DATA_PATH=C:\Hospital\DICOM\Data
MONITORING_ENABLED=true
ORTHANC_URL=http://localhost:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc
```

## Requirements

- PHP 7.4+
- MySQLi extension
- cURL extension
- Orthanc PACS server
- Admin user account
- Read access to hospital data directory

## Notes

- Large imports may take significant time
- Consider using CLI for very large datasets
- Monitor disk space on Orthanc server
- Regular cleanup of old import_history records recommended
- DICOM files must have DICM header at byte 128 or .dcm extension
