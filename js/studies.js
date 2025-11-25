const state = {
    openReports: [],
    currentReportIndex: 0,
    patient: null,
    studies: [],
    currentStudyUID: null,
    remarkModal: null
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize remark modal
    const remarkModalElement = document.getElementById('remarkModal');
    if (remarkModalElement) {
        state.remarkModal = new bootstrap.Modal(remarkModalElement);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient_id');
    if (!patientId) {
        alert('No patient selected');
        window.history.back();
        return;
    }
    loadStudies(patientId);
});

async function loadStudies(patientId) {
    try {
        const response = await fetch('../api/study_list_api.php?patient_id=' + encodeURIComponent(patientId));
        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'Failed to load studies');
        state.patient = data.patient;
        state.studies = data.studies;
        displayPatientInfo(data.patient);
        displayStudies(data.studies);
    } catch (error) {
        console.error('Error loading studies:', error);
        document.getElementById('studiesList').innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error: ' + error.message + '</div>';
    }
}

function displayPatientInfo(patient) {
    document.getElementById('patientName').textContent = patient.patient_name || 'Unknown Patient';
    document.getElementById('patientInfo').textContent = 'ID: ' + (patient.patient_id || 'N/A') + ' | DOB: ' + (patient.patient_birth_date || 'N/A') + ' | Sex: ' + (patient.patient_sex || 'N/A');
}

function displayStudies(studies) {
    const container = document.getElementById('studiesList');
    if (studies.length === 0) {
        container.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> No studies found for this patient.</div>';
        return;
    }

    // Log studies for debugging
    console.log('=== STUDIES LOADED ===');
    console.log('Total studies:', studies.length);
    studies.forEach((study, index) => {
        console.log(`Study ${index + 1}:`, {
            study_instance_uid: study.study_instance_uid,
            orthanc_id: study.orthanc_id,
            study_description: study.study_description
        });
    });

    // Create table layout
    container.innerHTML = `
        <div class="table-responsive">
            <table class="studies-table">
                <thead>
                    <tr>
                        <th>Study Description</th>
                        <th>Study Date</th>
                        <th>Modality</th>
                        <th>Images</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${studies.map(study => createStudyTableRow(study)).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function createStudyTableRow(study) {
    const hasReport = !!study.orthanc_id;
    const studyDesc = escapeHtml(study.study_description || study.study_name || 'Unnamed Study');
    const studyDate = formatDate(study.study_date);
    const studyTime = formatTime(study.study_time);
    const modality = study.modality || 'N/A';
    const imageCount = study.instance_count || '0';
    const studyUID = study.study_instance_uid;
    const orthancId = study.orthanc_id;

    return `
        <tr id="study-${studyUID}">
            <td>
                <strong>${studyDesc}</strong>
                <br><small style="color: var(--text-secondary);">ID: ${study.study_id || 'N/A'}</small>
            </td>
            <td>
                ${studyDate}
                <br><small style="color: var(--text-secondary);">${studyTime}</small>
            </td>
            <td>
                <span class="badge badge-info">${modality}</span>
            </td>
            <td>${imageCount} images</td>
            <td style="text-align: center;">
                <div class="btn-group">
                    <button class="btn-sm btn-primary" onclick="openStudy('${studyUID}', '${orthancId}')" title="Open Study">
                        <i class="bi bi-box-arrow-up-right"></i> View
                    </button>
                    <button class="btn-sm btn-success" onclick="exportToJPG('${studyUID}', '${studyDesc}')" title="Export all images as JPG">
                        <i class="bi bi-download"></i> Export JPG
                    </button>
                    <button class="btn-sm btn-warning" onclick="showRemarkModal('${studyUID}', '${studyDesc}')" title="Add/View Remarks">
                        <i class="bi bi-chat-square-text"></i> Remark
                    </button>
                    <button class="btn-sm" onclick="viewReport('${studyUID}', '${orthancId}')" ${!hasReport ? 'disabled' : ''} title="View Report">
                        <i class="bi bi-file-earmark-text"></i> Report
                    </button>
                </div>
            </td>
        </tr>
    `;
}

// Export study images as JPG
async function exportToJPG(studyUID, studyDescription) {
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';

    try {
        const response = await fetch(`../api/studies/export-images.php?study_uid=${encodeURIComponent(studyUID)}`);

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to export images');
        }

        // Create a blob from the response
        const blob = await response.blob();

        // Create a download link
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;

        // Get filename from Content-Disposition header or use default
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = `Study_${studyDescription.replace(/[^a-zA-Z0-9]/g, '_')}_images.zip`;
        if (contentDisposition) {
            const matches = /filename="(.+)"/.exec(contentDisposition);
            if (matches) filename = matches[1];
        }

        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        alert('Images exported successfully!');
    } catch (error) {
        console.error('Export error:', error);
        alert('Error exporting images: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// Show remark modal
async function showRemarkModal(studyUID, studyDescription) {
    state.currentStudyUID = studyUID;
    document.getElementById('remarkStudyName').textContent = studyDescription;
    document.getElementById('newRemarkText').value = '';

    if (state.remarkModal) {
        state.remarkModal.show();
        await loadRemarks();
    }
}

// Load remarks for a study
async function loadRemarks() {
    const remarksList = document.getElementById('remarksList');
    remarksList.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading remarks...</div>';

    try {
        const response = await fetch(`../api/studies/remarks.php?study_uid=${encodeURIComponent(state.currentStudyUID)}`);
        const data = await response.json();

        if (data.success) {
            if (data.remarks.length === 0) {
                remarksList.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-inbox"></i> No remarks yet</div>';
            } else {
                remarksList.innerHTML = data.remarks.map(remark => `
                    <div class="card mb-2" style="background: var(--bg-tertiary); border: 1px solid #444;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-2" style="color: var(--text-primary);">${escapeHtml(remark.remark)}</p>
                                    <small style="color: var(--text-secondary);">
                                        <i class="bi bi-person"></i> ${escapeHtml(remark.created_by_name || 'Unknown')}
                                        (${escapeHtml(remark.created_by_role || 'N/A')})
                                        <i class="bi bi-clock ms-2"></i> ${new Date(remark.created_at).toLocaleString()}
                                        ${remark.created_at !== remark.updated_at ? '<i class="bi bi-pencil ms-2"></i> Updated: ' + new Date(remark.updated_at).toLocaleString() : ''}
                                    </small>
                                </div>
                                <button class="btn btn-sm btn-danger ms-2" onclick="deleteRemark(${remark.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading remarks:', error);
        remarksList.innerHTML = '<div class="alert alert-danger">Failed to load remarks</div>';
    }
}

// Save new remark
async function saveRemark() {
    const remarkText = document.getElementById('newRemarkText').value.trim();

    if (!remarkText) {
        alert('Please enter a remark');
        return;
    }

    try {
        const response = await fetch('../api/studies/remarks.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                study_uid: state.currentStudyUID,
                remark: remarkText
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('newRemarkText').value = '';
            await loadRemarks();
            alert('Remark added successfully!');
        } else {
            throw new Error(data.error || 'Failed to save remark');
        }
    } catch (error) {
        console.error('Error saving remark:', error);
        alert('Error saving remark: ' + error.message);
    }
}

// Delete remark
async function deleteRemark(remarkId) {
    if (!confirm('Are you sure you want to delete this remark?')) {
        return;
    }

    try {
        const response = await fetch(`../api/studies/remarks.php?id=${remarkId}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (data.success) {
            await loadRemarks();
            alert('Remark deleted successfully!');
        } else {
            throw new Error(data.error || 'Failed to delete remark');
        }
    } catch (error) {
        console.error('Error deleting remark:', error);
        alert('Error deleting remark: ' + error.message);
    }
}

// Helper Functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    // Format DICOM date YYYYMMDD to YYYY-MM-DD
    if (dateStr.length === 8) {
        return `${dateStr.substr(0, 4)}-${dateStr.substr(4, 2)}-${dateStr.substr(6, 2)}`;
    }
    return dateStr;
}

function formatTime(timeStr) {
    if (!timeStr) return 'N/A';
    // Format DICOM time HHMMSS to HH:MM:SS
    if (timeStr.length >= 6) {
        return `${timeStr.substr(0, 2)}:${timeStr.substr(2, 2)}:${timeStr.substr(4, 2)}`;
    }
    return timeStr;
}

// Open study in viewer
function openStudy(studyUID, orthancId) {
    window.open(`../index.php?study_id=${encodeURIComponent(orthancId)}&studyUID=${encodeURIComponent(studyUID)}`, '_blank');
}

// View report function (existing functionality)
async function viewReport(studyUID, orthancId) {
    try {
        const response = await fetch(`../api/reports/by-study.php?studyUID=${encodeURIComponent(studyUID)}`);
        const data = await response.json();

        if (data.success && data.data && data.data.reports && data.data.reports.length > 0) {
            const report = data.data.reports[0];
            openReportTab(report, studyUID);
        } else {
            alert('No report found for this study.');
        }
    } catch (error) {
        console.error('Error loading report:', error);
        alert('Error loading report: ' + error.message);
    }
}

// Prescription function (existing functionality)
function addPrescription(studyUID, orthancId) {
    alert('Prescription feature - Coming soon!');
}

// Doctor info function (existing functionality)
function viewDoctorInfo(studyUID) {
    alert('Doctor info feature - Coming soon!');
}

// Report panel functions
function openReportTab(report, studyUID) {
    const existingIndex = state.openReports.findIndex(r => r.id === report.id);

    if (existingIndex !== -1) {
        state.currentReportIndex = existingIndex;
        switchToReport(existingIndex);
        return;
    }

    state.openReports.push(report);
    state.currentReportIndex = state.openReports.length - 1;

    const reportsPanel = document.getElementById('reportsPanel');
    const studiesPanel = document.getElementById('studiesPanel');

    if (!reportsPanel.classList.contains('active')) {
        reportsPanel.classList.add('active');
        studiesPanel.style.flex = '1';
    }

    updateReportTabs();
    displayReport(report);
    updateOpenReportsCount();

    const studyCard = document.getElementById('study-' + studyUID);
    if (studyCard) {
        studyCard.classList.add('report-opened');
    }
}

function updateReportTabs() {
    const tabsContainer = document.getElementById('reportTabs');
    tabsContainer.innerHTML = state.openReports.map((report, index) => {
        const title = report.title || 'Report ' + (index + 1);
        const isActive = index === state.currentReportIndex;
        return `
            <div class="report-tab ${isActive ? 'active' : ''}" onclick="switchToReport(${index})">
                <span>${escapeHtml(title)}</span>
                <span class="close-tab" onclick="closeReport(${index}, event)">&times;</span>
            </div>
        `;
    }).join('');
}

function switchToReport(index) {
    if (index < 0 || index >= state.openReports.length) return;
    state.currentReportIndex = index;
    updateReportTabs();
    displayReport(state.openReports[index]);
}

function displayReport(report) {
    const contentDiv = document.getElementById('reportContent');
    contentDiv.innerHTML = `
        <div class="report-card">
            <div class="report-header">
                <h4>${escapeHtml(report.title || 'Medical Report')}</h4>
                <div class="report-actions">
                    <button class="btn-sm btn-primary" onclick="printReport()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>

            <div class="report-section">
                <h6><i class="bi bi-card-text"></i> Indication</h6>
                <p>${escapeHtml(report.indication || 'Not specified')}</p>
            </div>

            <div class="report-section">
                <h6><i class="bi bi-shield-check"></i> Technique</h6>
                <p>${escapeHtml(report.technique || 'Not specified')}</p>
            </div>

            <div class="report-section">
                <h6><i class="bi bi-search"></i> Findings</h6>
                <p style="white-space: pre-wrap;">${escapeHtml(report.findings || 'No findings reported')}</p>
            </div>

            <div class="report-section">
                <h6><i class="bi bi-clipboard-check"></i> Impression</h6>
                <p style="white-space: pre-wrap;">${escapeHtml(report.impression || 'No impression provided')}</p>
            </div>

            <div class="report-section">
                <h6><i class="bi bi-info-circle"></i> Status</h6>
                <p><span class="badge ${report.status === 'final' ? 'badge-success' : 'badge-warning'}">${report.status || 'draft'}</span></p>
            </div>
        </div>
    `;
}

function closeReport(index, event) {
    if (event) event.stopPropagation();

    state.openReports.splice(index, 1);

    if (state.openReports.length === 0) {
        closeAllReports();
        return;
    }

    if (state.currentReportIndex >= state.openReports.length) {
        state.currentReportIndex = state.openReports.length - 1;
    }

    updateReportTabs();
    displayReport(state.openReports[state.currentReportIndex]);
    updateOpenReportsCount();
}

function closeAllReports() {
    state.openReports = [];
    state.currentReportIndex = 0;

    const reportsPanel = document.getElementById('reportsPanel');
    const studiesPanel = document.getElementById('studiesPanel');

    reportsPanel.classList.remove('active', 'full');
    studiesPanel.classList.remove('hidden');
    studiesPanel.style.flex = '1';

    document.getElementById('reportTabs').innerHTML = '';
    document.getElementById('reportContent').innerHTML = `
        <div class="no-report-message">
            <i class="bi bi-file-earmark-text"></i>
            <h5>No Report Selected</h5>
            <p>Click "View Report" button on any study</p>
        </div>
    `;

    updateOpenReportsCount();

    document.querySelectorAll('.study-card').forEach(card => {
        card.classList.remove('report-opened');
    });
}

function toggleFullScreen() {
    const reportsPanel = document.getElementById('reportsPanel');
    const studiesPanel = document.getElementById('studiesPanel');
    const toggleIcon = document.getElementById('toggleIcon');

    if (reportsPanel.classList.contains('full')) {
        reportsPanel.classList.remove('full');
        reportsPanel.classList.add('active');
        studiesPanel.classList.remove('hidden');
        studiesPanel.style.flex = '1';
        toggleIcon.className = 'bi bi-arrows-angle-expand';
    } else {
        reportsPanel.classList.add('full');
        reportsPanel.classList.remove('active');
        studiesPanel.classList.add('hidden');
        studiesPanel.style.flex = '0';
        toggleIcon.className = 'bi bi-arrows-angle-contract';
    }
}

function updateOpenReportsCount() {
    const countElement = document.getElementById('openReportsCount');
    const countText = document.getElementById('reportCountText');

    if (state.openReports.length > 0) {
        countElement.style.display = 'inline-flex';
        countText.textContent = state.openReports.length;
    } else {
        countElement.style.display = 'none';
    }
}

function printReport() {
    window.print();
}
