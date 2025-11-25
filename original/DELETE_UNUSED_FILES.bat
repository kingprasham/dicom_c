@echo off
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE UNUSED FILES - Cleanup script
:: This will delete all old test/fix/duplicate files
:: KEEP ONLY the essential working files for hospital deployment
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

cls
color 0C
echo ========================================================================
echo   WARNING: FILE DELETION SCRIPT
echo ========================================================================
echo.
echo This script will DELETE approximately 120+ old/unused files.
echo.
echo Files to be deleted:
echo   - Old test scripts
echo   - Duplicate fix scripts
echo   - Old installer attempts
echo   - Debug/diagnostic files
echo   - Old documentation
echo   - Log files
echo   - Cache directories
echo.
echo ESSENTIAL FILES WILL BE KEPT:
echo   - config.php, index.php, orthanc_api_gateway.py
echo   - All API files needed for operation
echo   - Auth files (login, session)
echo   - HTML pages (login, patients, studies)
echo   - CSS and JS directories
echo   - Current working scripts
echo.
echo ========================================================================
echo.
echo BEFORE PROCEEDING:
echo   1. Review CLEANUP_PLAN.txt to see what will be deleted
echo   2. Make a backup if unsure
echo.
pause
echo.
echo Are you ABSOLUTELY SURE you want to delete these files?
pause

cd /d "%~dp0"

echo.
echo Starting cleanup...
echo.

:: Create a log
set LOG=%~dp0CLEANUP_LOG.txt
echo Cleanup started: %date% %time% > "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE OLD FIX SCRIPTS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [1/10] Deleting old fix scripts...
del /Q AUTO_FIX_EVERYTHING.bat 2>nul
del /Q COMPLETE_AUTOFIX.bat 2>nul
del /Q COMPLETE_CLEANUP.bat 2>nul
del /Q DIAGNOSE_AND_FIX.bat 2>nul
del /Q FIX_AND_RESTART_SERVICES.bat 2>nul
del /Q FIX_NOW.bat 2>nul
del /Q FIX_NOW.html 2>nul
del /Q FIX_SERVICE_NOW.bat 2>nul
del /Q FINAL_FIX.bat 2>nul
del /Q FINAL_AUTOSTART_SOLUTION.bat 2>nul
del /Q FINAL_SERVICE_FIX.bat 2>nul
del /Q FINAL_NGROK_SETUP.bat 2>nul
del /Q FIX_NGROK_NOW.bat 2>nul
del /Q FORCE_KILL_AND_START.bat 2>nul
del /Q FORCE_RESTART_GATEWAY.bat 2>nul
del /Q ULTIMATE_FIX.bat 2>nul
del /Q WORKING_SERVICE_FIX.bat 2>nul
del /Q fix_dashboard.html 2>nul
del /Q quick_fix_dashboard.html 2>nul
echo   Deleted fix scripts >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE DATABASE FIX SCRIPTS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [2/10] Deleting database fix scripts...
del /Q check_and_insert_study.php 2>nul
del /Q check_database.php 2>nul
del /Q check_schema.php 2>nul
del /Q complete_reset.php 2>nul
del /Q consolidate_studies.php 2>nul
del /Q fix_database_schema.php 2>nul
del /Q fix_split_studies.php 2>nul
del /Q fix_study_separation.php 2>nul
del /Q fix_study_uid.php 2>nul
del /Q fix_study_uids.php 2>nul
del /Q insert_study_direct.php 2>nul
del /Q perfect_reset.php 2>nul
del /Q simple_reset.php 2>nul
del /Q ultimate_reset.php 2>nul
del /Q study_diagnostic.php 2>nul
del /Q clear_instances.sql 2>nul
del /Q update_database.php 2>nul
echo   Deleted database fix scripts >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE DUPLICATE SYNC FILES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [3/10] Deleting duplicate sync files...
del /Q auto_sync_trigger.php.DISABLED 2>nul
del /Q sync_all_cron.php 2>nul
del /Q sync_from_orthanc.php 2>nul
del /Q sync_from_orthanc_fixed.php 2>nul
del /Q sync_database.php 2>nul
del /Q SYNC_DATABASE.bat 2>nul
del /Q SYNC_FIXED.html 2>nul
del /Q FORCE_SYNC_ALL.bat 2>nul
del /Q sync_api.php 2>nul
echo   Deleted duplicate sync files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE OLD SERVICE SCRIPTS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [4/10] Deleting old service scripts...
del /Q AUTO_SYNC_SERVICE.bat 2>nul
del /Q COMPLETE_SETUP.bat 2>nul
del /Q DISABLE_SERVICES_AND_START.bat 2>nul
del /Q INSTALL_APPLICATION_FILES.bat 2>nul
del /Q INSTALL_AUTO_START.bat 2>nul
del /Q INSTALL_SERVICES.bat 2>nul
del /Q UNINSTALL_AUTO_START.bat 2>nul
del /Q UNINSTALL_SERVICES.bat 2>nul
del /Q REMOVE_OLD_SERVICES.bat 2>nul
del /Q ngrok_service.bat 2>nul
echo   Deleted old service scripts >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE DUPLICATE START/STOP SCRIPTS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [5/10] Deleting duplicate start/stop scripts...
del /Q START_ALL_SERVICES.bat 2>nul
del /Q START_APACHE_AND_TEST.bat 2>nul
del /Q START_EVERYTHING_CORRECTLY.bat 2>nul
del /Q START_GATEWAY.bat 2>nul
del /Q START_HERE.html 2>nul
del /Q START_HIDDEN.vbs 2>nul
del /Q START_NGROK_HIDDEN_FIXED.vbs 2>nul
del /Q START_NGROK_HIDDEN_V2.vbs 2>nul
del /Q START_NGROK_NOW.bat 2>nul
del /Q START_NGROK_STATIC.bat 2>nul
del /Q START_NGROK_TUNNEL.bat 2>nul
del /Q start_ngrok_wrapper.bat 2>nul
del /Q START_SERVICES_SIMPLE.bat 2>nul
del /Q SIMPLE_NGROK_START.bat 2>nul
del /Q STOP_EVERYTHING.bat 2>nul
del /Q STOP_TASK_AND_RESTART.bat 2>nul
del /Q CLEAN_RESTART_GATEWAY.bat 2>nul
del /Q RESTART_GATEWAY.bat 2>nul
del /Q RESTART_NGROK.bat 2>nul
del /Q RESTART_SERVICES.bat 2>nul
echo   Deleted duplicate start/stop scripts >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE TEST SCRIPTS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [6/10] Deleting test scripts...
del /Q test_db_connection.php 2>nul
del /Q test_gateway_direct.bat 2>nul
del /Q TEST_GATEWAY_ROUTES.bat 2>nul
del /Q TEST_LOGIN.php 2>nul
del /Q TEST_LOGIN_DIRECT.php 2>nul
del /Q TEST_NEW_ENDPOINT.bat 2>nul
del /Q TEST_NGROK_CONFIG.bat 2>nul
del /Q TEST_PYTHON_SCRIPT.bat 2>nul
del /Q debug_config.php 2>nul
del /Q debug_instances.php 2>nul
del /Q quick_diagnostic.php 2>nul
echo   Deleted test scripts >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE INSTALLER FILES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [7/10] Deleting installer files...
del /Q HOSPITAL_COMPLETE_INSTALLER.bat 2>nul
del /Q HOSPITAL_COMPLETE_INSTALLER_ENHANCED.bat 2>nul
del /Q HOSPITAL_INSTALLER.bat 2>nul
del /Q HOSPITAL_INSTALLER_FIXED.bat 2>nul
del /Q HOSPITAL_INSTALLATION_README.txt 2>nul
del /Q COPY_TO_HOSPITAL.bat 2>nul
del /Q COPY_WORKING_FILES_TO_HOSPITAL.bat 2>nul
del /Q CREATE_ALL_FILES.ps1 2>nul
del /Q CREATE_SYNC_FILES.bat 2>nul
echo   Deleted installer files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE OLD DOCUMENTATION
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [8/10] Deleting old documentation...
del /Q AUTO_START_INSTRUCTIONS.txt 2>nul
del /Q COMPLETE_FIX.txt 2>nul
del /Q COMPLETE_FIX_GUIDE.md 2>nul
del /Q FIX_STUDIES_NOT_SHOWING.md 2>nul
del /Q INSTALLATION_PACKAGE_README.md 2>nul
del /Q NGROK_STATIC_DOMAIN_GUIDE.txt 2>nul
del /Q PERMANENT_URL_SOLUTION.txt 2>nul
del /Q README.md 2>nul
del /Q README.txt 2>nul
del /Q README_FIX_SUMMARY.md 2>nul
del /Q REMOTE_RESET_SETUP.txt 2>nul
del /Q RESET_GUIDE.txt 2>nul
del /Q SCHEMA_FIX_GUIDE.md 2>nul
del /Q SETUP_GUIDE.md 2>nul
del /Q SIMPLE_MANUAL_START.txt 2>nul
del /Q TROUBLESHOOTING_PORT_CONFLICTS.md 2>nul
del /Q UPLOAD_TO_CPANEL.txt 2>nul
del /Q CHECK_NGROK_STATUS.bat 2>nul
del /Q CHECK_NGROK_TASK.bat 2>nul
del /Q GET_NGROK_URL.bat 2>nul
del /Q SETUP_NGROK_STATIC_DOMAIN.bat 2>nul
del /Q SETUP_AUTOSTART_TASK.bat 2>nul
del /Q SETUP_STARTUP_FOLDER.bat 2>nul
del /Q UPDATE_SERVICE_STATIC_DOMAIN.bat 2>nul
del /Q CONFIGURE_MODALITIES.bat 2>nul
del /Q KILL_SERVICE_BY_PID.bat 2>nul
echo   Deleted old documentation >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE REMOTE CLOUD SYNC
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [9/10] Deleting remote cloud sync files...
del /Q remote_cloud_sync.py 2>nul
del /Q remote_reset_handler.php 2>nul
del /Q remote_sync_sender.php 2>nul
del /Q start_cloud_sync.vbs 2>nul
del /Q config_production.php 2>nul
echo   Deleted remote cloud sync files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE OLD API FILES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo [10/10] Deleting old API files...
del /Q api\auto_download_files.php 2>nul
del /Q api\debug_studies.php 2>nul
del /Q api\find_dicom_files.php 2>nul
del /Q api\force_download_all.php 2>nul
del /Q api\get_dicom_from_storage.php 2>nul
del /Q api\get_dicom_orthanc.php 2>nul
del /Q api\get_studies_direct.php 2>nul
del /Q api\get_study_instances.php 2>nul
del /Q api\get_study_metadata.php 2>nul
del /Q api\orthanc_proxy.php 2>nul
del /Q api\populate_from_storage.php 2>nul
del /Q api\receive_dicom.php 2>nul
del /Q api\remote_sync_receiver.php 2>nul
del /Q api\sync_api.php 2>nul
del /Q api\sync_metadata.php 2>nul
del /Q api\update_instance_path.php 2>nul
del /Q api\upload_dicom_to_remote.php 2>nul
echo   Deleted old API files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE PRESCRIPTION/REPORT FILES (if not using)
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo.
echo Do you use the Prescription/Report features? (y/n)
set /p USE_REPORTS="Enter y to keep, n to delete: "
if /i "%USE_REPORTS%"=="n" (
    echo Deleting prescription/report files...
    del /Q api\get_prescription.php 2>nul
    del /Q api\save_prescription.php 2>nul
    del /Q api\get_study_report.php 2>nul
    del /Q get_measurements.php 2>nul
    del /Q get_notes.php 2>nul
    del /Q get_report_summary.php 2>nul
    del /Q list_reports.php 2>nul
    del /Q load_report.php 2>nul
    del /Q save_notes.php 2>nul
    del /Q save_report.php 2>nul
    del /Q setup_prescriptions_table.php 2>nul
    del /Q toggle_star.php 2>nul
    echo   Deleted prescription/report files >> "%LOG%"
)

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE OTHER OLD FILES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo.
echo Deleting other old files...
del /Q auto_download_files.php 2>nul
del /Q download_missing.php 2>nul
del /Q enable_sync.php 2>nul
del /Q fix_missing_files.php 2>nul
del /Q get_dicom.php 2>nul
del /Q get_dicom_fast.php 2>nul
del /Q get_dicom_file.php 2>nul
del /Q upload.php 2>nul
del /Q create_database.sql 2>nul
del /Q dicom.sql 2>nul
del /Q run_gateway.bat 2>nul
echo   Deleted other old files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE LOG FILES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo.
echo Deleting log files...
del /Q dicom_api_gateway.log 2>nul
del /Q error_log 2>nul
del /Q api\error_log 2>nul
del /Q ngrok_info.json 2>nul
del /Q nul 2>nul
echo   Deleted log files >> "%LOG%"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DELETE CACHE/STORAGE DIRECTORIES
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo.
echo Do you want to delete cache and storage directories? (y/n)
echo WARNING: This will delete DICOM files and cached data!
set /p DELETE_STORAGE="Enter y to delete, n to keep: "
if /i "%DELETE_STORAGE%"=="y" (
    echo Deleting cache and storage...
    rmdir /S /Q dicom_cache 2>nul
    rmdir /S /Q dicom_files 2>nul
    rmdir /S /Q logs 2>nul
    rmdir /S /Q reports 2>nul
    echo   Deleted cache/storage directories >> "%LOG%"
)

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: CLEANUP COMPLETE
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo.
echo Cleanup completed: %date% %time% >> "%LOG%"
echo.
echo ========================================================================
echo   CLEANUP COMPLETE!
echo ========================================================================
echo.
echo Files deleted successfully.
echo.
echo Remaining files are ESSENTIAL for operation:
echo   - Configuration and core PHP files
echo   - Python gateway
echo   - API endpoints
echo   - Authentication system
echo   - HTML pages
echo   - CSS and JavaScript
echo   - Working utility scripts
echo.
echo Check CLEANUP_LOG.txt for details.
echo.
echo Your directory is now clean and ready for:
echo   1. Zipping for backup
echo   2. Copying to hospital PC
echo   3. Easy deployment
echo.
pause
