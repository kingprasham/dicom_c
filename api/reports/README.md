# Medical Reports API

This directory contains the API endpoints for managing medical reports in the Hospital DICOM Viewer Pro v2.0 application.

## Overview

Medical reports are stored in the database (`medical_reports` table) with version history tracking (`report_versions` table). All endpoints require authentication and log operations to the audit logs.

## Endpoints

### 1. Create Report
**File:** `create.php`
**Method:** `POST`
**Endpoint:** `/api/reports/create.php`

**Request Body:**
```json
{
  "study_uid": "1.2.840.113619.2.55.3.1",
  "patient_id": "P123456",
  "patient_name": "John Doe",
  "template_name": "CT Chest",
  "title": "CT Chest with Contrast",
  "indication": "Shortness of breath",
  "technique": "Contrast-enhanced CT scan",
  "findings": "No acute findings",
  "impression": "Normal study",
  "reporting_physician_id": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Medical report created successfully",
  "data": {
    "id": 1,
    "study_uid": "1.2.840.113619.2.55.3.1",
    "patient_id": "P123456",
    "patient_name": "John Doe",
    "template_name": "CT Chest",
    "title": "CT Chest with Contrast",
    "indication": "Shortness of breath",
    "technique": "Contrast-enhanced CT scan",
    "findings": "No acute findings",
    "impression": "Normal study",
    "reporting_physician_id": 2,
    "status": "draft",
    "created_by": 1,
    "created_at": "2025-11-22 10:30:00",
    "updated_at": "2025-11-22 10:30:00",
    "finalized_at": null,
    "created_by_name": "Dr. John Smith",
    "reporting_physician_name": "Dr. Jane Doe"
  }
}
```

---

### 2. Get Report by ID
**File:** `get.php`
**Method:** `GET`
**Endpoint:** `/api/reports/get.php?id={reportId}`

**Query Parameters:**
- `id` (required): Report ID

**Response:**
```json
{
  "success": true,
  "message": "Medical report retrieved successfully",
  "data": {
    "id": 1,
    "study_uid": "1.2.840.113619.2.55.3.1",
    "patient_id": "P123456",
    "patient_name": "John Doe",
    "template_name": "CT Chest",
    "title": "CT Chest with Contrast",
    "indication": "Shortness of breath",
    "technique": "Contrast-enhanced CT scan",
    "findings": "No acute findings",
    "impression": "Normal study",
    "reporting_physician_id": 2,
    "status": "draft",
    "created_by": 1,
    "created_at": "2025-11-22 10:30:00",
    "updated_at": "2025-11-22 10:30:00",
    "finalized_at": null,
    "created_by_name": "Dr. John Smith",
    "created_by_username": "drsmith",
    "reporting_physician_name": "Dr. Jane Doe",
    "reporting_physician_username": "drjane"
  }
}
```

---

### 3. Update Report
**File:** `update.php`
**Method:** `PUT`
**Endpoint:** `/api/reports/update.php`

**Request Body:**
```json
{
  "id": 1,
  "indication": "Updated indication",
  "technique": "Updated technique",
  "findings": "Updated findings",
  "impression": "Updated impression",
  "status": "final",
  "change_reason": "Final review completed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Medical report updated successfully",
  "data": {
    "report": {
      "id": 1,
      "study_uid": "1.2.840.113619.2.55.3.1",
      "patient_id": "P123456",
      "patient_name": "John Doe",
      "template_name": "CT Chest",
      "title": "CT Chest with Contrast",
      "indication": "Updated indication",
      "technique": "Updated technique",
      "findings": "Updated findings",
      "impression": "Updated impression",
      "reporting_physician_id": 2,
      "status": "final",
      "created_by": 1,
      "created_at": "2025-11-22 10:30:00",
      "updated_at": "2025-11-22 10:35:00",
      "finalized_at": "2025-11-22 10:35:00",
      "created_by_name": "Dr. John Smith",
      "reporting_physician_name": "Dr. Jane Doe"
    },
    "version_created": 1,
    "version_id": 1
  }
}
```

**Notes:**
- Creates a version history entry before updating
- If status changes to "final", sets `finalized_at` timestamp
- Only provided fields are updated; others remain unchanged

---

### 4. Delete Report
**File:** `delete.php`
**Method:** `DELETE`
**Endpoint:** `/api/reports/delete.php?id={reportId}`

**Query Parameters:**
- `id` (required): Report ID

**Response:**
```json
{
  "success": true,
  "message": "Medical report deleted successfully",
  "data": {
    "deleted_report_id": 1,
    "study_uid": "1.2.840.113619.2.55.3.1",
    "title": "CT Chest with Contrast"
  }
}
```

**Notes:**
- Cascade deletes all version history entries
- Consider uncommenting permission checks in the code to restrict deletion

---

### 5. Get Reports by Study
**File:** `by-study.php`
**Method:** `GET`
**Endpoint:** `/api/reports/by-study.php?studyUID={studyUID}`

**Query Parameters:**
- `studyUID` (required): Study UID

**Response:**
```json
{
  "success": true,
  "message": "Medical reports retrieved successfully",
  "data": {
    "study_uid": "1.2.840.113619.2.55.3.1",
    "count": 2,
    "reports": [
      {
        "id": 1,
        "study_uid": "1.2.840.113619.2.55.3.1",
        "patient_id": "P123456",
        "patient_name": "John Doe",
        "template_name": "CT Chest",
        "title": "CT Chest with Contrast",
        "indication": "Shortness of breath",
        "technique": "Contrast-enhanced CT scan",
        "findings": "No acute findings",
        "impression": "Normal study",
        "status": "final",
        "created_at": "2025-11-22 10:30:00",
        "updated_at": "2025-11-22 10:35:00",
        "finalized_at": "2025-11-22 10:35:00",
        "created_by_id": 1,
        "created_by_name": "Dr. John Smith",
        "created_by_username": "drsmith",
        "reporting_physician_id": 2,
        "reporting_physician_name": "Dr. Jane Doe",
        "reporting_physician_username": "drjane"
      }
    ]
  }
}
```

---

### 6. Get Version History
**File:** `versions.php`
**Method:** `GET`
**Endpoint:** `/api/reports/versions.php?reportId={reportId}`

**Query Parameters:**
- `reportId` (required): Report ID

**Response:**
```json
{
  "success": true,
  "message": "Version history retrieved successfully",
  "data": {
    "report_id": 1,
    "study_uid": "1.2.840.113619.2.55.3.1",
    "report_title": "CT Chest with Contrast",
    "version_count": 2,
    "versions": [
      {
        "id": 2,
        "report_id": 1,
        "version_number": 2,
        "indication": "Updated indication",
        "technique": "Updated technique",
        "findings": "Updated findings",
        "impression": "Updated impression",
        "change_reason": "Final review completed",
        "created_at": "2025-11-22 10:40:00",
        "changed_by_id": 1,
        "changed_by_name": "Dr. John Smith",
        "changed_by_username": "drsmith"
      },
      {
        "id": 1,
        "report_id": 1,
        "version_number": 1,
        "indication": "Shortness of breath",
        "technique": "Contrast-enhanced CT scan",
        "findings": "No acute findings",
        "impression": "Normal study",
        "change_reason": null,
        "created_at": "2025-11-22 10:35:00",
        "changed_by_id": 1,
        "changed_by_name": "Dr. John Smith",
        "changed_by_username": "drsmith"
      }
    ]
  }
}
```

---

## Database Schema

### medical_reports Table
```sql
CREATE TABLE `medical_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_uid` VARCHAR(255) NOT NULL,
    `patient_id` VARCHAR(64) NOT NULL,
    `patient_name` VARCHAR(255) NOT NULL,
    `template_name` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `indication` TEXT,
    `technique` TEXT,
    `findings` TEXT,
    `impression` TEXT,
    `reporting_physician_id` INT UNSIGNED NULL,
    `status` ENUM('draft', 'final', 'amended') DEFAULT 'draft',
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `finalized_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`reporting_physician_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

### report_versions Table
```sql
CREATE TABLE `report_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `report_id` INT UNSIGNED NOT NULL,
    `version_number` INT UNSIGNED NOT NULL,
    `indication` TEXT,
    `technique` TEXT,
    `findings` TEXT,
    `impression` TEXT,
    `changed_by` INT UNSIGNED NOT NULL,
    `change_reason` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`report_id`) REFERENCES `medical_reports`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

---

## Security Features

1. **Authentication Required**: All endpoints validate session before processing
2. **Input Validation**: All input is sanitized and validated
3. **Prepared Statements**: MySQLi prepared statements prevent SQL injection
4. **Audit Logging**: All operations are logged to `audit_logs` table
5. **File Logging**: Operations are also logged to `reports.log`
6. **CORS Support**: Configurable CORS headers for API access

---

## Error Responses

All endpoints return consistent error responses:

```json
{
  "error": "Error message here",
  "success": false
}
```

### Common HTTP Status Codes:
- `200`: Success
- `400`: Bad Request (validation error)
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found (resource doesn't exist)
- `405`: Method Not Allowed (wrong HTTP method)
- `500`: Internal Server Error

---

## Version History Feature

The version history system automatically:
1. Creates a snapshot of the current report data before any update
2. Increments version numbers automatically
3. Records who made the change and when
4. Optionally records the reason for the change
5. Maintains complete audit trail for compliance (HIPAA)

---

## Usage Example (JavaScript)

```javascript
// Create a new report
async function createReport(reportData) {
    const response = await fetch('/api/reports/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(reportData)
    });
    return await response.json();
}

// Get reports for a study
async function getReportsByStudy(studyUID) {
    const response = await fetch(`/api/reports/by-study.php?studyUID=${encodeURIComponent(studyUID)}`);
    return await response.json();
}

// Update a report
async function updateReport(reportId, updates) {
    const response = await fetch('/api/reports/update.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: reportId, ...updates })
    });
    return await response.json();
}

// Get version history
async function getVersionHistory(reportId) {
    const response = await fetch(`/api/reports/versions.php?reportId=${reportId}`);
    return await response.json();
}
```

---

## Notes

- Reports are stored in the database, NOT as files
- All text fields support UTF-8 characters
- Version history is maintained indefinitely (unless manually deleted with the report)
- The `finalized_at` timestamp is automatically set when status changes to "final"
- Consider implementing additional permission checks based on user roles

---

**Created:** 2025-11-22
**Version:** 2.0.0
**Author:** Hospital DICOM Viewer Pro Development Team
