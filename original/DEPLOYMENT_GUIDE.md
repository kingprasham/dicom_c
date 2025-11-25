# Deployment Guide - Upload to e-connect.in/dicom/

## Files to Upload/Replace

### 1. **index.php** (REPLACE)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/index.php`

This is the main DICOM viewer file with all mobile optimizations.

**Changes:**
- Added mobile-responsive CSS (lines 14-250)
- Added mobile viewport meta tags (lines 6-8)
- Added mobile toolbar HTML (lines 425-447)
- Added image thumbnails container (lines 449-452)
- Added sidebar toggle button (line 353-355)
- Added mobile-controls.js script (line 447)

**Upload:** `c:\xampp\htdocs\papa\dicom_again\index.php`

---

### 2. **mobile-controls.js** (NEW FILE - CREATE)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/js/components/mobile-controls.js`

This handles all mobile-specific functionality.

**Upload:** `c:\xampp\htdocs\papa\dicom_again\js\components\mobile-controls.js`

---

### 3. **pages/patients.html** (REPLACE)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/pages/patients.html`

Updated with auto-refresh and sync functionality.

**Changes:**
- Added auto-refresh every 5 minutes (line 370-373)
- Added sync API integration (line 490-523)
- Enhanced refresh button functionality (line 365-367)

**Upload:** `c:\xampp\htdocs\papa\dicom_again\pages\patients.html`

---

### 4. **api/sync_orthanc_api.php** (NEW FILE - CREATE)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/api/sync_orthanc_api.php`

JSON API endpoint for programmatic syncing.

**Upload:** `c:\xampp\htdocs\papa\dicom_again\api\sync_orthanc_api.php`

---

### 5. **sync_orthanc.php** (REPLACE)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/sync_orthanc.php`

Modified to sync directly from Orthanc without dicom_instances check.

**Changes:**
- Removed dicom_instances dependency (lines 117-121)
- Updated sync strategy message (line 23)
- Updated completion messages (lines 240, 254-255)

**Upload:** `c:\xampp\htdocs\papa\dicom_again\sync_orthanc.php`

---

### 6. **dashboard.php** (NEW FILE - CREATE - OPTIONAL)
**Path:** `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/dashboard.php`

System overview dashboard.

**Upload:** `c:\xampp\htdocs\papa\dicom_again\dashboard.php`

---

## Deployment Steps

### Step 1: Backup Production Files

```bash
# SSH into server
ssh your_username@your_server

# Navigate to dicom directory
cd /home/odthzxeg2ajv/public_html/e-connect.in/dicom/

# Create backup
mkdir -p backups/$(date +%Y%m%d)
cp index.php backups/$(date +%Y%m%d)/
cp pages/patients.html backups/$(date +%Y%m%d)/
cp sync_orthanc.php backups/$(date +%Y%m%d)/
```

### Step 2: Upload Files via FTP/SFTP

Using FileZilla, WinSCP, or similar:

**Upload these files:**

1. **index.php**
   - Local: `c:\xampp\htdocs\papa\dicom_again\index.php`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/index.php`

2. **mobile-controls.js**
   - Local: `c:\xampp\htdocs\papa\dicom_again\js\components\mobile-controls.js`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/js/components/mobile-controls.js`

3. **patients.html**
   - Local: `c:\xampp\htdocs\papa\dicom_again\pages\patients.html`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/pages/patients.html`

4. **sync_orthanc_api.php**
   - Local: `c:\xampp\htdocs\papa\dicom_again\api\sync_orthanc_api.php`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/api/sync_orthanc_api.php`

5. **sync_orthanc.php**
   - Local: `c:\xampp\htdocs\papa\dicom_again\sync_orthanc.php`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/sync_orthanc.php`

6. **dashboard.php** (optional)
   - Local: `c:\xampp\htdocs\papa\dicom_again\dashboard.php`
   - Remote: `/home/odthzxeg2ajv/public_html/e-connect.in/dicom/dashboard.php`

### Step 3: Set Correct Permissions

```bash
# SSH into server
chmod 644 index.php
chmod 644 pages/patients.html
chmod 644 sync_orthanc.php
chmod 644 api/sync_orthanc_api.php
chmod 644 js/components/mobile-controls.js
chmod 644 dashboard.php

# Ensure directories are accessible
chmod 755 js/components/
chmod 755 api/
```

### Step 4: Clear Cache

```bash
# Clear PHP opcode cache if enabled
# If using OPcache:
# Service apache2 reload

# Or via cPanel:
# Go to Software > Select PHP Version > Extensions > Toggle OPcache off/on
```

### Step 5: Test Production

**Test URLs:**

1. **Dashboard:**
   ```
   https://e-connect.in/dicom/dashboard.php
   ```

2. **Patient List:**
   ```
   https://e-connect.in/dicom/pages/patients.html
   ```

3. **Sync:**
   ```
   https://e-connect.in/dicom/sync_orthanc.php
   ```

4. **DICOM Viewer (desktop):**
   ```
   https://e-connect.in/dicom/index.php?studyUID=XXX&orthancId=XXX
   ```

5. **DICOM Viewer (mobile):**
   - Open on mobile device
   - Check bottom toolbar appears
   - Test touch gestures
   - Test fullscreen mode

---

## Quick Upload Method (Using cPanel File Manager)

1. **Login to cPanel**
   - Go to your hosting control panel
   - Navigate to File Manager

2. **Navigate to Directory**
   - Go to: `/public_html/e-connect.in/dicom/`

3. **Upload Files**
   - Click "Upload" button
   - Select files from your local machine:
     - `index.php`
     - `sync_orthanc.php`
     - `dashboard.php`

4. **Navigate to Subdirectories**
   - Go to `pages/` folder
     - Upload `patients.html`

   - Go to `api/` folder
     - Upload `sync_orthanc_api.php`

   - Go to `js/components/` folder
     - Upload `mobile-controls.js`

5. **Verify Uploads**
   - Check each file uploaded successfully
   - Verify file sizes match local files

---

## File Checklist

### Required Files (Must Upload):
- [x] **index.php** - Mobile-responsive viewer
- [x] **js/components/mobile-controls.js** - Mobile functionality
- [x] **pages/patients.html** - Auto-refresh patient list
- [x] **api/sync_orthanc_api.php** - Sync API endpoint
- [x] **sync_orthanc.php** - Updated sync script

### Optional Files:
- [ ] **dashboard.php** - System dashboard
- [ ] **check_db_state.php** - Database checker
- [ ] **MOBILE_GUIDE.md** - Mobile documentation

### Don't Upload (Localhost Only):
- ❌ **QUICK_START.txt** - Local reference
- ❌ **SETUP_INSTRUCTIONS.md** - Local setup guide
- ❌ **config.php** - Already exists on production with different settings
- ❌ Any `.bat` files - Windows scripts

---

## Post-Deployment Verification

### 1. Test Desktop View
```
https://e-connect.in/dicom/index.php?studyUID=XXX&orthancId=XXX
```
**Check:**
- [ ] Page loads without errors
- [ ] All sidebars visible
- [ ] Tools panel works
- [ ] Images load correctly
- [ ] MPR controls visible

### 2. Test Mobile View (Chrome DevTools)
1. Open viewer URL
2. Press F12
3. Click device toolbar (Ctrl+Shift+M)
4. Select mobile device (iPhone 12, Galaxy S21, etc.)

**Check:**
- [ ] Bottom toolbar visible
- [ ] Only essential controls shown
- [ ] Pan/Zoom/W-L tools work
- [ ] "Images" button shows thumbnails
- [ ] Fullscreen button works
- [ ] Touch gestures responsive

### 3. Test Mobile View (Real Device)
**On iPhone/Android:**
- [ ] Open patients.html
- [ ] Select patient and study
- [ ] Tap "Open" to load viewer
- [ ] Test bottom toolbar buttons
- [ ] Try pinch-to-zoom
- [ ] Test fullscreen mode
- [ ] Rotate device (portrait/landscape)
- [ ] Check image selection

### 4. Test Patient List
```
https://e-connect.in/dicom/pages/patients.html
```
**Check:**
- [ ] Patients display correctly
- [ ] "Refresh Data" button works
- [ ] Sync shows status message
- [ ] Auto-refresh works (wait 5 minutes)

### 5. Test Sync
```
https://e-connect.in/dicom/sync_orthanc.php
```
**Check:**
- [ ] Connects to Orthanc
- [ ] Syncs patients and studies
- [ ] Shows success message
- [ ] "Go to Patients Page" link works

---

## Troubleshooting

### Issue: Mobile toolbar not showing

**Check:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Verify `mobile-controls.js` uploaded correctly
3. Check browser console for errors (F12)
4. Verify file path in index.php line 447

**Fix:**
```javascript
// In browser console, check if mobile controls loaded:
console.log(window.MobileControls);
// Should show object, not undefined
```

### Issue: Images button shows no thumbnails

**Cause:** JavaScript not loading image stack

**Fix:**
- Check if images are loaded in main viewer
- Verify `window.APP_STATE` exists
- Check console for errors

### Issue: Fullscreen not working on mobile

**Cause:** iOS/Safari limitations

**Solution:**
- User must interact with page first
- Some browsers require touch before fullscreen
- Try tapping viewport before fullscreen

### Issue: Touch gestures not responding

**Check:**
1. Correct tool selected (should be highlighted blue)
2. Touching viewport, not sidebar
3. Browser supports touch events

**Fix:**
```javascript
// Test touch support in console:
console.log('ontouchstart' in window);
// Should return true on touch devices
```

### Issue: Sync API returns 401 Unauthorized

**Cause:** Session not authenticated

**Fix:**
1. Login at pages/login.html
2. Then test sync
3. Check session timeout (1 hour)

### Issue: CSS not applying on mobile

**Cause:** Browser cache or stylesheet conflict

**Fix:**
1. Hard refresh (Ctrl+Shift+R)
2. Clear browser cache
3. Check media queries in DevTools
4. Verify viewport meta tag present

---

## Environment Differences

### Localhost Configuration
```php
ORTHANC_URL: http://localhost:8042
USE_API_GATEWAY: false
DB_USER: root
DB_PASS: (empty)
```

### Production Configuration
```php
ORTHANC_URL: (via API gateway)
USE_API_GATEWAY: true
API_GATEWAY_URL: https://brendon-interannular-nonconnectively.ngrok-free.dev/
DB_USER: acc_admin
DB_PASS: Prasham123$
```

**Important:** Don't upload `config.php` - it auto-detects environment!

---

## Rollback Plan

If issues occur after deployment:

### Quick Rollback
```bash
# SSH into server
cd /home/odthzxeg2ajv/public_html/e-connect.in/dicom/

# Restore from backup
cp backups/YYYYMMDD/index.php ./
cp backups/YYYYMMDD/pages/patients.html pages/
cp backups/YYYYMMDD/sync_orthanc.php ./

# Remove new files
rm js/components/mobile-controls.js
rm api/sync_orthanc_api.php
```

### Verify Rollback
```
https://e-connect.in/dicom/index.php
```
Should show old version without mobile features.

---

## Success Criteria

Deployment is successful when:

✅ **Desktop View:**
- All features work as before
- No broken functionality
- Images load correctly
- Tools work normally

✅ **Mobile View:**
- Bottom toolbar visible on mobile devices
- Touch gestures work (pan, zoom, W-L)
- Fullscreen mode works
- Image thumbnails accessible
- No JavaScript errors

✅ **Patient List:**
- Auto-refresh works
- Sync button works
- Data displays correctly

✅ **Performance:**
- Page loads in < 3 seconds
- Images load smoothly
- No lag in touch gestures
- Responsive on all devices

---

## Support

After deployment, monitor:

1. **Server Logs**
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. **PHP Errors**
   - Check error_log in dicom directory
   - Enable error reporting temporarily if needed

3. **Browser Console**
   - Check for JavaScript errors
   - Monitor network requests

4. **User Feedback**
   - Test on multiple devices
   - Different browsers
   - Various screen sizes

---

## Final Checklist

Before going live:

- [ ] Backup all files
- [ ] Upload all required files
- [ ] Set correct permissions
- [ ] Test desktop view
- [ ] Test mobile view (DevTools)
- [ ] Test on real mobile device
- [ ] Test patient list
- [ ] Test sync functionality
- [ ] Clear all caches
- [ ] Monitor for errors
- [ ] Document any issues
- [ ] Inform users of new features

---

**Deployment Time:** ~15-30 minutes

**Risk Level:** Low (mobile features are additive, desktop unchanged)

**Recommended Time:** Off-peak hours or maintenance window

---

**Note:** The mobile features are designed to be non-breaking. Desktop users will see no difference, while mobile users get enhanced experience automatically.
