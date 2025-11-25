# Complete Hospital DICOM Viewer Pro v2.0 - Claude Code Rebuild Prompt

## INSTRUCTIONS FOR USE

Copy the entire content below (from "START OF PROMPT" to "END OF PROMPT") and paste it into Claude Code to automatically rebuild this entire DICOM viewer system with modern architecture and zero database syncing.

---

## START OF PROMPT

I need you to build a complete **Hospital DICOM Viewer Pro v2.0** - a modern, production-ready web-based medical imaging system for viewing CT, MRI, X-Ray, and other DICOM medical images. This system will work with Orthanc DICOM server and must eliminate all database synchronization issues by using DICOMweb APIs directly.

## PROJECT REQUIREMENTS

### Core Functionality (ALL must work perfectly)
1. **Patient/Study Management**
   - Patient list with advanced filtering (name, ID, date range, modality, sex)
   - Study list for each patient
   - Real-time data from Orthanc (NO manual database sync)
   - Pagination and infinite scroll
   - Search and sort capabilities

2. **Advanced DICOM Viewer**
   - Multi-layout support: 1x1, 2x1, 2x2 viewports
   - Multi-Planar Reconstruction (MPR): Axial, Sagittal, Coronal views
   - Synchronized crosshairs and reference lines
   - Series navigation with slider
   - Image stack navigation (previous/next)
   - Cine mode (play/pause) with FPS control

3. **Image Enhancement**
   - Window/Level adjustment (W/L) with sliders
   - Presets: Lung, Abdomen, Brain, Bone, Default
   - Auto Window/Level
   - Invert colors
   - Flip horizontal/vertical
   - Rotate left/right
   - Zoom and pan
   - Reset view

4. **Measurement Tools**
   - Length measurement (ruler)
   - Angle measurement
   - Free-hand ROI (region of interest)
   - Elliptical ROI
   - Rectangle ROI
   - Probe (pixel value)
   - Save measurements to database

5. **Medical Reporting**
   - Report templates: CT Head, CT Chest, CT Abdomen, MRI Brain, X-Ray Chest
   - Report sections: Indication, Technique, Findings, Impression
   - Auto-save functionality
   - Version history (track changes)
   - Reporting physician assignment
   - Save to database (NOT file system)
   - Load existing reports

6. **Clinical Notes**
   - Per-series or per-image notes
   - Clinical history capture
   - Patient demographics display
   - Save to database

7. **Authentication & Authorization**
   - JWT-based authentication
   - Login/Logout
   - Role-based access: admin, radiologist, technician, viewer
   - Session management
   - Secure password hashing (bcrypt)

8. **Mobile Support**
   - Fully responsive design
   - Touch gestures (pinch zoom, pan)
   - Mobile-optimized UI
   - Works on tablets and smartphones

9. **Export & Print**
   - Export image as PNG
   - Export report as PDF
   - Print functionality
   - Export measurements

10. **Performance Features**
    - Progressive image loading (thumbnails first, full quality on demand)
    - Browser caching (IndexedDB)
    - Lazy loading for study lists
    - Web Workers for heavy computations (MPR)
    - Service Worker for offline capability (PWA)

---

## TECHNICAL ARCHITECTURE

### Technology Stack

**Backend:**
- PHP 8.2+ (for XAMPP compatibility)
- MySQL 8.0+ database
- JWT authentication (use Firebase JWT library: `composer require firebase/php-jwt`)
- DICOMweb proxy (no manual sync, query Orthanc directly)

**Frontend:**
- React 18+ with TypeScript (optional, can use JavaScript)
- Vite (build tool)
- Tailwind CSS + Shadcn/UI components
- Cornerstone3D 2.0 (latest DICOM viewer library)
- @cornerstonejs/tools (measurements)
- @cornerstonejs/dicomImageLoader (DICOM loading)
- dicomweb-client (standard DICOMweb library)
- Zustand or Redux Toolkit (state management)

**DICOM Server:**
- Orthanc with DICOMweb plugin enabled
- Direct DICOMweb API integration (WADO-RS, QIDO-RS)

**Deployment:**
- Works on XAMPP (localhost development)
- Docker Compose option (production)
- cPanel compatible (traditional hosting)

### Architecture Pattern

```
MRI/CT Device → Orthanc (DICOMweb APIs) → PHP API (Proxy + Auth) → React Frontend
                                              ↓
                                         MySQL (users, reports, measurements ONLY)
```

**NO patient/study caching in MySQL - query Orthanc directly via DICOMweb!**

---

## DATABASE SCHEMA

Create a MySQL database named `dicom_viewer_v2` with these tables:

```sql
-- Users and Authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    role ENUM('admin', 'radiologist', 'technician', 'viewer') DEFAULT 'viewer',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Medical Reports (linked to DICOM Study UID)
CREATE TABLE medical_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    study_instance_uid VARCHAR(255) NOT NULL,
    report_type VARCHAR(50),
    indication TEXT,
    technique TEXT,
    findings TEXT,
    impression TEXT,
    reporting_physician_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version INT DEFAULT 1,
    status ENUM('draft', 'final', 'amended') DEFAULT 'draft',
    FOREIGN KEY (reporting_physician_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_study_uid (study_instance_uid),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Report Version History
CREATE TABLE report_versions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    version INT NOT NULL,
    report_data JSON,
    modified_by INT,
    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES medical_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES users(id),
    INDEX idx_report_id (report_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Measurements and Annotations
CREATE TABLE measurements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    series_instance_uid VARCHAR(255) NOT NULL,
    sop_instance_uid VARCHAR(255),
    measurement_type ENUM('length', 'angle', 'roi', 'ellipse', 'rectangle', 'probe'),
    measurement_data JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_series_uid (series_instance_uid),
    INDEX idx_sop_uid (sop_instance_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clinical Notes
CREATE TABLE clinical_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    study_instance_uid VARCHAR(255),
    series_instance_uid VARCHAR(255),
    note_text TEXT,
    note_category VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_study_uid (study_instance_uid),
    INDEX idx_series_uid (series_instance_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prescriptions
CREATE TABLE prescriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    study_instance_uid VARCHAR(255),
    patient_identifier VARCHAR(255),
    prescription_data JSON,
    prescribed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescribed_by) REFERENCES users(id),
    INDEX idx_study_uid (study_instance_uid),
    INDEX idx_patient_id (patient_identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Preferences
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value JSON,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_pref (user_id, preference_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs (HIPAA compliance)
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    resource_type VARCHAR(50),
    resource_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin user (password: admin123)
INSERT INTO users (username, password_hash, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@hospital.com', 'admin');
```

---

## API ENDPOINTS TO BUILD

### Authentication (`/api/auth/`)
```
POST   /api/auth/login.php       - Login (returns JWT token)
POST   /api/auth/logout.php      - Logout
GET    /api/auth/refresh.php     - Refresh JWT token
GET    /api/auth/me.php          - Get current user info
```

### DICOMweb Proxy (`/api/dicomweb/`)
**IMPORTANT: These endpoints query Orthanc directly, NO database cache!**

```
GET    /api/dicomweb/studies.php
       - Query studies from Orthanc QIDO-RS
       - Params: PatientName, PatientID, StudyDate, ModalitiesInStudy, limit, offset
       - Returns: JSON array of studies

GET    /api/dicomweb/study-metadata.php?studyUID={uid}
       - Get full study metadata from Orthanc

GET    /api/dicomweb/series.php?studyUID={uid}
       - Get series list for study

GET    /api/dicomweb/instances.php?studyUID={uid}&seriesUID={uid}
       - Get instances for series

GET    /api/dicomweb/instance-file.php?instanceUID={uid}
       - Get DICOM file (proxy to Orthanc WADO-RS)

GET    /api/dicomweb/thumbnail.php?instanceUID={uid}
       - Get thumbnail image
```

### Medical Reports (`/api/reports/`)
```
POST   /api/reports/create.php    - Create new report
GET    /api/reports/get.php?id={id}  - Get report by ID
PUT    /api/reports/update.php    - Update report
DELETE /api/reports/delete.php?id={id}  - Delete report
GET    /api/reports/by-study.php?studyUID={uid}  - Get report for study
GET    /api/reports/versions.php?reportId={id}   - Get version history
```

### Measurements (`/api/measurements/`)
```
POST   /api/measurements/create.php  - Save measurement
GET    /api/measurements/by-series.php?seriesUID={uid}  - Get measurements
DELETE /api/measurements/delete.php?id={id}  - Delete measurement
```

### Clinical Notes (`/api/notes/`)
```
POST   /api/notes/create.php   - Create note
GET    /api/notes/by-study.php?studyUID={uid}  - Get notes
PUT    /api/notes/update.php   - Update note
DELETE /api/notes/delete.php?id={id}  - Delete note
```

### Utilities (`/api/`)
```
GET    /api/orthanc-status.php  - Check Orthanc server health
GET    /api/config.php          - Get frontend configuration (Orthanc URL, etc.)
```

---

## FRONTEND STRUCTURE

Create a React application with this structure:

```
frontend/
├── src/
│   ├── components/
│   │   ├── viewer/
│   │   │   ├── DicomViewer.tsx         # Main viewer component
│   │   │   ├── ViewportGrid.tsx        # Layout manager (1x1, 2x1, 2x2)
│   │   │   ├── MPRViewports.tsx        # Multi-planar reconstruction
│   │   │   ├── Toolbar.tsx             # Tools and controls
│   │   │   ├── SeriesNavigator.tsx     # Series slider
│   │   │   ├── ImageEnhancement.tsx    # W/L, presets, transforms
│   │   │   └── MeasurementTools.tsx    # Measurement buttons
│   │   ├── patient/
│   │   │   ├── PatientList.tsx         # Patient worklist
│   │   │   ├── StudyList.tsx           # Studies for patient
│   │   │   ├── StudyCard.tsx           # Study preview card
│   │   │   └── Filters.tsx             # Advanced filters
│   │   ├── reporting/
│   │   │   ├── ReportEditor.tsx        # Medical report UI
│   │   │   ├── ReportTemplates.tsx     # Template selector
│   │   │   ├── ReportSections.tsx      # Indication, Technique, etc.
│   │   │   └── ReportHistory.tsx       # Version history
│   │   ├── notes/
│   │   │   ├── NotesPanel.tsx          # Clinical notes UI
│   │   │   └── NotesEditor.tsx         # Note editor
│   │   ├── auth/
│   │   │   ├── Login.tsx               # Login form
│   │   │   ├── ProtectedRoute.tsx      # Route guard
│   │   │   └── UserMenu.tsx            # User dropdown
│   │   ├── layout/
│   │   │   ├── Header.tsx              # App header
│   │   │   ├── Sidebar.tsx             # Navigation sidebar
│   │   │   └── MobileNav.tsx           # Mobile navigation
│   │   └── ui/                         # Shadcn/UI components
│   │       ├── button.tsx
│   │       ├── input.tsx
│   │       ├── dialog.tsx
│   │       └── ... (install via shadcn/ui CLI)
│   ├── services/
│   │   ├── dicomwebService.ts          # DICOMweb API client
│   │   ├── authService.ts              # JWT authentication
│   │   ├── reportService.ts            # Reports API
│   │   ├── measurementService.ts       # Measurements API
│   │   ├── notesService.ts             # Notes API
│   │   └── cacheService.ts             # IndexedDB caching
│   ├── stores/
│   │   ├── authStore.ts                # User state (Zustand)
│   │   ├── viewerStore.ts              # Viewer state
│   │   ├── studyStore.ts               # Current study data
│   │   └── uiStore.ts                  # UI state (sidebar, layout)
│   ├── utils/
│   │   ├── cornerstoneInit.ts          # Cornerstone3D setup
│   │   ├── dicomParser.ts              # DICOM parsing utilities
│   │   ├── mprCalculations.ts          # MPR math
│   │   └── constants.ts                # Constants (W/L presets, etc.)
│   ├── hooks/
│   │   ├── useDicomLoader.ts           # Load DICOM images
│   │   ├── useStudyQuery.ts            # Query studies
│   │   └── useAuth.ts                  # Auth hook
│   ├── types/
│   │   ├── dicom.ts                    # DICOM type definitions
│   │   ├── report.ts                   # Report types
│   │   └── user.ts                     # User types
│   ├── App.tsx                         # Root component
│   ├── main.tsx                        # Entry point
│   └── index.css                       # Global styles (Tailwind)
├── public/
│   └── service-worker.js               # PWA service worker
├── package.json
├── vite.config.ts
├── tailwind.config.js
└── tsconfig.json
```

---

## IMPLEMENTATION REQUIREMENTS

### 1. DICOMweb Integration (PHP)

**Example: `/api/dicomweb/studies.php`**
```php
<?php
require_once '../config/database.php';
require_once '../middleware/auth.php';

// Verify JWT token
$user = verifyJWT();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get query parameters
$patientName = $_GET['PatientName'] ?? '';
$patientID = $_GET['PatientID'] ?? '';
$studyDate = $_GET['StudyDate'] ?? '';
$modality = $_GET['ModalitiesInStudy'] ?? '';
$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

// Build QIDO-RS query to Orthanc
$orthancUrl = getenv('ORTHANC_URL') ?: 'http://localhost:8042';
$qidoUrl = $orthancUrl . '/dicom-web/studies?';

$params = [];
if ($patientName) $params['PatientName'] = $patientName;
if ($patientID) $params['PatientID'] = $patientID;
if ($studyDate) $params['StudyDate'] = $studyDate;
if ($modality) $params['ModalitiesInStudy'] = $modality;
$params['limit'] = $limit;
$params['offset'] = $offset;

$qidoUrl .= http_build_query($params);

// Fetch from Orthanc with authentication
$ch = curl_init($qidoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, 'orthanc:orthanc');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/dicom+json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    // Log audit
    logAudit($user['id'], 'query_studies', 'study', null);

    header('Content-Type: application/json');
    echo $response;
} else {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Failed to query Orthanc']);
}
```

### 2. Cornerstone3D Initialization (React)

**`src/utils/cornerstoneInit.ts`**
```typescript
import { init as csInit } from '@cornerstonejs/core';
import { init as csToolsInit } from '@cornerstonejs/tools';
import cornerstoneDICOMImageLoader from '@cornerstonejs/dicom-image-loader';
import dicomParser from 'dicom-parser';

export async function initializeCornerstone() {
  // Initialize Cornerstone3D
  await csInit();
  await csToolsInit();

  // Configure DICOM Image Loader
  cornerstoneDICOMImageLoader.external.cornerstone = cornerstone;
  cornerstoneDICOMImageLoader.external.dicomParser = dicomParser;

  cornerstoneDICOMImageLoader.configure({
    useWebWorkers: true,
    decodeConfig: {
      convertFloatPixelDataToInt: false,
    },
  });

  // Set up WADO-RS loader (DICOMweb)
  cornerstoneDICOMImageLoader.wadouri.dataSetCacheManager.maxCacheSize = 3 * 1024 * 1024 * 1024; // 3GB
}
```

### 3. Patient List Component (React)

**`src/components/patient/PatientList.tsx`**
```tsx
import { useState, useEffect } from 'react';
import { dicomwebService } from '@/services/dicomwebService';
import { StudyCard } from './StudyCard';
import { Filters } from './Filters';

export function PatientList() {
  const [studies, setStudies] = useState([]);
  const [filters, setFilters] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    loadStudies();
  }, [filters]);

  const loadStudies = async () => {
    setLoading(true);
    try {
      const data = await dicomwebService.queryStudies(filters);
      setStudies(data);
    } catch (error) {
      console.error('Failed to load studies:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="patient-list">
      <Filters filters={filters} onChange={setFilters} />
      <div className="study-grid">
        {loading ? (
          <div>Loading...</div>
        ) : (
          studies.map(study => (
            <StudyCard key={study.studyUID} study={study} />
          ))
        )}
      </div>
    </div>
  );
}
```

### 4. DICOM Viewer Component (React)

**`src/components/viewer/DicomViewer.tsx`**
```tsx
import { useEffect, useRef } from 'react';
import { RenderingEngine, Types } from '@cornerstonejs/core';
import { cornerstoneInit } from '@/utils/cornerstoneInit';
import { useDicomLoader } from '@/hooks/useDicomLoader';

export function DicomViewer({ studyUID }) {
  const viewportRef = useRef<HTMLDivElement>(null);
  const { images, loading } = useDicomLoader(studyUID);

  useEffect(() => {
    initViewer();
  }, []);

  useEffect(() => {
    if (images.length > 0) {
      displayImages();
    }
  }, [images]);

  const initViewer = async () => {
    await cornerstoneInit();

    const renderingEngine = new RenderingEngine('myRenderingEngine');
    const viewport = renderingEngine.createViewport(viewportRef.current, {
      viewportId: 'main-viewport',
      type: Types.ViewportType.STACK,
    });
  };

  const displayImages = () => {
    const imageIds = images.map(img => `wadors:${img.wadoUri}`);
    viewport.setStack(imageIds);
    viewport.render();
  };

  return (
    <div ref={viewportRef} className="dicom-viewport" />
  );
}
```

### 5. MPR Manager (React)

**`src/components/viewer/MPRViewports.tsx`**
```tsx
// Implement MPR using Cornerstone3D volume rendering
// Create 3 viewports: Axial, Sagittal, Coronal
// Synchronize slicing with crosshairs
// Use VTK.js for volume reconstruction
```

### 6. Report Editor Component (React)

**`src/components/reporting/ReportEditor.tsx`**
```tsx
import { useState, useEffect } from 'react';
import { reportService } from '@/services/reportService';
import { ReportTemplates } from './ReportTemplates';
import { ReportSections } from './ReportSections';

export function ReportEditor({ studyUID }) {
  const [report, setReport] = useState(null);
  const [template, setTemplate] = useState('ct_head');

  useEffect(() => {
    loadReport();
  }, [studyUID]);

  const loadReport = async () => {
    const data = await reportService.getByStudy(studyUID);
    if (data) {
      setReport(data);
      setTemplate(data.report_type);
    }
  };

  const saveReport = async () => {
    if (report?.id) {
      await reportService.update(report.id, report);
    } else {
      await reportService.create({ ...report, study_instance_uid: studyUID });
    }
  };

  return (
    <div className="report-editor">
      <ReportTemplates selected={template} onChange={setTemplate} />
      <ReportSections
        report={report}
        onChange={setReport}
        onSave={saveReport}
      />
    </div>
  );
}
```

---

## CONFIGURATION

### Environment Variables (`.env`)

Create `/config/.env`:
```
# Database
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=root
DB_NAME=dicom_viewer_v2

# Orthanc
ORTHANC_URL=http://localhost:8042
ORTHANC_USERNAME=orthanc
ORTHANC_PASSWORD=orthanc

# JWT
JWT_SECRET=your-secret-key-change-in-production
JWT_EXPIRY=28800

# Environment
APP_ENV=development
APP_URL=http://localhost:3000
```

### Orthanc Configuration

Enable DICOMweb plugin in Orthanc configuration file:
```json
{
  "Plugins": ["./plugins"],
  "DicomWeb": {
    "Enable": true,
    "Root": "/dicom-web/",
    "EnableWado": true,
    "WadoRoot": "/wado/",
    "Ssl": false,
    "QidoCaseSensitive": false
  }
}
```

---

## DEPLOYMENT INSTRUCTIONS

### Option 1: XAMPP (Localhost)

1. **Install XAMPP** with PHP 8.2+
2. **Install Orthanc** with DICOMweb plugin
3. **Clone/Copy project** to `C:\xampp\htdocs\dicom_viewer_v2\`
4. **Create database**: Import SQL schema
5. **Install PHP dependencies**:
   ```bash
   cd api
   composer install
   ```
6. **Build React frontend**:
   ```bash
   cd frontend
   npm install
   npm run build
   cp -r dist/* ../public/
   ```
7. **Configure `.env`** file
8. **Start XAMPP** (Apache, MySQL)
9. **Start Orthanc**
10. **Access**: `http://localhost/dicom_viewer_v2/`

### Option 2: Docker Compose (Production)

Create `docker-compose.yml` with:
- Orthanc service (with DICOMweb plugin)
- MySQL service
- PHP-FPM service
- Nginx service (serving React build + proxying API)

Single command: `docker-compose up -d`

---

## TESTING CHECKLIST

After implementation, verify:

- [ ] Login works with JWT authentication
- [ ] Patient/Study list loads from Orthanc (no database sync)
- [ ] Studies display in real-time (new studies appear without manual sync)
- [ ] DICOM images load and render in viewer
- [ ] 1x1, 2x1, 2x2 layouts work
- [ ] MPR (Axial, Sagittal, Coronal) works
- [ ] Window/Level adjustment works
- [ ] All W/L presets work (Lung, Abdomen, Brain, Bone)
- [ ] Image transforms work (flip, rotate, invert, zoom, pan)
- [ ] All measurement tools work (length, angle, ROI, etc.)
- [ ] Measurements save to database
- [ ] Report creation works with all templates
- [ ] Report saving works (to database, not file system)
- [ ] Report version history works
- [ ] Clinical notes save and load
- [ ] Mobile responsive (test on tablet/phone)
- [ ] Touch gestures work (pinch zoom, pan)
- [ ] Export to PNG works
- [ ] Export to PDF works
- [ ] Print functionality works
- [ ] Role-based access works (admin, radiologist, etc.)
- [ ] Audit logging works
- [ ] Performance is acceptable (study loads < 3 seconds)
- [ ] Large studies work (100+ images)
- [ ] Progressive loading works (thumbnails first)

---

## SUCCESS CRITERIA

1. **Zero Database Sync** - Patient/study data comes directly from Orthanc
2. **All Features Work** - Every feature from the old system works perfectly
3. **Better Performance** - Faster loading, progressive rendering
4. **Modern UI** - Clean, responsive, mobile-friendly
5. **Production-Ready** - Proper error handling, security, logging
6. **Easy Deployment** - Works on XAMPP, Docker, or cPanel
7. **No Manual Scripts** - No batch files needed for syncing

---

## ADDITIONAL NOTES

- Use **modern ES6+ JavaScript/TypeScript** throughout
- Follow **React best practices** (hooks, functional components)
- Implement **proper error handling** in all API calls
- Add **loading states** and **error messages** for better UX
- Use **Tailwind CSS** for styling (utility-first)
- Make it **fully responsive** (mobile-first design)
- Add **keyboard shortcuts** for common actions
- Implement **accessibility** (ARIA labels, keyboard navigation)
- Add **dark mode** support (optional but nice to have)
- Use **Service Worker** for offline capability (PWA)
- Implement **progressive image loading** (thumbnails → full quality)
- Use **Web Workers** for heavy computations (MPR calculations)
- Add **comprehensive logging** for debugging
- Implement **audit trail** for HIPAA compliance

---

## END OF PROMPT

---

## HOW TO USE THIS PROMPT

1. Open Claude Code (VS Code extension or standalone app)
2. Create a new project folder or navigate to your desired location
3. Copy the entire content between "START OF PROMPT" and "END OF PROMPT"
4. Paste into Claude Code
5. Claude will:
   - Create the complete project structure
   - Generate all PHP API files
   - Create React frontend with all components
   - Set up database schema
   - Configure environment files
   - Add deployment instructions
6. Follow the deployment steps for XAMPP or Docker
7. Test all functionality

**Estimated Time**: Claude should complete this in 15-30 minutes

**Result**: A fully functional, modern DICOM viewer with zero database syncing, all features working, and production-ready code.

---

**Document Version**: 1.0
**Date**: 2025-11-19
**Tested**: Ready for Claude Code execution
