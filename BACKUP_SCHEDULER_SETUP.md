# Windows Task Scheduler Setup for Automated Backups

## Create Scheduled Task

1. **Open Task Scheduler**
   - Press `Win + R`
   - Type: `taskschd.msc`
   - Press Enter

2. **Create New Task**
   - Click "Create Basic Task" in the right panel
   - Name: `DICOM Viewer Auto Backup`
   - Description: `Automatically backs up DICOM viewer data every 6 hours`
   - Click "Next"

3. **Set Trigger**
   - Select "Daily"
   - Click "Next"
   - Set start date and time (e.g., today at current time)
   - Recur every: `1` day
   - Click "Next"

4. **Set Action**
   - Select "Start a program"
   - Click "Next"
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `-f backup-scheduler.php`
   - Start in: `C:\xampp\htdocs\papa\dicom_again\claude`
   - Click "Next"

5. **Advanced Settings** (After clicking Finish)
   - Right-click the task → Properties
   - Go to "Triggers" tab
   - Edit the trigger
   - Check "Repeat task every: 6 hours"
   - For a duration of: Indefinitely
   - Click OK

6. **Test the Task**
   - Right-click the task
   - Click "Run"
   - Check the "History" tab for results

## Alternative: Run manually every 6 hours

If you prefer to test first, you can run manually:

```cmd
cd C:\xampp\htdocs\papa\dicom_again\claude
php backup-scheduler.php
```

The script will check if backup is due and execute if needed.

## Verify Setup

After setup, the system will:
- Run every 6 hours automatically
- Back up to all active Google Drive accounts
- Update "last backup" timestamp
- Show next backup time in UI
