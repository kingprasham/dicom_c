<?php
// Start session and check authentication
define('DICOM_VIEWER', true);
require_once __DIR__ . '/auth/session.php';

// Redirect to login if not authenticated
requireLogin();

// Redirect to dashboard if no study selected (optional, but good UX)
if (empty($_GET['study_id']) && empty($_GET['series_id']) && empty($_GET['studyUID']) && empty($_GET['orthancId'])) {
    // Check if we are just landing here or if we want to show empty viewer
    // For now, let's redirect to dashboard to pick a study
    header('Location: dashboard.php');
    exit;
}

// BASE_PATH and BASE_URL are now defined in config.php (loaded via session.php)

// Get user info
$userName = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'viewer';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="base-path" content="<?= BASE_PATH ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title>DICOM Viewer Pro</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/css/styles.css">
    <style>
        /* Mobile-First Responsive Enhancements */
        body {
            overscroll-behavior: none;
            -webkit-overflow-scrolling: touch;
        }

        /* Hide complex controls on mobile */
        @media (max-width: 767px) {
            .navbar-brand span {
                display: none;
            }
            .navbar-brand::after {
                content: "DICOM";
                font-size: 1.1rem;
                font-weight: bold;
            }
            #uploadForm, #exportBtn, #settingsBtn {
                display: none !important;
            }
            .sidebar:last-child {
                display: none !important;
            }
            .mpr-controls {
                display: none !important;
            }
        }

        /* Mobile header optimization */
        @media (max-width: 767px) {
            header {
                height: 48px !important;
                padding: 0.25rem !important;
            }
            header .container-fluid {
                padding: 0 0.5rem;
            }
            .main-layout {
                height: calc(100vh - 48px) !important;
            }
        }

        /* Mobile sidebar - collapsible */
        @media (max-width: 767px) {
            .sidebar:first-child {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                max-height: 50vh;
                background: #1c2128;
                border-top: 2px solid #0d6efd;
                transition: transform 0.3s ease;
            }
            .sidebar:first-child.collapsed {
                transform: translateY(calc(100% - 50px));
            }
            .sidebar-toggle-btn {
                display: block;
                width: 100%;
                background: #0d6efd;
                border: none;
                color: white;
                padding: 0.75rem;
                font-weight: bold;
                text-align: center;
            }
            .series-list-container {
                max-height: calc(50vh - 100px);
            }
        }

        /* Mobile viewport */
        @media (max-width: 767px) {
            .viewport-container {
                height: calc(100vh - 48px) !important;
                grid-template-columns: 1fr !important;
                grid-template-rows: 1fr !important;
                padding: 0 !important;
                gap: 0 !important;
            }
            .viewport-container.layout-2x2,
            .viewport-container.layout-2x1 {
                grid-template-columns: 1fr !important;
                grid-template-rows: 1fr !important;
            }
        }

        /* Image Thumbnail Selector */
        .image-thumbnails {
            display: none;
            position: fixed;
            bottom: 60px;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.9);
            padding: 10px;
            z-index: 999;
            overflow-x: auto;
            white-space: nowrap;
            border-top: 2px solid #0d6efd;
        }
        .image-thumbnails.show {
            display: block;
        }
        .thumbnail-item {
            display: inline-block;
            width: 80px;
            height: 80px;
            margin: 0 5px;
            border: 2px solid #444;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: #000;
        }
        .thumbnail-item.active {
            border-color: #0d6efd;
            box-shadow: 0 0 10px #0d6efd;
        }
        .thumbnail-item img,
        .thumbnail-item canvas {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .thumbnail-number {
            position: absolute;
            top: 2px;
            left: 2px;
            background: rgba(13,110,253,0.8);
            color: white;
            padding: 2px 6px;
            font-size: 0.7rem;
            border-radius: 3px;
        }

        /* Mobile Tools - Bottom Bar */
        @media (max-width: 767px) {
            .mobile-tools-bar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(28,33,40,0.95);
                backdrop-filter: blur(10px);
                display: flex;
                justify-content: space-around;
                align-items: center;
                padding: 0.5rem;
                border-top: 1px solid #0d6efd;
                z-index: 1001;
            }
            .mobile-tools-bar button {
                flex: 1;
                margin: 0 3px;
                padding: 0.5rem;
                border: none;
                background: #1c2128;
                color: white;
                border-radius: 8px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                font-size: 0.75rem;
            }
            .mobile-tools-bar button.active {
                background: #0d6efd;
                box-shadow: 0 0 10px rgba(13,110,253,0.5);
            }
            .mobile-tools-bar button i {
                font-size: 1.2rem;
                margin-bottom: 2px;
            }
        }

        /* Fullscreen mode */
        .fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            background: #000;
        }
        .fullscreen-mode .viewport-container {
            height: 100vh !important;
        }
        .fullscreen-exit-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 10000;
            background: rgba(13,110,253,0.8);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 1.2rem;
        }

        /* Viewport touch improvements */
        .viewport {
            touch-action: none;
            -webkit-user-select: none;
            user-select: none;
        }

        /* Loading indicator */
        .loading-progress {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10001;
            background: rgba(0,0,0,0.9);
            padding: 20px 30px;
            border-radius: 10px;
            border: 2px solid #0d6efd;
        }

        /* Desktop - keep sidebar visible */
        @media (min-width: 768px) {
            .mobile-tools-bar {
                display: none;
            }
            .image-thumbnails {
                display: none;
            }
            .sidebar-toggle-btn {
                display: none;
            }
        }
    </style>

    <!-- PDF Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- DICOM Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/dicom-parser@1.8.21/dist/dicomParser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-core@2.6.1/dist/cornerstone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-math@0.1.10/dist/cornerstoneMath.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/cornerstone-wado-image-loader@3.1.2/dist/cornerstoneWADOImageLoader.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/cornerstone-tools@5.1.5/dist/cornerstoneTools.min.js"></script>
    <script>
        // CRITICAL FIX: Image loading for remote storage with BASE_PATH support
        window.DICOM_VIEWER = window.DICOM_VIEWER || {};
        window.DICOM_VIEWER.getImageUrl = function (image) {
            if (!image) return null;

            const basePath = '<?= BASE_PATH ?>';
            const baseUrl = '<?= BASE_URL ?>';

            if (image.isOrthancImage && image.orthancInstanceId) {
                const instanceId = image.orthancInstanceId;
                // Use direct Orthanc proxy endpoint
                return `wadouri:${basePath}/api/get_dicom_from_orthanc.php?instanceId=${instanceId}`;
            }

            if (image.instanceId) {
                return `wadouri:${basePath}/api/get_dicom_from_orthanc.php?instanceId=${image.instanceId}`;
            }

            if (image.id) {
                return `wadouri:${basePath}/api/get_dicom_from_orthanc.php?instanceId=${image.id}`;
            }

            return null;
        };
        console.log('Image URL helper loaded with BASE_PATH:', '<?= BASE_PATH ?>');
    </script>
</head>

<body>
    <div id="loadingProgress" class="loading-progress" style="display: none;">
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            <span>Loading images...</span>
        </div>
    </div>

    <header class="navbar navbar-expand-lg bg-body-tertiary border-bottom" style="height: 58px;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="<?= BASE_PATH ?>/">
                <i class="bi bi-heart-pulse-fill text-primary fs-4 me-2"></i>
                <span class="fw-semibold">DICOM Viewer Pro - Enhanced MPR</span>
            </a>
            <div id="report-indicator" class="ms-3" style="display: none;">
                <span class="badge bg-success d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-text-fill"></i> Report Attached
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <form id="uploadForm" enctype="multipart/form-data" class="m-0">
                    <div class="btn-group">
                        <label for="dicomFolderInput" class="btn btn-primary" style="cursor: pointer;">
                            <i class="bi bi-folder2-open me-2"></i>Open Folder
                        </label>
                        <button class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                            data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="uploadFolder">Folder (Default)</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="#" id="uploadSeries">Select Multiple Files</a></li>
                            <li><a class="dropdown-item" href="#" id="uploadSingle">Select Single File</a></li>
                        </ul>
                    </div>

                    <input type="file" id="dicomFileInput" name="dicomFile" class="d-none" accept=".dcm,.dicom"
                        multiple>
                    <input type="file" id="dicomFolderInput" name="dicomFolder" class="d-none" webkitdirectory directory
                        multiple>
                </form>
                <div class="btn-group">
                    <button class="btn btn-secondary" id="exportBtn"><i class="bi bi-download me-2"></i>Export</button>
                    <button class="btn btn-secondary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown"></button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" id="exportImage">Export as Image</a></li>
                        <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-file-pdf me-2"></i>Download as PDF</a></li>
                        <li><a class="dropdown-item" href="#" id="exportReport">Export Report</a></li>
                        <li><a class="dropdown-item" href="#" id="exportDicom">Export DICOM</a></li>
                        <li><a class="dropdown-item" href="#" id="exportMPR">Export MPR Views</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="#" id="createMedicalReport">
                                <i class="bi bi-file-medical me-2"></i>Create Medical Report
                            </a></li>
                    </ul>
                </div>
                <button class="btn btn-secondary" id="printBtn" title="Print DICOM Image"><i class="bi bi-printer"></i></button>
                <button class="btn btn-secondary" id="settingsBtn"><i class="bi bi-gear"></i></button>
                <button class="btn btn-secondary" id="fullscreenBtn"><i class="bi bi-arrows-fullscreen"></i></button>
            </div>
        </div>
    </header>

    <div class="main-layout">
        <aside class="sidebar bg-body-tertiary border-end" id="leftSidebar">
            <button class="sidebar-toggle-btn" onclick="document.getElementById('leftSidebar').classList.toggle('collapsed')">
                <i class="bi bi-list"></i> Series & Controls
            </button>
            <div class="sidebar-section"
                style="padding: 1rem; flex-shrink: 0; border-bottom: 1px solid var(--bs-border-color);">
                <h6 class="text-light mb-2">Series Navigation</h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="enableMPR" checked>
                    <label class="form-check-label small text-success" for="enableMPR">
                        <i class="bi bi-layers"></i> Enable MPR Views
                    </label>
                </div>
            </div>

            <div class="series-list-container" id="series-list">
                <div class="text-center text-muted small p-4">
                    No DICOM files uploaded
                </div>
            </div>

            <div class="sidebar-section fixed-section navigation-section">
                <h6 class="text-light mb-2">Image Navigation</h6>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <button class="btn btn-sm btn-secondary" id="prevImage">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="small text-muted flex-fill text-center" id="imageCounter">- / -</span>
                    <button class="btn btn-sm btn-secondary" id="nextImage">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
                <input type="range" class="form-range" id="imageSlider" min="0" max="0" value="0">

                <div class="mt-2" id="mprNavigation" style="display: none;">
                    <small class="text-success">MPR Slice Control</small>
                    <div class="row g-1 mt-1">
                        <div class="col-4">
                            <small class="text-muted d-block">Axial</small>
                            <input type="range" class="form-range form-range-sm" id="axialSlider" min="0" max="100"
                                value="50">
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Sagittal</small>
                            <input type="range" class="form-range form-range-sm" id="sagittalSlider" min="0" max="100"
                                value="50">
                        </div>
                        <div class="col-4">
                            <small class="text-muted d-block">Coronal</small>
                            <input type="range" class="form-range form-range-sm" id="coronalSlider" min="0" max="100"
                                value="50">
                        </div>
                    </div>
                </div>
            </div>

            <div class="sidebar-section fixed-section cine-section">
                <h6 class="text-light mb-2">Cine Controls</h6>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-secondary" id="playPause">
                        <i class="bi bi-play-fill"></i>
                    </button>
                    <button class="btn btn-sm btn-secondary" id="stopCine">
                        <i class="bi bi-stop-fill"></i>
                    </button>
                    <small class="text-muted">FPS:</small>
                    <input type="range" class="form-range flex-fill" id="fpsSlider" min="1" max="30" value="10">
                    <small class="text-muted" id="fpsDisplay">10</small>
                </div>
            </div>
        </aside>

        <main id="main-content" class="d-flex flex-column" style="background-color: #000;">
            <div class="mpr-controls">
                <div class="top-controls-bar">
                    <div class="controls-group-left">
                        <div class="control-group">
                            <span class="control-label">Layout:</span>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-secondary" data-layout="1x1"><i
                                        class="bi bi-app"></i></button>
                                <button type="button" class="btn btn-sm btn-primary" data-layout="2x2"><i
                                        class="bi bi-grid-fill"></i></button>
                                <button type="button" class="btn btn-sm btn-secondary" data-layout="2x1"><i
                                        class="bi bi-layout-split"></i></button>
                            </div>
                        </div>
                        <div class="control-group">
                            <span class="control-label">MPR:</span>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    id="mprAxial">Axial</button>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    id="mprSagittal">Sagittal</button>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    id="mprCoronal">Coronal</button>
                                <button type="button" class="btn btn-sm btn-outline-success" id="mprAll">All
                                    Views</button>
                            </div>
                        </div>
                        <div class="control-group">
                            <span class="control-label">Sync:</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="stackScroll" checked>
                                <label class="form-check-label small" for="stackScroll">Stack Scroll</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="syncWL" checked>
                                <label class="form-check-label small" for="syncWL">W/L</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="showCrosshairs" checked>
                                <label class="form-check-label small" for="showCrosshairs">Crosshairs</label>
                            </div>
                        </div>
                    </div>

                    <div class="controls-group-right">
                        <div class="control-group">
                            <button class="btn btn-sm btn-secondary" id="resetBtn" title="Reset">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" id="invertBtn" title="Invert">
                                <i class="bi bi-circle-half"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" id="flipHBtn" title="Flip Horizontal">
                                <i class="bi bi-arrow-left-right"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" id="flipVBtn" title="Flip Vertical">
                                <i class="bi bi-arrow-down-up"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" id="rotateLeftBtn" title="Rotate Left">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary" id="rotateRightBtn" title="Rotate Right">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="viewport-container" class="viewport-container layout-2x2">
                <div class="card bg-dark text-light text-center">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <h5 class="card-title text-muted">No DICOM file selected</h5>
                        <p class="card-text text-muted small">Upload and select a DICOM file to begin viewing with
                            automatic MPR reconstruction</p>
                    </div>
                </div>
            </div>
        </main>

        <aside class="sidebar bg-body-tertiary border-start">
            <div class="p-3 border-bottom">
                <h6 class="text-light mb-2">Tools</h6>
                <div class="row row-cols-3 g-1" id="tools-panel">
                    <div class="col"><button data-tool="Pan"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-arrows-move"></i><span class="small">Pan</span></button></div>
                    <div class="col"><button data-tool="Zoom"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-zoom-in"></i><span class="small">Zoom</span></button></div>
                    <div class="col"><button data-tool="Wwwc"
                            class="btn btn-primary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-sliders"></i><span class="small">W/L</span></button></div>
                    <div class="col"><button data-tool="Length"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-rulers"></i><span class="small">Length</span></button></div>
                    <div class="col"><button data-tool="Angle"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-triangle"></i><span class="small">Angle</span></button></div>
                    <div class="col"><button data-tool="FreehandRoi"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-pencil"></i><span class="small">Draw</span></button></div>
                    <div class="col"><button data-tool="EllipticalRoi"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-circle"></i><span class="small">Circle</span></button></div>
                    <div class="col"><button data-tool="RectangleRoi"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-square"></i><span class="small">Rectangle</span></button></div>
                    <div class="col"><button data-tool="Probe"
                            class="btn btn-secondary w-100 tool-btn d-flex flex-column justify-content-center align-items-center"><i
                                class="bi bi-eyedropper"></i><span class="small">Probe</span></button></div>
                </div>
            </div>

            <div class="sidebar-scrollable">
                <div class="p-3 border-bottom">
                    <h6 class="text-light mb-2">Window/Level Presets</h6>
                    <div class="d-grid gap-1">
                        <button class="btn btn-sm btn-outline-secondary preset-btn"
                            data-preset="default">Default</button>
                        <button class="btn btn-sm btn-outline-secondary preset-btn" data-preset="lung">Lung
                            (-600/1500)</button>
                        <button class="btn btn-sm btn-outline-secondary preset-btn" data-preset="abdomen">Abdomen
                            (50/400)</button>
                        <button class="btn btn-sm btn-outline-secondary preset-btn" data-preset="brain">Brain
                            (40/80)</button>
                        <button class="btn btn-sm btn-outline-secondary preset-btn" data-preset="bone">Bone
                            (400/1000)</button>
                    </div>

                    <div class="mt-3">
                        <label class="form-label small text-light mb-1">Window Width</label>
                        <input type="range" class="form-range" id="windowSlider" min="1" max="4000" value="400">
                        <small class="text-muted" id="windowValue">400</small>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small text-light mb-1">Window Level</label>
                        <input type="range" class="form-range" id="levelSlider" min="-1000" max="1000" value="40">
                        <small class="text-muted" id="levelValue">40</small>
                    </div>
                </div>


                <div class="p-3 border-bottom">
                    <h6 class="text-light mb-2">AI Assistant</h6>
                    <div class="d-grid gap-1">
                        <button class="btn btn-sm btn-outline-info" id="autoAdjustWL">Auto W/L</button>
                        <button class="btn btn-sm btn-outline-info" id="detectAbnormalities">Detect
                            Abnormalities</button>
                        <button class="btn btn-sm btn-outline-info" id="measureDistance">Smart Measure</button>
                        <button class="btn btn-sm btn-outline-info" id="enhanceImage">Enhance Quality</button>
                    </div>
                    <div class="mt-2">
                        <div id="aiSuggestions" class="small text-info" style="display: none;">
                            <div class="bg-info bg-opacity-10 p-2 rounded">
                                <strong>AI Suggestion:</strong>
                                <div id="suggestionText">Ready to assist with image analysis</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </aside>
    </div>

    <!-- Mobile Tools Bar -->
    <div class="mobile-tools-bar">
        <button id="mobilePanTool" data-tool="Pan">
            <i class="bi bi-arrows-move"></i>
            <span>Pan</span>
        </button>
        <button id="mobileZoomTool" data-tool="Zoom">
            <i class="bi bi-zoom-in"></i>
            <span>Zoom</span>
        </button>
        <button id="mobileWLTool" data-tool="Wwwc" class="active">
            <i class="bi bi-sliders"></i>
            <span>W/L</span>
        </button>
        <button id="mobileImagesList">
            <i class="bi bi-grid-3x3"></i>
            <span>Images</span>
        </button>
        <button id="mobileFullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
            <span>Full</span>
        </button>
    </div>

    <!-- Image Thumbnails Selector -->
    <div class="image-thumbnails" id="imageThumbnails">
        <!-- Thumbnails will be populated here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Load utilities first -->
    <script src="<?= BASE_PATH ?>/js/utils/constants.js"></script>
    <script src="<?= BASE_PATH ?>/js/utils/cornerstone-init.js"></script>

    <!-- Load managers -->
    <script src="<?= BASE_PATH ?>/js/managers/enhancement-manager.js"></script>
    <script src="<?= BASE_PATH ?>/js/managers/crosshair-manager.js"></script>
    <script src="<?= BASE_PATH ?>/js/managers/viewport-manager.js"></script>
    <script src="<?= BASE_PATH ?>/js/managers/mpr-manager.js"></script>
    <script src="<?= BASE_PATH ?>/js/managers/reference-lines-manager.js"></script>

    <!-- Load components -->
    <script src="<?= BASE_PATH ?>/js/components/upload-handler.js"></script>
    <script src="<?= BASE_PATH ?>/js/components/ui-controls.js"></script>
    <script src="<?= BASE_PATH ?>/js/components/event-handlers.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/medical-notes.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/reporting-system.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/mouse-controls.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/export-manager.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/print-manager.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/settings-manager.js?v=<?= time() ?>"></script>
    <script src="<?= BASE_PATH ?>/js/components/mobile-controls.js?v=<?= time() ?>"></script>


    <script src="https://unpkg.com/dicom-parser@1.8.21/dist/dicomParser.min.js"></script>


    <!-- Load main application -->
    <script src="<?= BASE_PATH ?>/js/main.js"></script>
    <script src="<?= BASE_PATH ?>/js/orthanc-autoload.js"></script>

    <script>
        // Fix sidebar visibility on load
        document.addEventListener('DOMContentLoaded', function() {
            const leftSidebar = document.getElementById('leftSidebar');
            const isMobile = window.innerWidth < 768;

            if (isMobile && leftSidebar) {
                // Collapse sidebar on mobile by default
                leftSidebar.classList.add('collapsed');
            } else if (leftSidebar) {
                // Ensure sidebar is visible on desktop
                leftSidebar.classList.remove('collapsed');
            }

            // Check for report existence and show indicator
            checkReportExistence();
        });

        // Function to check if a report exists for the current study
        async function checkReportExistence() {
            const urlParams = new URLSearchParams(window.location.search);
            const studyUID = urlParams.get('studyUID');

            if (!studyUID) {
                console.log('No studyUID in URL, skipping report check');
                return;
            }

            try {
                const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
                const response = await fetch(`${basePath}/api/reports/by-study.php?studyUID=${encodeURIComponent(studyUID)}`);
                const data = await response.json();

                const reportIndicator = document.getElementById('report-indicator');
                if (data.success && data.data && data.data.count > 0) {
                    // Report exists - show indicator
                    if (reportIndicator) {
                        reportIndicator.style.display = 'block';
                        const report = data.data.reports[0];
                        const statusBadge = reportIndicator.querySelector('.badge');

                        // Update badge based on status
                        if (report.status === 'final') {
                            statusBadge.className = 'badge bg-success d-flex align-items-center gap-1';
                            statusBadge.innerHTML = '<i class="bi bi-file-earmark-check-fill"></i> Report (Final)';
                        } else if (report.status === 'printed') {
                            statusBadge.className = 'badge bg-info d-flex align-items-center gap-1';
                            statusBadge.innerHTML = '<i class="bi bi-printer-fill"></i> Report (Printed)';
                        } else {
                            statusBadge.className = 'badge bg-warning d-flex align-items-center gap-1';
                            statusBadge.innerHTML = '<i class="bi bi-file-earmark-text-fill"></i> Report (Draft)';
                        }
                    }
                } else {
                    // No report - hide indicator
                    if (reportIndicator) {
                        reportIndicator.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error checking report existence:', error);
                // Hide indicator on error
                const reportIndicator = document.getElementById('report-indicator');
                if (reportIndicator) {
                    reportIndicator.style.display = 'none';
                }
            }
        }

        // Make checkReportExistence globally available so it can be called after report save
        window.checkReportExistence = checkReportExistence;
    </script>
</body>

</html>
