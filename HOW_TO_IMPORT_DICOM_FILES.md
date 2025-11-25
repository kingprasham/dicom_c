# How to Import DICOM Files - Complete Guide

## Overview
The DICOM Viewer Pro system supports **recursive import** of DICOM files from any directory structure. This means you can point to a parent folder containing multiple subdirectories of DICOM studies, and the system will automatically scan all subdirectories and import all DICOM files.

## Directory Structure Support

### ✅ Supported Structures

**Single Series (works):**
```
C:\Users\prash\Downloads\dicom\series-000001\
├── image-00001.dcm
├── image-00002.dcm
└── image-00003.dcm
```

**Multiple Series in Subdirectories (works):**
```
C:\Users\prash\Downloads\dicom\
├── series-000001\
│   ├── image-00001.dcm
│   ├── image-00002.dcm
│   └── image-00003.dcm
├── series-000002\
│   ├── image-00001.dcm
│   └── image-00002.dcm
├── patient-john-doe\
│   ├── ct-chest\
│   │   └── images.dcm
│   └── mri-brain\
│       └── images.dcm
└── any-nested-structure\
    └── deeply\
        └── nested\
            └── folder\
                └── study.dcm
```

## Step-by-Step Import Instructions

### Method 1: Using Hospital Config Page (Recommended)

1. **Login as Admin**
   - Navigate to: `http://localhost/papa/dicom_again/claude/pages/patients.html`
   - Click on "Hospital Config" button (available for admin users)

2. **Navigate to Import Section**
   - Go to the "Import Existing Studies" section
   - You'll see a directory path input field

3. **Enter Parent Directory Path**
   - Enter: `C:\Users\prash\Downloads\dicom\` (the parent folder)
   - The system will automatically scan ALL subdirectories recursively

4. **Click "Scan Directory"**
   - The system will display the count of DICOM files found
   - It will show files grouped by subdirectories

5. **Review and Import**
   - Review the list of files to be imported
   - Click "Start Import" to begin the import process
   - Monitor the progress bar as files are uploaded to Orthanc

6. **Verify Import**
   - After import completes, go to "Patients" page
   - You should see all patients from all subdirectories

### Method 2: Using Test Script (For Debugging)

1. **Open the test script**
   - Navigate to: `http://localhost/papa/dicom_again/claude/test-recursive-scan.php`

2. **Modify the test path**
   - Edit line 7 in the file to your directory:
     ```php
     $testDirectory = 'C:\\Users\\prash\\Downloads\\dicom\\';
     ```

3. **Run the test**
   - Refresh the page
   - It will show you:
     - Total files found
     - Files grouped by directory
     - Scan time
     - Sample file list

4. **Verify Results**
   - If it finds 0 files, check:
     - Directory path is correct
     - Files have .dcm extension or DICM marker
     - PHP has read permissions

## How the Recursive Scanner Works

### Backend Logic

The `scanDicomFiles()` function recursively scans directories:

```php
function scanDicomFiles($dir, &$files, $recursive = true) {
    $items = @scandir($dir);
    if ($items === false) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path) && $recursive) {
            // RECURSIVELY scan subdirectories
            scanDicomFiles($path, $files, $recursive);
        } elseif (is_file($path)) {
            // Check if it's a DICOM file
            $handle = @fopen($path, 'rb');
            if ($handle) {
                fseek($handle, 128);
                $marker = fread($handle, 4);
                fclose($handle);

                // Valid DICOM file has "DICM" marker at byte 128
                // Or has .dcm extension
                if ($marker === 'DICM' ||
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'dcm') {
                    $files[] = [
                        'path' => $path,
                        'name' => basename($path),
                        'size' => filesize($path)
                    ];
                }
            }
        }
    }
}
```

### DICOM File Detection

The system identifies DICOM files by:
1. **DICM Marker**: Reads byte 128-131 and checks for "DICM" string
2. **File Extension**: Checks if file ends with `.dcm` or `.dicom`

### Import Process

1. **Scan Phase**
   - Recursively scans all subdirectories
   - Identifies valid DICOM files
   - Counts files and calculates total size

2. **Upload Phase**
   - Uploads each file to Orthanc PACS
   - Orthanc automatically extracts metadata (Patient Name, Study UID, etc.)
   - Records import in database

3. **Sync Phase**
   - Syncs patient data from Orthanc to local database
   - Creates patient records in `cached_patients` table
   - Creates study records in `cached_studies` table

4. **Display Phase**
   - Patients page queries local database
   - Displays all patients with their studies
   - Click patient to view studies
   - Click study to open viewer

## Troubleshooting

### Problem: "No DICOM files found"

**Solution:**
1. Check directory path is correct
2. Verify files have `.dcm` extension
3. Check PHP has read permissions:
   ```bash
   # On Windows, check folder properties > Security
   # Make sure IUSR and IIS_IUSRS have Read permissions
   ```

### Problem: "Directory does not exist"

**Solution:**
1. Use absolute path (e.g., `C:\Users\...`)
2. Use double backslashes on Windows: `C:\\Users\\...`
3. Or use forward slashes: `C:/Users/...`

### Problem: Files imported but not showing in patients page

**Solution:**
1. Check Orthanc is running:
   - Navigate to: `http://localhost:8042`
   - Login with credentials (orthanc / orthanc)
   - Check if studies are visible

2. Sync Orthanc data:
   - Go to patients page
   - Click "Refresh" button
   - This syncs data from Orthanc to local database

3. Check database:
   ```sql
   SELECT * FROM cached_patients;
   SELECT * FROM cached_studies;
   ```

### Problem: Import fails midway

**Solution:**
1. Check PHP execution time:
   ```php
   // In php.ini:
   max_execution_time = 3600
   memory_limit = 512M
   ```

2. Check Orthanc storage space:
   - Default storage: `C:\Orthanc\OrthancStorage`
   - Make sure there's enough disk space

3. Check error logs:
   - `logs/app.log`
   - `logs/import.log`

## API Endpoints

### Scan Directory
```bash
POST /api/hospital-config/scan-directory.php
Content-Type: application/json

{
    "directory": "C:/Users/prash/Downloads/dicom/",
    "recursive": true
}

Response:
{
    "success": true,
    "count": 150,
    "files": [...]
}
```

### Import Studies
```bash
POST /api/hospital-config/import-existing-studies.php
Content-Type: application/json

{
    "directory": "C:/Users/prash/Downloads/dicom/",
    "auto_backup": true
}

Response:
{
    "success": true,
    "batch_id": "IMPORT_20251125_143052_abc123",
    "imported": 150,
    "errors": 0
}
```

### Check Import Progress
```bash
GET /api/hospital-config/import-progress.php?batch_id=IMPORT_20251125_143052_abc123

Response:
{
    "success": true,
    "progress": 75,
    "current": 113,
    "total": 150,
    "status": "importing",
    "current_file": "image-00113.dcm"
}
```

## Performance Tips

1. **Large Imports (1000+ files)**
   - Import runs in background
   - You can close browser and check progress later
   - Uses progress file in system temp directory

2. **Network Drives**
   - Avoid UNC paths: `\\server\share\dicom`
   - Map network drive first: `Z:\dicom`
   - Then use mapped path

3. **Speed Optimization**
   - Put DICOM files on local SSD for faster scanning
   - Orthanc storage should be on fast disk
   - Use PHP-FPM or FastCGI for better performance

## Summary

✅ **The system ALREADY supports recursive scanning**
✅ **Just provide the parent directory path**
✅ **All subdirectories are automatically scanned**
✅ **No code changes needed**

Example:
- Input: `C:\Users\prash\Downloads\dicom\`
- System scans: `dicom\series-000001\`, `dicom\series-000002\`, etc.
- Result: All DICOM files from all subdirectories are imported

If you still have issues, run the test script first to verify the scanner can see your files!
