/**
 * Modern DICOM Print Manager
 * Provides professional medical imaging printing capabilities
 */

window.DICOM_VIEWER = window.DICOM_VIEWER || {};

window.DICOM_VIEWER.PrintManager = class {
    constructor() {
        this.setupPrintButton();
    }

    setupPrintButton() {
        const printBtn = document.getElementById('printBtn');
        if (printBtn) {
            printBtn.addEventListener('click', () => this.showPrintDialog());
        }
    }

    showPrintDialog() {
        const state = window.DICOM_VIEWER.STATE;

        if (!state.currentSeriesImages || state.currentSeriesImages.length === 0) {
            alert('No images loaded to print');
            return;
        }

        // Create print dialog modal
        const modalHTML = `
            <div class="modal fade" id="printDialog" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title"><i class="bi bi-printer me-2"></i>Print DICOM Images</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Print Range</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="printRange" id="printCurrent" value="current" checked>
                                    <label class="form-check-label" for="printCurrent">
                                        Current Image Only
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="printRange" id="printAll" value="all">
                                    <label class="form-check-label" for="printAll">
                                        All Images in Series (${state.totalImages} images)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="printRange" id="printActive" value="active">
                                    <label class="form-check-label" for="printActive">
                                        Active Viewport Only
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Layout (Images per Page)</label>
                                <select class="form-select bg-dark text-light border-secondary" id="printLayout">
                                    <option value="1x1">1 image per page (Full Page)</option>
                                    <option value="2x2" selected>4 images per page (2×2 Grid)</option>
                                    <option value="3x3">9 images per page (3×3 Grid)</option>
                                    <option value="4x4">16 images per page (4×4 Grid)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Include Information</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="printPatientInfo" checked>
                                    <label class="form-check-label" for="printPatientInfo">
                                        Patient Information Overlay
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="printAnnotations" checked>
                                    <label class="form-check-label" for="printAnnotations">
                                        Measurements & Annotations
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="printWindowLevel" checked>
                                    <label class="form-check-label" for="printWindowLevel">
                                        Window/Level Information
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Print Quality</label>
                                <select class="form-select bg-dark text-light border-secondary" id="printQuality">
                                    <option value="normal">Normal (Faster)</option>
                                    <option value="high" selected>High (Better Quality)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmPrint">
                                <i class="bi bi-eye me-2"></i>Preview & Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if present
        const existingModal = document.getElementById('printDialog');
        if (existingModal) existingModal.remove();

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        const modal = new bootstrap.Modal(document.getElementById('printDialog'));
        modal.show();

        document.getElementById('confirmPrint').addEventListener('click', () => {
            modal.hide();
            this.executePrint();
        });
    }

    async executePrint() {
        const state = window.DICOM_VIEWER.STATE;
        const printRange = document.querySelector('input[name="printRange"]:checked').value;
        const layout = document.getElementById('printLayout').value;
        const includePatientInfo = document.getElementById('printPatientInfo').checked;
        const includeAnnotations = document.getElementById('printAnnotations').checked;
        const includeWindowLevel = document.getElementById('printWindowLevel').checked;
        const quality = document.getElementById('printQuality').value;

        // Show loading indicator
        window.DICOM_VIEWER.showLoadingIndicator('Preparing print preview...', false);

        try {
            let imagesToPrint = [];

            if (printRange === 'current') {
                imagesToPrint = [state.currentImageIndex];
            } else if (printRange === 'all') {
                imagesToPrint = Array.from({ length: state.totalImages }, (_, i) => i);
            } else if (printRange === 'active') {
                imagesToPrint = [state.currentImageIndex];
            }

            await this.generatePrintPreview(imagesToPrint, layout, {
                includePatientInfo,
                includeAnnotations,
                includeWindowLevel,
                quality
            });

        } catch (error) {
            console.error('Print error:', error);
            alert('Failed to generate print preview: ' + error.message);
        } finally {
            window.DICOM_VIEWER.hideLoadingIndicator();
        }
    }

    async generatePrintPreview(imageIndices, layout, options) {
        const [cols, rows] = layout.split('x').map(Number);
        const imagesPerPage = cols * rows;
        const totalPages = Math.ceil(imageIndices.length / imagesPerPage);

        // Create print window
        const printWindow = window.open('', '_blank');

        if (!printWindow) {
            alert('Please allow popups to print');
            return;
        }

        // Build print HTML
        let printHTML = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DICOM Print - ${new Date().toLocaleDateString()}</title>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .page-break {
                page-break-after: always;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .print-page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 15px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }

        .page-header h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }

        .page-header .meta {
            font-size: 11px;
            color: #666;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(${cols}, 1fr);
            grid-template-rows: repeat(${rows}, 1fr);
            gap: 10px;
            flex: 1;
        }

        .image-container {
            position: relative;
            border: 1px solid #ddd;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-overlay {
            position: absolute;
            color: #00ff00;
            font-size: 10px;
            font-family: 'Courier New', monospace;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
            pointer-events: none;
        }

        .overlay-top-left {
            top: 5px;
            left: 5px;
        }

        .overlay-top-right {
            top: 5px;
            right: 5px;
            text-align: right;
        }

        .overlay-bottom-left {
            bottom: 5px;
            left: 5px;
        }

        .overlay-bottom-right {
            bottom: 5px;
            right: 5px;
            text-align: right;
        }

        .page-footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; margin-right: 10px;">
            <i class="bi bi-printer"></i> Print
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #6c757d; color: white; border: none; border-radius: 5px;">
            Close
        </button>
    </div>
`;

        // Generate pages
        for (let page = 0; page < totalPages; page++) {
            const pageImages = imageIndices.slice(page * imagesPerPage, (page + 1) * imagesPerPage);

            printHTML += `
    <div class="print-page${page < totalPages - 1 ? ' page-break' : ''}">
        <div class="page-header">
            <h2>DICOM Medical Images</h2>
            <div class="meta">
                Printed: ${new Date().toLocaleString()} | Page ${page + 1} of ${totalPages}
            </div>
        </div>

        <div class="images-grid">
`;

            for (let i = 0; i < pageImages.length; i++) {
                const imageIndex = pageImages[i];
                const imageData = await this.captureImage(imageIndex, options);

                printHTML += `
            <div class="image-container">
                <img src="${imageData.dataUrl}" alt="DICOM Image ${imageIndex + 1}">
                ${options.includePatientInfo ? this.generateOverlayHTML(imageData.info, options) : ''}
            </div>
`;
            }

            // Fill empty cells
            for (let i = pageImages.length; i < imagesPerPage; i++) {
                printHTML += '<div class="image-container" style="background: #f0f0f0;"></div>';
            }

            printHTML += `
        </div>

        <div class="page-footer">
            Generated by DICOM Viewer Pro | For Medical Use Only | Confidential
        </div>
    </div>
`;
        }

        printHTML += `
</body>
</html>
`;

        printWindow.document.write(printHTML);
        printWindow.document.close();
    }

    async captureImage(imageIndex, options) {
        const state = window.DICOM_VIEWER.STATE;
        const image = state.currentSeriesImages[imageIndex];
        const viewport = state.activeViewport || document.querySelector('[data-viewport-name="original"]');

        // Temporarily load the image if not current
        const wasCurrentIndex = state.currentImageIndex;
        if (imageIndex !== wasCurrentIndex) {
            state.currentImageIndex = imageIndex;
            state.currentFileId = image.id;
            await window.DICOM_VIEWER.loadCurrentImage(true);
        }

        // Wait for image to render
        await new Promise(resolve => setTimeout(resolve, 200));

        // Capture canvas
        const canvas = viewport.querySelector('canvas');
        const dataUrl = canvas ? canvas.toDataURL('image/png', options.quality === 'high' ? 1.0 : 0.8) : '';

        // Get image info
        const enabledElement = cornerstone.getEnabledElement(viewport);
        const cornerstoneImage = enabledElement.image;
        const viewportData = cornerstone.getViewport(viewport);

        const info = {
            patientName: image.patient_name || image.patientName || 'Unknown',
            studyDescription: image.study_description || 'Study',
            seriesDescription: image.series_description || image.seriesDescription || '',
            imageNumber: `${imageIndex + 1}/${state.totalImages}`,
            dimensions: cornerstoneImage ? `${cornerstoneImage.width}×${cornerstoneImage.height}` : '',
            windowCenter: viewportData ? Math.round(viewportData.voi.windowCenter) : '',
            windowWidth: viewportData ? Math.round(viewportData.voi.windowWidth) : '',
            zoom: viewportData ? `${(viewportData.scale * 100).toFixed(0)}%` : ''
        };

        // Restore original image if needed
        if (imageIndex !== wasCurrentIndex) {
            state.currentImageIndex = wasCurrentIndex;
            state.currentFileId = state.currentSeriesImages[wasCurrentIndex].id;
            await window.DICOM_VIEWER.loadCurrentImage(true);
        }

        return { dataUrl, info };
    }

    generateOverlayHTML(info, options) {
        return `
            <div class="image-overlay overlay-top-left">
                ${info.patientName}<br>
                ${info.studyDescription}
            </div>
            <div class="image-overlay overlay-top-right">
                ${info.seriesDescription}<br>
                Image ${info.imageNumber}
            </div>
            <div class="image-overlay overlay-bottom-left">
                ${info.dimensions}
            </div>
            ${options.includeWindowLevel ? `
            <div class="image-overlay overlay-bottom-right">
                W/L: ${info.windowCenter}/${info.windowWidth}<br>
                Zoom: ${info.zoom}
            </div>
            ` : ''}
        `;
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (!window.DICOM_VIEWER.MANAGERS) {
        window.DICOM_VIEWER.MANAGERS = {};
    }
    window.DICOM_VIEWER.MANAGERS.printManager = new window.DICOM_VIEWER.PrintManager();
    console.log('✓ Print Manager initialized');
});
