# Revert Guide - Fix Only Image Loading Issue

This guide shows you how to revert to your original working code with ONLY the essential image loading fix.

## Problem Summary

The image loading error on `https://e-connect.in/dicom/` is caused by a JavaScript scope issue where the `image` variable was declared inside a try block but accessed outside it.

## Files Status

### ✅ Files That Need The Fix (Keep Modified)

#### 1. **js/main.js** - CRITICAL FIX NEEDED
**Line ~969**: Change variable scope
```javascript
// BEFORE (BROKEN):
try {
    const image = await cornerstone.loadImage(imageId);
    ...
}
// Later tries to use: image.columns, image.rows → ERROR!

// AFTER (FIXED):
let image;  // Declare before try
try {
    image = await cornerstone.loadImage(imageId);
    ...
}
// Now works: image.columns, image.rows ✓
```

**Action**: Apply ONLY this scope fix to your production file

---

### ❌ Files You Can Revert (Not Essential for Image Loading)

#### 2. **index.php** - Sidebar Cleanup (Optional)
Changes made:
- Removed Image Enhancement section
- Removed Measurements section
- Removed Display Options section
- Added Print button

**Action**: Can use original version - these changes don't affect image loading

#### 3. **js/components/reporting-system.js** - Report Loading Fixes (Optional)
Changes made:
- Fixed `loadReportForImage()` function
- Fixed image ID usage for Orthanc images

**Action**: If reports aren't critical right now, can use original version

#### 4. **js/components/print-manager.js** - NEW FILE (Optional)
**Action**: Delete this file if you don't need print functionality

---

### ⚠️ Files That Should Stay Modified (Supporting Infrastructure)

#### 5. **api/get_dicom_from_orthanc.php** - REQUIRED (NEW FILE)
This PHP proxy is needed to fetch DICOM files from Orthanc server.

**Action**: KEEP this file - it's required for Orthanc image loading

#### 6. **api/load_study_fast.php** - REQUIRED CHANGE
Changes made:
- Added `'orthancInstanceId' => $instanceId` field
- Added `'isOrthancImage' => true` field

**Action**: KEEP these changes - required for the main.js fix to work

---

## Minimal Fix for Production

To fix ONLY the image loading error with minimal changes:

### Step 1: Verify Required Files Exist
```
/api/get_dicom_from_orthanc.php  ← Must exist
```

### Step 2: Update Only These Files

**File 1: api/load_study_fast.php**
Find line ~188-199 and ensure it has:
```php
$images[] = [
    'instanceId' => $instanceId,
    'orthancInstanceId' => $instanceId,  // ← Must have this
    'seriesInstanceUID' => $seriesUID,
    'sopInstanceUID' => $instanceData['MainDicomTags']['SOPInstanceUID'] ?? $instanceId,
    'instanceNumber' => intval($instanceData['MainDicomTags']['InstanceNumber'] ?? 0),
    'seriesDescription' => $seriesDesc,
    'seriesNumber' => intval($seriesNumber),
    'patientName' => $studyInfo['patient_name'],
    'useApiGateway' => false,
    'isOrthancImage' => true  // ← Must have this
];
```

**File 2: js/main.js**
Find line ~960-1000 (the Orthanc image loading section) and apply the scope fix:
```javascript
let image;  // ← Add this line
try {
    image = await cornerstone.loadImage(imageId);  // ← Remove 'const'
    // ... rest of code
}
// ... later code uses image.columns and image.rows
```

### Step 3: Keep Original Versions of These
- index.php (use your original)
- js/components/reporting-system.js (use your original)
- Delete: js/components/print-manager.js (if you don't need it)

---

## Testing After Revert

1. Open: `https://e-connect.in/dicom/index.php?studyUID=...&orthancId=...`
2. Image should load without errors
3. Console should show: "Image loaded successfully: 1280 x 1024" or similar

---

## Quick Reference: What Was The Original Bug?

**Original Error**: "image load error undefined"

**Root Cause**:
```javascript
try {
    const image = await cornerstone.loadImage(imageId);  // image only exists here
} catch (error) {
    // ...
}
// Later code tries to access image...
columns: image.columns,  // ReferenceError: image is undefined
```

**Fix**:
```javascript
let image;  // Declare in outer scope
try {
    image = await cornerstone.loadImage(imageId);  // Assign (no const)
} catch (error) {
    // ...
}
// Now image is accessible
columns: image.columns,  // Works!
```

---

## Summary

**MUST HAVE for image loading to work:**
1. ✅ api/get_dicom_from_orthanc.php (new file)
2. ✅ api/load_study_fast.php (add orthancInstanceId and isOrthancImage fields)
3. ✅ js/main.js (fix variable scope - `let image;` before try block)

**OPTIONAL (can revert to original):**
4. ⚪ index.php (UI cleanup)
5. ⚪ js/components/reporting-system.js (report fixes)
6. ⚪ js/components/print-manager.js (new feature - can delete)

**Config requirement:**
- Make sure production config.php has correct ORTHANC_URL, ORTHANC_USER, ORTHANC_PASS
