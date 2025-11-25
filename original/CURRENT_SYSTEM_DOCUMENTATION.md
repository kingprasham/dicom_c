# Hospital DICOM Viewer Pro - Complete System Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Current Architecture](#current-architecture)
3. [Features & Functionality](#features--functionality)
4. [Technology Stack](#technology-stack)
5. [Data Flow](#data-flow)
6. [Database Schema](#database-schema)
7. [API Endpoints](#api-endpoints)
8. [Current Issues & Pain Points](#current-issues--pain-points)
9. [Modern Best Practices (2025)](#modern-best-practices-2025)

---

## System Overview

**Hospital DICOM Viewer Pro** is a complete web-based medical imaging system that interfaces with Orthanc (an open-source DICOM server). It's designed for hospital radiologists and technicians to view, analyze, and report on DICOM medical images from CT, MRI, X-Ray, and other medical imaging devices.

### Primary Purpose
Bridge local imaging devices (MRI/CT machines) to a web-based viewer with advanced features:
- Multi-Planar Reconstruction (MPR)
- Real-time image analysis and measurements
- Medical report generation with templates
- Remote access via ngrok tunneling
- Database caching of patient/study metadata

---

## Current Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                   MRI/CT IMAGING DEVICE                         │
│              (Sends DICOM files via DICOM protocol)            │
└─────────────────────────┬───────────────────────────────────────┘
                          │ DICOM C-STORE (Port 4242)
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│                      ORTHANC SERVER                             │
│          (Receives and indexes DICOM files locally)            │
│          API: http://localhost:8042                           │
│          Credentials: orthanc/orthanc                         │
└──┬──────────────────────────────┬────────────────────────────┬──┘
   │                              │                            │
   │ (Direct local access)        │ (Production via ngrok)     │
   │                              │                            │
   ▼                              ▼                            │
┌─────────────┐          ┌──────────────────┐                │
│ Web Browser │          │ Python API       │                │
│ (Localhost) │          │ Gateway Port 5000│                │
│             │          │ (Caches DICOM)   │                │
└──────┬──────┘          └────────┬─────────┘                │
       │                          │                          │
       │ (Fetch DICOM)            │ (Tunnel via ngrok)      │
       │                          │                          │
       └──────────────────────────┴──────────────────────────┘
                                  │
                    ┌─────────────▼─────────────┐
                    │  XAMPP PHP Application    │
                    │  (Database cached data)   │
                    │  MySQL: cached_patients,  │
                    │         cached_studies,   │
                    │         dicom_instances   │
                    └───────────┬───────────────┘
                                │
         ┌──────────────────────┼──────────────────────┐
         │                      │                      │
         ▼                      ▼                      ▼
    ┌──────────┐          ┌──────────┐          ┌──────────┐
    │ Patient  │          │ Study    │          │ DICOM    │
    │ List API │          │ List API │          │ File API │
    │ (JSON)   │          │ (JSON)   │          │ (Binary) │
    └─────┬────┘          └─────┬────┘          └─────┬────┘
          │                     │                     │
          └─────────────────────┼─────────────────────┘
                                │
         ┌──────────────────────▼──────────────────────┐
         │      Web Browser (Frontend JS)              │
         │  - Cornerstone.js viewport rendering       │
         │  - MPR volume reconstruction                │
         │  - Medical reporting UI                     │
         │  - Patient/Study management                 │
         └──────────────────────────────────────────────┘
```

### Key Components

#### 1. **Backend - PHP Application**
- **Location**: `c:\xampp\htdocs\papa\dicom_again\`
- **Server**: Apache (XAMPP)
- **Framework**: Vanilla PHP with MySQLi
- **Configuration**: [config.php](config.php:1-65)
  - Environment detection (local/production)
  - Database credentials
  - Orthanc credentials
  - API gateway URLs

#### 2. **Database - MySQL**
- **Name**: `dicom`
- **Engine**: MySQL/MariaDB via XAMPP
- **Tables**:
  - `cached_patients` - Patient metadata cache
  - `cached_studies` - Study metadata cache
  - `dicom_instances` - Instance tracking
  - `users` - User authentication
  - `sessions` - Session management
  - `measurements` - Image measurements
  - `prescriptions` - Medical prescriptions

#### 3. **API Gateway - Python Flask**
- **File**: [orthanc_api_gateway.py](orthanc_api_gateway.py)
- **Port**: 5000
- **Purpose**: Secure proxy between remote clients and local Orthanc
- **Features**:
  - API key authentication
  - DICOM file caching (`/dicom_cache/`)
  - Health check endpoint
  - Custom metadata endpoints

#### 4. **DICOM Server - Orthanc**
- **URL**: `http://localhost:8042`
- **Version**: Latest stable
- **Credentials**: `orthanc` / `orthanc`
- **DICOM Port**: 4242 (C-STORE)
- **Plugins**: Standard Orthanc plugins

#### 5. **Frontend - JavaScript Application**
- **Framework**: Vanilla JavaScript (no React/Vue/Angular)
- **UI Library**: Bootstrap 5.3.3
- **DICOM Libraries**:
  - Cornerstone Core (image rendering)
  - Cornerstone WADO Image Loader
  - Cornerstone Tools (measurements)
  - DICOM Parser
  - Cornerstone Math
- **Architecture**: Component-based with managers

#### 6. **Remote Access - ngrok**
- **Domain**: `brendon-interannular-nonconnectively.ngrok-free.dev`
- **Purpose**: HTTPS tunnel for production remote access
- **Configuration**: Static domain assignment

---

## Features & Functionality

### 1. **Patient Management**
- View patient list with pagination (50 items/page default)
- Advanced filtering:
  - Search by name/ID
  - Filter by sex (M/F/O)
  - Filter by study date range
  - Filter by modality (CT, MRI, XRAY)
  - Filter by study name/description
  - Minimum number of studies filter
- Sort options: name (A-Z, Z-A), date (newest, oldest), studies (most-least)
- **File**: [api/patient_list_api.php](api/patient_list_api.php:1-259)

### 2. **Study Viewing**
- Load studies from Orthanc in real-time or via cache
- Auto-load via URL parameter: `?studyUID=XXXXX`
- Series navigation with slider
- Image counter showing current/total
- Cine mode (play/pause/stop) with FPS control
- **File**: [api/load_study_fast.php](api/load_study_fast.php:1-231)

### 3. **Advanced Image Viewing**

#### Multiple Layout Options
- **1x1**: Single viewport
- **2x1**: Side-by-side comparison
- **2x2**: Quad view with MPR
- **File**: [js/managers/viewport-manager.js](js/managers/viewport-manager.js)

#### MPR (Multi-Planar Reconstruction)
- Axial, Sagittal, Coronal planes
- Synchronized slicing with reference lines
- Volume rendering from single series
- Professional interpolation:
  - Nearest neighbor (fast)
  - Trilinear (balanced)
  - Cubic (high quality)
- **File**: [js/managers/mpr-manager.js](js/managers/mpr-manager.js)

#### Image Enhancement
- Window/Level (W/L) adjustment with sliders
- Presets:
  - Default (Auto W/L)
  - Lung (-600/1500)
  - Abdomen (50/400)
  - Brain (40/80)
  - Bone (400/1000)
- Auto W/L adjustment
- Invert colors
- Flip horizontal/vertical
- Rotate left/right
- **File**: [js/managers/enhancement-manager.js](js/managers/enhancement-manager.js)

#### Measurement Tools
- Length measurement (ruler)
- Angle measurement
- Free-hand ROI (region of interest)
- Elliptical ROI
- Rectangle ROI
- Probe (pixel value sampling)
- **File**: Cornerstone Tools integration

### 4. **Medical Reporting**

#### Report Templates
- CT Head
- CT Chest
- CT Abdomen/Pelvis
- MRI Brain
- X-Ray Chest

#### Report Sections
- **Indication**: Clinical reason for study
- **Technique**: Imaging parameters used
- **Findings**: Detailed observations with subsections
- **Impression**: Diagnostic summary

#### Features
- Auto-save to JSON files
- Version tracking with history (last 10 versions)
- Backup creation with timestamps
- Reporting physician tracking
- Linked to StudyInstanceUID
- **File**: [save_report.php](save_report.php:1-112)

### 5. **Medical Notes & Annotations**
- Per-image notes (stored per SeriesInstanceUID)
- Patient ID, Study Date, Clinical History capture
- Timestamp tracking
- Version history (last 10 versions)
- **Files**: [save_notes.php](save_notes.php), [get_notes.php](get_notes.php)

### 6. **Authentication & Authorization**

#### User Management
- User login with username/password
- Bcrypt password hashing
- Session tokens (64-char hex)
- Role-based access:
  - `admin` - Full access
  - `radiologist` - View, report, annotate
  - `technician` - View, upload
  - `viewer` - View only

#### Session Management
- Session expiration (default 1 hour)
- "Remember me" option (30 days)
- IP address tracking
- User-Agent tracking
- Secure cookies (HttpOnly, SameSite=Strict)
- **Files**: [auth/login.php](auth/login.php), [includes/session.php](includes/session.php)

### 7. **Mobile Support**
- Responsive design (Bootstrap 5)
- Mobile-specific UI controls at bottom
- Touch gesture support (pan, zoom via Hammer.js)
- Collapsible sidebar on mobile
- Optimized header (48px on mobile vs 58px on desktop)
- **File**: [js/components/mobile-controls.js](js/components/mobile-controls.js)

### 8. **Data Export**
- Export as PNG image
- Export as PDF report
- Export raw DICOM files
- Export MPR views
- Print functionality with preview
- **File**: [js/components/export-manager.js](js/components/export-manager.js)

---

## Technology Stack

### Backend
- **PHP**: 8.2+
- **Database Driver**: MySQLi
- **Python**: 3.11+ (API Gateway)
- **Flask**: Latest (Python web framework)

### Frontend
- **JavaScript**: ES6+ (Vanilla, no framework)
- **CSS Framework**: Bootstrap 5.3.3
- **Medical Libraries**:
  - Cornerstone Core 2.x
  - Cornerstone WADO Image Loader
  - Cornerstone Tools
  - DICOM Parser
  - Cornerstone Math
  - Hammer.js (touch gestures)

### Infrastructure
- **Web Server**: Apache (XAMPP)
- **Database**: MySQL/MariaDB (XAMPP)
- **DICOM Server**: Orthanc
- **Tunneling**: ngrok
- **Scheduler**: Windows Task Scheduler (for auto-sync)

### Protocols
- **HTTP/HTTPS**: RESTful APIs
- **DICOM**: C-STORE (port 4242)
- **Authentication**: Custom session-based

---

## Data Flow

### 1. DICOM Ingestion
```
MRI/CT Device → (DICOM C-STORE) → Orthanc Server → File Storage
```

### 2. Metadata Caching (Current - Manual)
```
PHP sync_orthanc.php → Orthanc REST API → MySQL (cached_patients, cached_studies)
```

### 3. Patient List Retrieval
```
Browser → patient_list_api.php → MySQL cached_patients → JSON Response
```

### 4. Study Retrieval
```
Browser → load_study_fast.php → [MySQL cache OR Orthanc API] → JSON Response
```

### 5. DICOM Image Loading (Local)
```
Browser → get_dicom_from_orthanc.php → Orthanc /instances/{id}/file → DICOM Binary
```

### 6. DICOM Image Loading (Production)
```
Browser → get_dicom_via_gateway.php → ngrok tunnel → Python API Gateway →
  → Cache Check → [Return cached OR Fetch from Orthanc] → Base64 DICOM → Browser
```

### 7. Image Rendering
```
DICOM Binary → Cornerstone WADO Loader → Parse DICOM → Render to Canvas → Display
```

### 8. MPR Reconstruction
```
Series DICOM Stack → Volume Buffer (Float32Array) →
  → Slice Plane Calculation → Interpolation → Orthogonal View
```

---

## Database Schema

### `cached_patients`
```sql
CREATE TABLE cached_patients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id VARCHAR(255) UNIQUE,      -- DICOM Patient ID
    orthanc_id VARCHAR(255),              -- Orthanc system ID
    patient_name VARCHAR(500),            -- Full name
    patient_sex CHAR(1),                  -- M/F/O
    patient_birth_date DATE,
    study_count INT DEFAULT 0,
    last_study_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_synced TIMESTAMP
);
```

### `cached_studies`
```sql
CREATE TABLE cached_studies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    study_instance_uid VARCHAR(255),      -- DICOM Study UID
    orthanc_id VARCHAR(255),              -- Orthanc study ID
    patient_id VARCHAR(255),              -- FK to patient
    study_date DATE,
    study_time TIME,
    study_description TEXT,
    study_name VARCHAR(500),
    accession_number VARCHAR(255),
    modality VARCHAR(50),                 -- CT, MRI, XRAY, etc.
    series_count INT,
    instance_count INT,
    instances_count INT,                  -- Duplicate field
    last_synced TIMESTAMP,
    created_at TIMESTAMP,
    study_id VARCHAR(100),
    is_starred TINYINT                    -- Favorite flag
);
```

### `users`
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),           -- bcrypt
    session_token VARCHAR(255),
    full_name VARCHAR(255),
    email VARCHAR(255),
    role ENUM('admin', 'radiologist', 'technician', 'viewer') DEFAULT 'viewer',
    is_active TINYINT DEFAULT 1,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `sessions`
```sql
CREATE TABLE sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255),
    session_token VARCHAR(255) UNIQUE,    -- 64-char hex
    user_id INT,                          -- FK to users.id
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP,
    expires_at DATETIME,
    last_activity TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### File-Based Storage
- `/reports/*.json` - Medical reports indexed by StudyInstanceUID
- `/notes/notes_*.json` - Image-specific notes keyed by SeriesInstanceUID
- `/dicom_files/` - Optional local DICOM storage
- `/logs/*.log` - Application and error logs
- `/dicom_cache/` - API Gateway DICOM cache (organized by instance ID prefix)

---

## API Endpoints

### Authentication
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/auth/login.php` | POST | User authentication | [auth/login.php](auth/login.php) |
| `/auth/logout.php` | POST | Session termination | [auth/logout.php](auth/logout.php) |
| `/auth/check_session.php` | GET | Session validation | [auth/check_session.php](auth/check_session.php) |

### Patient & Study Management
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/api/patient_list_api.php` | GET | List patients with filters | [api/patient_list_api.php](api/patient_list_api.php:1-259) |
| `/api/study_list_api.php` | GET | List studies for patient | [api/study_list_api.php](api/study_list_api.php:1-134) |
| `/api/load_study_fast.php` | GET | Load study with instances | [api/load_study_fast.php](api/load_study_fast.php:1-231) |

### DICOM File Serving
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/api/get_dicom_from_orthanc.php` | GET | Fetch DICOM file (local/gateway) | [api/get_dicom_from_orthanc.php](api/get_dicom_from_orthanc.php:1-90) |
| `/api/get_dicom_via_gateway.php` | GET | Fetch via API Gateway (prod) | [api/get_dicom_via_gateway.php](api/get_dicom_via_gateway.php:1-62) |

### Synchronization
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/sync_orthanc.php` | GET | Sync patients/studies (HTML) | [sync_orthanc.php](sync_orthanc.php:1-246) |
| `/api/sync_orthanc_api.php` | POST | Sync patients/studies (JSON) | [api/sync_orthanc_api.php](api/sync_orthanc_api.php:1-244) |

### Medical Reporting
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/save_report.php` | POST | Save medical report | [save_report.php](save_report.php:1-112) |
| `/load_report.php` | GET | Load medical report | [load_report.php](load_report.php) |
| `/save_notes.php` | POST | Save image notes | [save_notes.php](save_notes.php:1-58) |
| `/get_notes.php` | GET | Get image notes | [get_notes.php](get_notes.php:1-43) |

### Utilities
| Endpoint | Method | Purpose | File |
|----------|--------|---------|------|
| `/toggle_star.php` | POST | Star/favorite study | [toggle_star.php](toggle_star.php:1-57) |
| `/get_measurements.php` | GET | Get image measurements | [get_measurements.php](get_measurements.php:1-41) |

### API Gateway (Python Flask - Port 5000)
| Endpoint | Method | Purpose | Auth Required |
|----------|--------|---------|---------------|
| `/health` | GET | Health check | No |
| `/gateway/studies/{studyId}/instances` | GET | Get study instances with metadata | Yes (API Key) |
| `/api/instances/{instanceId}/file` | GET | Get cached DICOM file | Yes (API Key) |
| `/api/orthanc/{endpoint}` | ANY | Proxy to Orthanc | Yes (API Key) |

---

## Current Issues & Pain Points

### 1. **Database Syncing (MAJOR ISSUE)**
- **Problem**: Manual sync required via `sync_orthanc.php`
- **Impact**:
  - New patients don't appear until manual sync
  - Requires scheduled batch job or manual intervention
  - Data staleness - cache can be outdated
- **Files**:
  - [sync_orthanc.php](sync_orthanc.php:1-246)
  - Multiple .bat files: `AUTO_SYNC_FROM_PRODUCTION.bat`, `AUTO_SYNC_LOCAL.bat`

### 2. **Environment Configuration Complexity**
- **Problem**: Two distinct environments (local/production) with hardcoded configs
- **Impact**:
  - Easy to misconfigure
  - API Gateway URL hardcoded
  - Database credentials in config file
- **File**: [config.php](config.php:1-65)

### 3. **ngrok Dependency**
- **Problem**: Remote access relies on ngrok with static domain
- **Issues**:
  - Domain could change or expire
  - Service availability dependency
  - Hardcoded domain: `brendon-interannular-nonconnectively.ngrok-free.dev`

### 4. **API Gateway Startup & Management**
- **Problem**: Python API Gateway requires manual startup
- **Commands**: Must kill existing python.exe, then start gateway, then verify health
- **Files**: [orthanc_api_gateway.py](orthanc_api_gateway.py), batch startup scripts

### 5. **CORS Issues**
- **Problem**: Browser CORS restrictions when accessing Orthanc directly
- **Solution**: Proxy through PHP instead of direct AJAX
- **Evidence**: Multiple CORS enable/disable batch files

### 6. **Session Management Complexity**
- **Problem**: Custom session implementation in database
- **Issues**:
  - Token validation on every API call (performance)
  - Need to manage expiration
  - Remember-me feature adds complexity
- **File**: [includes/session.php](includes/session.php:40-106)

### 7. **Multiple Database Connection Approaches**
- **Problem**: Inconsistent DB connection code
- **Evidence**: Some files use `db_connect.php`, others use `includes\db.php`

### 8. **Deprecated/Legacy Code**
- **Problem**: Multiple "ORIGINAL" backup files
- **Files**: `ORIGINAL_js_main.js.txt`, `ORIGINAL_WORKING_main.js`

### 9. **Batch Script Fragmentation**
- **Problem**: 17+ .bat files for various scenarios
- **Impact**: Confusion on which to use; maintenance nightmare

### 10. **Report Storage Inconsistency**
- **Problem**: Reports stored in `/reports/` as JSON files
- **Issues**:
  - Not in database - scalability issue
  - File system dependent
  - No built-in backup strategy

### 11. **Performance Issues**
- **Problem**:
  - Large studies (100+ images) slow to load
  - MPR volume reconstruction computationally intensive
  - No lazy loading or progressive rendering

### 12. **Duplicate Database Fields**
- **Problem**: `cached_studies` has both `instance_count` and `instances_count`
- **Impact**: Data consistency issues

---

## Modern Best Practices (2025)

Based on research of modern DICOM web viewers and medical imaging systems:

### 1. **DICOMweb API Integration**
- **Standard**: Use DICOMweb APIs (WADO-RS, QIDO-RS, STOW-RS)
- **Benefit**: Eliminate database syncing - query Orthanc directly
- **Implementation**: Orthanc has official DICOMweb plugin
- **Reference**: https://orthanc.uclouvain.be/book/plugins/dicomweb.html

### 2. **Cornerstone3D Migration**
- **Current**: Using legacy Cornerstone Core 2.x
- **Recommended**: Migrate to Cornerstone3D with VTK.js
- **Benefits**:
  - 50% memory reduction (VoxelManager)
  - Native 3D rendering and segmentation
  - Better MPR performance
  - TypeScript support
- **Reference**: OHIF Viewer 3.9+ uses Cornerstone3D 2.0

### 3. **Modern Frontend Framework**
- **Current**: Vanilla JavaScript
- **Recommended**: React 18+ with concurrent rendering
- **Benefits**:
  - Component reusability
  - Better state management
  - Faster updates with large datasets
- **Reference**: OHIF v3 architecture

### 4. **Real-time Change Detection**
- **Current**: Manual sync
- **Recommended**: Orthanc `/changes` endpoint with polling
- **Implementation**: Long-polling or WebSocket for real-time updates
- **Reference**: Orthanc REST API documentation

### 5. **Cloud-Native & Modular Architecture**
- **Pattern**: Microservices with clear separation
- **Components**:
  - API Gateway (authentication, routing)
  - DICOM Service (Orthanc interface)
  - Viewer Frontend (React/Vue)
  - Report Service (separate from viewer)

### 6. **Security Best Practices**
- OAuth2/SAML instead of custom session management
- JWT tokens for API authentication
- Role-based access control (RBAC) with proper middleware
- End-to-end encryption
- HIPAA/GDPR compliance features

### 7. **Performance Optimization**
- Progressive image loading (thumbnails first, full quality on demand)
- Image caching at edge locations
- Lazy loading for large study lists
- Web Workers for MPR calculations
- Service Workers for offline capability

### 8. **Deployment & DevOps**
- Docker containerization (Orthanc, API, Frontend)
- Environment-based configuration (`.env` files)
- CI/CD pipelines
- Auto-scaling for cloud deployments
- Health checks and monitoring

### 9. **Data Management**
- Store reports in database, not file system
- Use Orthanc's built-in storage for DICOM files
- Implement proper backup strategies
- Database migrations for schema changes

### 10. **Mobile-First Design**
- Progressive Web App (PWA)
- Touch-optimized UI
- Responsive layouts with CSS Grid/Flexbox
- Offline capability with Service Workers

---

## Summary

This system is a sophisticated medical imaging platform with advanced features including MPR, medical reporting, and multi-user authentication. However, it suffers from:

1. **Manual database synchronization** - the biggest pain point
2. **Complex deployment** - multiple batch scripts and manual steps
3. **Legacy architecture** - could benefit from modern frameworks

The improved version should:
- **Eliminate database syncing** by using DICOMweb APIs directly
- **Simplify deployment** with Docker and environment configs
- **Modernize frontend** with React and Cornerstone3D
- **Improve security** with OAuth2/JWT
- **Enhance performance** with progressive loading and Web Workers

---

**Generated**: 2025-11-19
**Version**: 1.0
**Author**: System Analysis
