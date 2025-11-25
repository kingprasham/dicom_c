# Hospital Data Import System - Quick Start Guide

## System Overview
The Hospital Data Import System allows you to import existing DICOM files from hospital directories into the Orthanc PACS server with progress tracking, duplicate detection, and error handling.

## Quick Setup

### 1. Verify Installation
All files have been created in the following locations:

**PHP Class:**
- `c:\xampp\htdocs\papa\dicom_again\claude\includes\classes\HospitalDataImporter.php`

**API Endpoints:**
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\scan-directory.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\start-import.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\process-import.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\import-status.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\configure-hospital-path.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\get-import-history.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\get-configuration.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\cancel-import.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\list-jobs.php`
- `c:\xampp\htdocs\papa\dicom_again\claude\api\sync\scan-new-files.php`

### 2. Test the System
Run the test script to verify everything is working:

```bash
cd c:\xampp\htdocs\papa\dicom_again\claude\api\sync
php test-import-system.php
```

### 3. Configure Hospital Data Path
Update your `.env` file or use the API:

```bash
curl -X POST http://localhost/api/sync/configure-hospital-path.php \
  -H "Content-Type: application/json" \
  -d '{
    "hospital_data_path": "C:\\Hospital\\DICOM\\Data",
    "monitoring_enabled": true,
    "monitoring_interval": 30
  }'
```

## Basic Usage

### Example 1: Import All Files from Directory

**Step 1: Scan Directory**
```javascript
fetch('/api/sync/scan-directory.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    path: 'C:\\Hospital\\DICOM\\Data'
  })
})
.then(response => response.json())
.then(data => {
  console.log('Found', data.data.total_files, 'DICOM files');
  console.log('Total size:', data.data.total_size_formatted);
});
```

**Step 2: Create Import Job**
```javascript
fetch('/api/sync/start-import.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    source_path: 'C:\\Hospital\\DICOM\\Data',
    job_type: 'manual'
  })
})
.then(response => response.json())
.then(data => {
  const jobId = data.data.job_id;
  console.log('Import job created:', jobId);

  // Step 3: Process the import
  return fetch('/api/sync/process-import.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ job_id: jobId })
  });
})
.then(response => response.json())
.then(data => {
  console.log('Import completed:', data);
});
```

**Step 4: Monitor Progress**
```javascript
function checkProgress(jobId) {
  fetch(`/api/sync/import-status.php?job_id=${jobId}`)
    .then(response => response.json())
    .then(data => {
      const progress = data.data;
      console.log(`Progress: ${progress.progress_percentage}%`);
      console.log(`Imported: ${progress.files_imported}/${progress.total_files}`);

      if (progress.status === 'running') {
        // Check again in 2 seconds
        setTimeout(() => checkProgress(jobId), 2000);
      } else {
        console.log('Import finished with status:', progress.status);
      }
    });
}

// Start monitoring
checkProgress(123);
```

### Example 2: Scan for New Files Only

```javascript
fetch('/api/sync/scan-new-files.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    path: 'C:\\Hospital\\DICOM\\Data'
  })
})
.then(response => response.json())
.then(data => {
  console.log('New files:', data.data.new_files);
  console.log('Already imported:', data.data.already_imported);

  if (data.data.new_files > 0) {
    // Create incremental import job
    return fetch('/api/sync/start-import.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        source_path: 'C:\\Hospital\\DICOM\\Data',
        job_type: 'incremental'
      })
    });
  }
});
```

### Example 3: View Import History

```javascript
fetch('/api/sync/get-import-history.php?limit=50&status=imported')
  .then(response => response.json())
  .then(data => {
    console.log('Import history:', data.data.history);
    console.log('Statistics:', data.data.statistics);
  });
```

### Example 4: List All Import Jobs

```javascript
fetch('/api/sync/list-jobs.php?limit=20&status=completed')
  .then(response => response.json())
  .then(data => {
    data.data.jobs.forEach(job => {
      console.log(`Job ${job.id}: ${job.status} - ${job.files_imported}/${job.total_files} files`);
    });
  });
```

## Frontend Integration Example

Here's a simple HTML/JavaScript example for importing DICOM files:

```html
<!DOCTYPE html>
<html>
<head>
    <title>DICOM Import</title>
</head>
<body>
    <h1>Hospital DICOM Import</h1>

    <div>
        <label>Directory Path:</label>
        <input type="text" id="path" value="C:\Hospital\DICOM\Data" style="width: 400px">
        <button onclick="scanDirectory()">Scan Directory</button>
    </div>

    <div id="scan-results"></div>

    <div>
        <button id="import-btn" onclick="startImport()" disabled>Start Import</button>
    </div>

    <div id="progress"></div>

    <script>
        let currentJobId = null;

        async function scanDirectory() {
            const path = document.getElementById('path').value;
            const response = await fetch('/api/sync/scan-directory.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ path })
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById('scan-results').innerHTML = `
                    <p>Found ${data.data.total_files} DICOM files</p>
                    <p>Total size: ${data.data.total_size_formatted}</p>
                `;
                document.getElementById('import-btn').disabled = false;
            }
        }

        async function startImport() {
            const path = document.getElementById('path').value;

            // Create job
            const response = await fetch('/api/sync/start-import.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    source_path: path,
                    job_type: 'manual'
                })
            });

            const data = await response.json();
            if (data.success) {
                currentJobId = data.data.job_id;

                // Process import
                fetch('/api/sync/process-import.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: currentJobId })
                });

                // Start monitoring
                monitorProgress();
            }
        }

        async function monitorProgress() {
            const response = await fetch(`/api/sync/import-status.php?job_id=${currentJobId}`);
            const data = await response.json();

            if (data.success) {
                const progress = data.data;
                document.getElementById('progress').innerHTML = `
                    <p>Status: ${progress.status}</p>
                    <p>Progress: ${progress.progress_percentage}%</p>
                    <p>Imported: ${progress.files_imported}/${progress.total_files}</p>
                    <p>Failed: ${progress.files_failed}</p>
                `;

                if (progress.status === 'running') {
                    setTimeout(monitorProgress, 2000);
                }
            }
        }
    </script>
</body>
</html>
```

## Command Line Usage

For large imports, use the command line:

```bash
# Test the system
php test-import-system.php

# You can also create a CLI script for batch imports
php -r "
require_once 'includes/config.php';
require_once 'includes/classes/HospitalDataImporter.php';

\$importer = new HospitalDataImporter();
\$jobId = \$importer->createImportJob('C:\\\Hospital\\\DICOM\\\Data', 'manual', 0, 0);
echo 'Job created: ' . \$jobId . PHP_EOL;
"
```

## Important Notes

1. **Authentication Required**: All API endpoints require admin authentication
2. **Path Format**: Use double backslashes for Windows paths in JSON (`C:\\Hospital\\DICOM\\Data`)
3. **Large Imports**: For large datasets, consider processing in batches
4. **Duplicate Detection**: The system automatically detects and skips duplicate files
5. **Error Handling**: Failed imports are logged but don't stop the entire process
6. **Progress Tracking**: Poll `import-status.php` regularly for real-time progress

## Troubleshooting

### Issue: "Directory does not exist"
- Verify the path is correct and accessible
- Check Windows path format (use `\\` instead of `\`)
- Ensure the web server has read permissions

### Issue: "Authentication required"
- Make sure you're logged in as an admin user
- Check session is active

### Issue: "No DICOM files found"
- Verify files have `.dcm` extension or DICM header at byte 128
- Check files are readable

### Issue: Import is slow
- Large files take time to process
- Check Orthanc server performance
- Consider batch processing for very large datasets

## Next Steps

1. Integrate with your frontend UI
2. Set up scheduled monitoring for new files
3. Configure automated incremental imports
4. Review import history regularly
5. Monitor Orthanc storage space

For detailed documentation, see `README_IMPORT_SYSTEM.md`
