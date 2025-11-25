# Hospital DICOM Viewer Pro v2.0 - Complete Testing Checklist

## 🧪 Comprehensive Testing Guide

This document provides a complete testing checklist to ensure all features work correctly before production deployment.

---

## Testing Environment Setup

### Prerequisites
- [ ] XAMPP running (Apache + MySQL)
- [ ] Orthanc running on port 8042
- [ ] Database schema imported
- [ ] Application files deployed
- [ ] Test DICOM files available

---

## 1. Authentication & Authorization Tests

### 1.1 Login Functionality
- [ ] **Test 1:** Login with admin credentials (admin / Admin@123)
  - Expected: Successful login, redirect to dashboard

- [ ] **Test 2:** Login with radiologist credentials
  - Expected: Successful login, redirect to dashboard

- [ ] **Test 3:** Login with wrong password
  - Expected: Error message "Invalid username or password"

- [ ] **Test 4:** Login with non-existent user
  - Expected: Error message "Invalid username or password"

### 1.2 Session Management
- [ ] **Test 5:** Check session timeout (default: 8 hours)
  - Expected: Auto-logout after SESSION_LIFETIME

- [ ] **Test 6:** Logout functionality
  - Expected: Session destroyed, redirect to login

- [ ] **Test 7:** Access protected page without login
  - Expected: Redirect to login page

### 1.3 Role-Based Access
- [ ] **Test 8:** Admin access to admin panel
  - Expected: Full access to all admin features

- [ ] **Test 9:** Non-admin access to admin panel
  - Expected: Access denied or features hidden

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 2. DICOMweb Integration Tests

### 2.1 Orthanc Connectivity
- [ ] **Test 10:** Access Orthanc web interface
  - URL: http://localhost:8042
  - Expected: Orthanc Explorer loads

- [ ] **Test 11:** DICOMweb plugin enabled
  - URL: http://localhost:8042/dicom-web/
  - Expected: DICOMweb endpoint accessible

### 2.2 API Endpoints
- [ ] **Test 12:** Query studies endpoint
  - API: `/api/dicomweb/studies.php`
  - Expected: Returns JSON array of studies

- [ ] **Test 13:** Get study metadata
  - API: `/api/dicomweb/study-metadata.php?studyUID={uid}`
  - Expected: Returns study metadata JSON

- [ ] **Test 14:** Get series
  - API: `/api/dicomweb/series.php?studyUID={uid}`
  - Expected: Returns array of series

- [ ] **Test 15:** Get instances
  - API: `/api/dicomweb/instances.php?seriesUID={uid}`
  - Expected: Returns array of instances

- [ ] **Test 16:** Get instance file (WADO-RS)
  - API: `/api/dicomweb/instance-file.php?instanceUID={uid}`
  - Expected: Returns DICOM file

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 3. DICOM Viewer Tests

### 3.1 Image Loading
- [ ] **Test 17:** Upload DICOM file
  - Upload test CT scan
  - Expected: File appears in Orthanc

- [ ] **Test 18:** Load study in viewer
  - Click on study from list
  - Expected: Images load in viewport

- [ ] **Test 19:** Image displays correctly
  - Expected: Medical image visible, no errors

- [ ] **Test 20:** Multiple instances load
  - Study with 100+ images
  - Expected: All instances load, smooth scrolling

### 3.2 Viewport Layouts
- [ ] **Test 21:** 1x1 layout
  - Expected: Single viewport fills screen

- [ ] **Test 22:** 2x1 layout
  - Expected: Two viewports side-by-side

- [ ] **Test 23:** 2x2 layout
  - Expected: Four viewports in grid

### 3.3 Navigation
- [ ] **Test 24:** Series selection
  - Click different series
  - Expected: Series loads in viewport

- [ ] **Test 25:** Stack scrolling (mouse wheel)
  - Expected: Navigate through image stack

- [ ] **Test 26:** Stack slider
  - Expected: Slider updates image index

- [ ] **Test 27:** Cine mode
  - Play/pause, FPS control
  - Expected: Images play in sequence

### 3.4 Image Manipulation
- [ ] **Test 28:** Pan (middle mouse / drag)
  - Expected: Image moves within viewport

- [ ] **Test 29:** Zoom (scroll / pinch)
  - Expected: Image zooms in/out

- [ ] **Test 30:** Window/Level (drag)
  - Expected: Brightness/contrast adjusts

- [ ] **Test 31:** Rotate
  - Expected: Image rotates 90 degrees

- [ ] **Test 32:** Flip horizontal
  - Expected: Image flips

- [ ] **Test 33:** Flip vertical
  - Expected: Image flips

- [ ] **Test 34:** Invert
  - Expected: Colors inverted

### 3.5 Window/Level Presets
- [ ] **Test 35:** Lung preset
  - Expected: W/L optimized for lung tissue

- [ ] **Test 36:** Abdomen preset
  - Expected: W/L optimized for abdomen

- [ ] **Test 37:** Brain preset
  - Expected: W/L optimized for brain

- [ ] **Test 38:** Bone preset
  - Expected: W/L optimized for bone

- [ ] **Test 39:** Auto W/L
  - Expected: Automatically calculates optimal W/L

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 4. MPR (Multi-Planar Reconstruction) Tests

### 4.1 MPR Activation
- [ ] **Test 40:** Enable MPR mode
  - Expected: Three orthogonal views appear

### 4.2 MPR Views
- [ ] **Test 41:** Axial view
  - Expected: Horizontal cross-section

- [ ] **Test 42:** Sagittal view
  - Expected: Vertical left-right cross-section

- [ ] **Test 43:** Coronal view
  - Expected: Vertical front-back cross-section

### 4.3 MPR Interaction
- [ ] **Test 44:** Crosshair sync
  - Click on one view
  - Expected: Crosshairs update in all views

- [ ] **Test 45:** Reference lines
  - Expected: Lines show slice position

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 5. Measurement Tools Tests

### 5.1 Length Measurement
- [ ] **Test 46:** Draw length measurement
  - Expected: Line drawn, distance displayed

- [ ] **Test 47:** Save measurement
  - Expected: Saved to database

- [ ] **Test 48:** Load measurement
  - Reload same image
  - Expected: Measurement appears

### 5.2 Angle Measurement
- [ ] **Test 49:** Draw angle measurement
  - Expected: Angle drawn, degrees displayed

### 5.3 ROI Tools
- [ ] **Test 50:** Rectangle ROI
  - Expected: Rectangle drawn, stats displayed

- [ ] **Test 51:** Elliptical ROI
  - Expected: Ellipse drawn, stats displayed

- [ ] **Test 52:** Freehand ROI
  - Expected: Custom shape, stats displayed

### 5.4 Probe Tool
- [ ] **Test 53:** Probe measurement
  - Expected: Pixel value displayed

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 6. Medical Reporting Tests

### 6.1 Report Creation
- [ ] **Test 54:** Create new report
  - Select template (CT Head)
  - Expected: Template loads with sections

- [ ] **Test 55:** Fill report sections
  - Indication, Technique, Findings, Impression
  - Expected: Text saves

### 6.2 Report Management
- [ ] **Test 56:** Save report draft
  - Expected: Status = draft, saved to database

- [ ] **Test 57:** Finalize report
  - Expected: Status = final, timestamp set

- [ ] **Test 58:** Load existing report
  - Expected: Report data populates form

- [ ] **Test 59:** Update report
  - Expected: New version created

### 6.3 Report Templates
- [ ] **Test 60:** CT Head template
- [ ] **Test 61:** CT Chest template
- [ ] **Test 62:** CT Abdomen template
- [ ] **Test 63:** MRI Brain template
- [ ] **Test 64:** X-Ray Chest template

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 7. Clinical Notes Tests

### 7.1 Note Creation
- [ ] **Test 65:** Create clinical history note
  - Expected: Note saved to database

- [ ] **Test 66:** Create series note
  - Expected: Associated with series

- [ ] **Test 67:** Create image note
  - Expected: Associated with specific image

### 7.2 Note Management
- [ ] **Test 68:** Load notes for study
  - Expected: All notes displayed

- [ ] **Test 69:** Edit note
  - Expected: Note updated

- [ ] **Test 70:** Delete note
  - Expected: Note removed

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 8. Export & Print Tests

### 8.1 Export
- [ ] **Test 71:** Export to PNG
  - Expected: PNG file downloaded

- [ ] **Test 72:** Export to PDF
  - Expected: PDF with images/annotations

- [ ] **Test 73:** Export DICOM
  - Expected: Original DICOM file

### 8.2 Print
- [ ] **Test 74:** Print current view
  - Expected: Print dialog with preview

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 9. Mobile Responsiveness Tests

### 9.1 Mobile UI
- [ ] **Test 75:** Access on smartphone
  - Expected: Mobile-optimized layout

- [ ] **Test 76:** Access on tablet
  - Expected: Tablet-optimized layout

### 9.2 Touch Gestures
- [ ] **Test 77:** Touch pan
  - Expected: Image pans with finger drag

- [ ] **Test 78:** Pinch zoom
  - Expected: Image zooms

- [ ] **Test 79:** Touch W/L
  - Expected: Brightness adjusts

### 9.3 Mobile Controls
- [ ] **Test 80:** Bottom toolbar
  - Expected: Tool buttons accessible

- [ ] **Test 81:** Collapsible sidebar
  - Expected: Sidebar slides up/down

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 10. Hospital Data Import Tests

### 10.1 Directory Scanning
- [ ] **Test 82:** Configure hospital data path
  - Path: D:\Hospital\DICOM\
  - Expected: Path saved

- [ ] **Test 83:** Scan directory
  - Expected: DICOM files detected

- [ ] **Test 84:** File count accurate
  - Expected: Matches actual file count

### 10.2 Import Process
- [ ] **Test 85:** Start import job
  - Expected: Job created in database

- [ ] **Test 86:** Monitor import progress
  - Expected: Real-time progress updates

- [ ] **Test 87:** Import completes successfully
  - Expected: All files imported to Orthanc

### 10.3 Continuous Monitoring
- [ ] **Test 88:** Enable monitoring
  - Expected: Service starts checking for new files

- [ ] **Test 89:** Add new file to directory
  - Expected: Auto-imported within 30 seconds

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 11. Automated Sync Tests

### 11.1 FTP Configuration
- [ ] **Test 90:** Enter FTP credentials
  - Expected: Credentials saved (encrypted)

- [ ] **Test 91:** Test FTP connection
  - Expected: Connection successful

### 11.2 Manual Sync
- [ ] **Test 92:** Trigger manual sync
  - Expected: Files uploaded to FTP

- [ ] **Test 93:** Verify files on GoDaddy
  - Expected: Files present on server

### 11.3 Automated Sync
- [ ] **Test 94:** Enable auto-sync
  - Expected: Sync service starts

- [ ] **Test 95:** Wait 2 minutes
  - Expected: Automatic sync occurs

- [ ] **Test 96:** Check sync history
  - Expected: Sync recorded in database

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 12. Google Drive Backup Tests

### 12.1 OAuth Configuration
- [ ] **Test 97:** Enter Google credentials
  - Expected: Credentials saved

- [ ] **Test 98:** Complete OAuth flow
  - Expected: Authorized, refresh token saved

- [ ] **Test 99:** Test connection
  - Expected: Connection successful

### 12.2 Manual Backup
- [ ] **Test 100:** Trigger manual backup
  - Expected: Backup created

- [ ] **Test 101:** Verify backup in Drive
  - Expected: ZIP file in DICOM_Viewer_Backups folder

- [ ] **Test 102:** Check backup contents
  - Expected: Contains database SQL + files

### 12.3 Scheduled Backup
- [ ] **Test 103:** Set daily backup schedule
  - Expected: Schedule saved

- [ ] **Test 104:** Wait for scheduled time
  - Expected: Backup runs automatically

### 12.4 Restore
- [ ] **Test 105:** Download backup
  - Expected: ZIP downloads from Drive

- [ ] **Test 106:** Restore database
  - Expected: Database restored successfully

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 13. NSSM Services Tests

### 13.1 Service Installation
- [ ] **Test 107:** Install services via setup script
  - Expected: 3 services installed

### 13.2 Service Status
- [ ] **Test 108:** DicomViewer_Data_Monitor running
  - Command: `sc query DicomViewer_Data_Monitor`
  - Expected: STATE: RUNNING

- [ ] **Test 109:** DicomViewer_FTP_Sync running
  - Expected: STATE: RUNNING

- [ ] **Test 110:** DicomViewer_GDrive_Backup running
  - Expected: STATE: RUNNING

### 13.3 Service Logs
- [ ] **Test 111:** Check monitor-service.log
  - Expected: No errors, monitoring active

- [ ] **Test 112:** Check sync-service.log
  - Expected: Sync operations logged

- [ ] **Test 113:** Check backup-service.log
  - Expected: Backup operations logged

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 14. Performance Tests

### 14.1 Load Time
- [ ] **Test 114:** Study list loads < 2 seconds
  - Expected: Fast response

- [ ] **Test 115:** First image displays < 3 seconds
  - Expected: Quick initial render

### 14.2 Large Dataset
- [ ] **Test 116:** Load study with 500+ images
  - Expected: Loads without freezing

- [ ] **Test 117:** Scroll through 500+ images
  - Expected: Smooth scrolling

### 14.3 Memory Usage
- [ ] **Test 118:** Monitor RAM usage
  - Expected: < 2 GB for typical study

- [ ] **Test 119:** No memory leaks
  - Load/unload multiple studies
  - Expected: Memory returns to baseline

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 15. Security Tests

### 15.1 SQL Injection
- [ ] **Test 120:** Try SQL injection in login
  - Input: `admin' OR '1'='1`
  - Expected: Login fails, no database error

### 15.2 XSS Prevention
- [ ] **Test 121:** Try XSS in report
  - Input: `<script>alert('XSS')</script>`
  - Expected: Escaped, no script execution

### 15.3 Session Security
- [ ] **Test 122:** Session hijacking prevention
  - Expected: Session tied to IP address

- [ ] **Test 123:** CSRF protection
  - Expected: Forms require CSRF token (if implemented)

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## 16. Production Deployment Tests

### 16.1 Path Resolution
- [ ] **Test 124:** Deploy to subdirectory
  - URL: http://localhost/subfolder/
  - Expected: All paths resolve correctly

- [ ] **Test 125:** Deploy to domain root
  - URL: https://hospital.com/
  - Expected: All paths resolve correctly

### 16.2 CORS Configuration
- [ ] **Test 126:** Cross-origin requests
  - Expected: CORS headers allow access

### 16.3 Error Handling
- [ ] **Test 127:** Database connection fails
  - Expected: Graceful error message

- [ ] **Test 128:** Orthanc unavailable
  - Expected: User-friendly error

**Test Results:**
```
[  ] All Passed
[  ] Failed: _______________
```

---

## Summary

### Test Statistics
```
Total Tests: 128
Passed: ___ / 128
Failed: ___ / 128
Skipped: ___ / 128

Pass Rate: ____%
```

### Critical Issues Found
```
1. ____________________________
2. ____________________________
3. ____________________________
```

### Non-Critical Issues
```
1. ____________________________
2. ____________________________
3. ____________________________
```

### Recommendations
```
1. ____________________________
2. ____________________________
3. ____________________________
```

---

## Sign-Off

**Tested By:** _____________________
**Date:** _____________________
**Version:** 2.0.0
**Status:** ☐ Approved ☐ Rejected ☐ Needs Fixes

**Notes:**
```
_________________________________________________
_________________________________________________
_________________________________________________
```

---

## Next Steps

### If All Tests Pass:
1. Deploy to production
2. Configure MRI/CT machines
3. Train hospital staff
4. Monitor for 1 week
5. Full production release

### If Tests Fail:
1. Document failures
2. Create bug tickets
3. Fix issues
4. Re-test
5. Repeat until all pass

---

**End of Testing Checklist**
