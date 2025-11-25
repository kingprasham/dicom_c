# JavaScript API Path Fix Guide

## Overview
This guide shows how to update all JavaScript fetch() calls to use BASE_PATH for production deployment.

---

## Quick Fix Pattern

### Current Problem
```javascript
// Hardcoded paths - won't work in subfolders
fetch('api/some-endpoint.php')
fetch('../api/some-endpoint.php')
fetch('upload.php')
fetch('auth/login.php')
```

### Solution
```javascript
// Dynamic paths using window.basePath
const basePath = window.basePath || '';
fetch(`${basePath}/api/some-endpoint.php`)
```

---

## Files Requiring Updates

### 1. js/main.js
**Current Issues:**
```javascript
Line 166: fetch(`pacs_search.php?action=search&...`)
Line 223: fetch(`pacs_search.php?action=load_study&...`)
Line 599: fetch(`get_dicom_fast.php?id=${fileId}&format=base64`)
Line 1077: fetch(`get_dicom_fast.php?id=${state.currentFileId}`)
Line 1273: fetch('toggle_star.php', {...})
Line 2082: fetch(`get_dicom_fast.php?id=${img.id}&format=base64`)
Line 2549: fetch(`get_dicom_fast.php?id=${fileId}`)
Line 2725: fetch(`get_dicom_file.php?id=${image.id}&format=raw`)
```

**Fix:**
```javascript
const basePath = window.basePath || '';

// Example fixes:
fetch(`${basePath}/pacs_search.php?action=search&...`)
fetch(`${basePath}/get_dicom_fast.php?id=${fileId}&format=base64`)
fetch(`${basePath}/toggle_star.php`, {...})
fetch(`${basePath}/get_dicom_file.php?id=${image.id}&format=raw`)
```

---

### 2. js/studies.js
**Current Issues:**
```javascript
Line 21: fetch('../api/study_list_api.php?patient_id=' + ...)
Line 120: fetch('../toggle_star.php', {...})
Line 177: fetch(apiUrl)  // May contain relative path
Line 222: fetch('../api/get_prescription.php?study_uid=' + ...)
Line 466: fetch('../api/save_prescription.php', {...})
```

**Fix:**
```javascript
const basePath = window.basePath || '';

// Remove '../' and add basePath
fetch(`${basePath}/api/study_list_api.php?patient_id=` + ...)
fetch(`${basePath}/toggle_star.php`, {...})
fetch(`${basePath}/api/get_prescription.php?study_uid=` + ...)
fetch(`${basePath}/api/save_prescription.php`, {...})
```

---

### 3. js/components/upload-handler.js
**Current Issues:**
```javascript
Line 248: fetch('upload.php', {...})
```

**Fix:**
```javascript
const basePath = window.basePath || '';
fetch(`${basePath}/upload.php`, {...})
```

---

### 4. js/components/export-manager.js
**Current Issues:**
```javascript
Line 160: fetch(`api/get_study_report.php?imageId=${fileId}`)
```

**Fix:**
```javascript
const basePath = window.basePath || '';
fetch(`${basePath}/api/get_study_report.php?imageId=${fileId}`)
```

---

### 5. js/components/event-handlers.js
**Current Issues:**
```javascript
Line 139: fetch(`check_report.php?imageId=${file.id}`, {...})
```

**Fix:**
```javascript
const basePath = window.basePath || '';
fetch(`${basePath}/check_report.php?imageId=${file.id}`, {...})
```

---

### 6. js/components/reporting-system.js
**Current Issues:**
```javascript
Line 1040: fetch('save_report.php', {...})
Line 1093: fetch(`load_report.php?imageId=${currentImage.id}&...`, {...})
Line 1308: fetch(`check_report.php?imageId=${imageId}&...`, {...})
Line 1350: fetch(`check_report.php?imageId=${imageId}&...`, {...})
Line 1410: fetch(`api/get_report.php?imageId=${imageId}&...`)
```

**Fix:**
```javascript
const basePath = window.basePath || '';

fetch(`${basePath}/save_report.php`, {...})
fetch(`${basePath}/load_report.php?imageId=${currentImage.id}&...`, {...})
fetch(`${basePath}/check_report.php?imageId=${imageId}&...`, {...})
fetch(`${basePath}/api/get_report.php?imageId=${imageId}&...`)
```

---

### 7. js/components/medical-notes.js
**Current Issues:**
```javascript
Line 246: fetch(`get_notes.php?${params}`)
Line 308: fetch('save_notes.php', {...})
Line 428: fetch('save_notes.php', {...})
Line 464: fetch(`get_notes.php?${params}`)
```

**Fix:**
```javascript
const basePath = window.basePath || '';

fetch(`${basePath}/get_notes.php?${params}`)
fetch(`${basePath}/save_notes.php`, {...})
```

---

## Automated Fix Script

### Option 1: Manual Search & Replace

Use your IDE's search/replace across all files in `/js/` folder:

**Find:** `fetch\(['"](?!https?://|wadouri:)([^'"\s]+)`
**Replace:** Check each and add basePath prefix

### Option 2: Node.js Script

Create `fix-paths.js`:
```javascript
const fs = require('fs');
const path = require('path');

const jsDir = './js';

function fixFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Fix patterns
    const patterns = [
        { find: /fetch\('api\//g, replace: "fetch(`${window.basePath || ''}/api/" },
        { find: /fetch\("\.\.\\/api\//g, replace: "fetch(`${window.basePath || ''}/api/" },
        { find: /fetch\('upload\.php'/g, replace: "fetch(`${window.basePath || ''}/upload.php`" },
        { find: /fetch\('save_report\.php'/g, replace: "fetch(`${window.basePath || ''}/save_report.php`" },
        // Add more patterns as needed
    ];

    patterns.forEach(pattern => {
        if (pattern.find.test(content)) {
            content = content.replace(pattern.find, pattern.replace);
            modified = true;
        }
    });

    if (modified) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`✓ Fixed: ${filePath}`);
    }
}

// Recursively fix all JS files
function walkDir(dir) {
    const files = fs.readdirSync(dir);
    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);
        if (stat.isDirectory()) {
            walkDir(filePath);
        } else if (file.endsWith('.js')) {
            fixFile(filePath);
        }
    });
}

walkDir(jsDir);
console.log('✓ All files processed');
```

Run with: `node fix-paths.js`

---

## Testing After Fixes

### 1. Check Browser Console
After fixing paths, load the page and check console:
```
✓ No 404 errors
✓ All API calls use correct paths
✓ BASE_PATH correctly applied
```

### 2. Test Each Feature
- [ ] Upload DICOM files
- [ ] Load images
- [ ] Save/load reports
- [ ] Save/load notes
- [ ] Toggle star/favorite
- [ ] Export functions
- [ ] Print functions
- [ ] PACS search

### 3. Test Different Deployments
Test in multiple scenarios:
- [ ] localhost root: `http://localhost/index.php`
- [ ] localhost subfolder: `http://localhost/dicom/index.php`
- [ ] production: `https://yourdomain.com/index.php`
- [ ] production subfolder: `https://yourdomain.com/radiology/index.php`

---

## Priority Files (Fix These First)

1. **js/main.js** - Core functionality
2. **js/components/upload-handler.js** - File uploads
3. **js/orthanc-autoload.js** - Already fixed ✅
4. **js/components/reporting-system.js** - Report management
5. **js/components/medical-notes.js** - Notes management

---

## Example: Complete Fix for upload-handler.js

**Before:**
```javascript
async uploadFiles(files) {
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('dicomFiles[]', files[i]);
    }

    const response = await fetch('upload.php', {
        method: 'POST',
        body: formData
    });

    return await response.json();
}
```

**After:**
```javascript
async uploadFiles(files) {
    const basePath = window.basePath || '';
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('dicomFiles[]', files[i]);
    }

    const response = await fetch(`${basePath}/upload.php`, {
        method: 'POST',
        body: formData
    });

    return await response.json();
}
```

---

## Best Practices

### 1. Declare basePath Once Per Function
```javascript
async myFunction() {
    const basePath = window.basePath || '';

    // Use basePath for all fetch calls in this function
    await fetch(`${basePath}/api/endpoint1.php`);
    await fetch(`${basePath}/api/endpoint2.php`);
}
```

### 2. Create a Helper Function
```javascript
// At top of file or in utils
const getApiUrl = (endpoint) => {
    const basePath = window.basePath || '';
    return `${basePath}/${endpoint}`;
};

// Usage
fetch(getApiUrl('api/studies.php'));
fetch(getApiUrl('upload.php'));
```

### 3. Don't Touch External URLs
```javascript
// Keep these as-is (they're absolute URLs)
fetch('https://api.external.com/data')
fetch('wadouri:http://localhost:8042/dicom')
```

---

## Verification Checklist

After fixing all files:

- [ ] No hardcoded `fetch('api/...)`
- [ ] No hardcoded `fetch('../api/...)`
- [ ] All relative paths use `${basePath}/`
- [ ] window.basePath is available (set in main.js)
- [ ] Browser console shows no 404 errors
- [ ] All API calls work in localhost
- [ ] All API calls work in subfolder deployment
- [ ] All API calls work on production domain

---

## Summary

**Total Files to Fix:** 7 files
**Total fetch() Calls:** ~26 calls
**Estimated Time:** 30-60 minutes for manual fixes
**Complexity:** Low (simple pattern replacement)

**Recommendation:**
Use your IDE's search/replace feature to fix all files quickly, then test thoroughly.

---

*Last Updated: 2025-11-22*
