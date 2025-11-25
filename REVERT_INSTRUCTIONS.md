# Revert Instructions - DICOM Viewer Changes

## Quick Revert (If Something Goes Wrong)

If you encounter any issues with the new changes and need to revert to the original system:

### Step 1: Stop All Services

```powershell
# Stop DICOM related Windows services (if running)
net stop DicomViewer_FTP_Sync
net stop DicomViewer_Backup
net stop DicomViewer_DataMonitor
```

### Step 2: Restore Files

```powershell
# Delete current directory
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude" -Recurse -Force

# Copy backup back
Copy-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude_backup_original" -Destination "c:\xampp\htdocs\papa\dicom_again\claude" -Recurse
```

**OR** use robocopy for faster restoration:

```powershell
robocopy "c:\xampp\htdocs\papa\dicom_again\claude_backup_original" "c:\xampp\htdocs\papa\dicom_again\claude" /MIR
```

### Step 3: Restore Database (Optional)

If database changes were made and you need to revert:

```powershell
# Using MySQL command line
mysql -u root -p dicom_viewer_v2_production < c:\xampp\htdocs\papa\dicom_again\claude\dicom_viewer_v2_production.sql
```

**OR** using phpMyAdmin:
1. Open http://localhost/phpmyadmin
2. Select `dicom_viewer_v2_production` database
3. Click "Import" tab
4. Choose `dicom_viewer_v2_production.sql` file
5. Click "Go"

### Step 4: Restart Services

```powershell
# Restart Apache and MySQL
net stop Apache2.4
net stop MySQL
net start MySQL
net start Apache2.4

# Restart DICOM services (if they were running)
net start DicomViewer_FTP_Sync
net start DicomViewer_Backup
net start DicomViewer_DataMonitor
```

### Step 5: Verify

1. Open browser and go to: http://localhost/papa/dicom_again/claude/
2. Try logging in with your credentials
3. Check that patients page loads correctly
4. Verify DICOM viewer functionality

---

## Selective Revert (Revert Specific Files Only)

If only certain features are causing issues:

### Revert Settings System Only

```powershell
# Remove new settings files
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude\admin\settings.php" -Force
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude\api\settings" -Recurse -Force

# Revert database (remove settings table)
# In MySQL:
# DROP TABLE IF EXISTS system_settings;
```

### Revert Patients Page UI Only

```powershell
# Restore original patients.php
Copy-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude_backup_original\patients.php" -Destination "c:\xampp\htdocs\papa\dicom_again\claude\patients.php" -Force

# Remove new CSS file
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude\css\patients-enhanced.css" -Force
```

### Revert Hospital Configuration Only

```powershell
# Remove hospital config files
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude\admin\hospital-config.php" -Force
Remove-Item -Path "c:\xampp\htdocs\papa\dicom_again\claude\api\hospital-config" -Recurse -Force

# Remove imported_studies table
# In MySQL:
# DROP TABLE IF EXISTS imported_studies;
```

---

## Important Notes

- **Backup Location**: `c:\xampp\htdocs\papa\dicom_again\claude_backup_original`
- **Keep this backup safe**: Don't delete it until you're 100% satisfied with the new changes
- **Database Backup**: Always backup the database before making changes
- **Test After Revert**: Always verify the system works after reverting

---

## Contact & Support

If you need help with reverting or encounter issues:
1. Check the logs in `logs/` directory for error messages
2. Refer to the original documentation in the backup folder
3. The backup is a complete copy - everything should work exactly as before

---

## Safety Measures

The following safety measures are in place:

1. ✅ Complete file backup created
2. ✅ Original database SQL file preserved
3. ✅ All changes are documented
4. ✅ Revert process is straightforward
5. ✅ Partial revert options available

**Last Backup Created**: 2025-11-24 17:19:00 IST
**Backup Location**: `c:\xampp\htdocs\papa\dicom_again\claude_backup_original`
