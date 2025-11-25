-- ============================================================================
-- Hospital DICOM Viewer Pro v2.0 - Database Schema
-- Production Database: dicom_viewer_v2_production
--
-- IMPORTANT: This schema eliminates patient/study caching tables
-- All patient and study data is queried directly from Orthanc via DICOMweb
-- ============================================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS `dicom_viewer_v2_production`
DEFAULT CHARACTER SET utf8mb4
DEFAULT COLLATE utf8mb4_unicode_ci;

USE `dicom_viewer_v2_production`;

-- ============================================================================
-- USER MANAGEMENT & AUTHENTICATION
-- ============================================================================

-- Users Table
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `role` ENUM('admin', 'radiologist', 'technician', 'viewer') DEFAULT 'viewer',
    `is_active` BOOLEAN DEFAULT TRUE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table (for session-based authentication)
CREATE TABLE `sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_id` VARCHAR(128) UNIQUE NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_session_id` (`session_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MEDICAL REPORTS (STORED IN DATABASE, NOT FILES)
-- ============================================================================

-- Medical Reports Table
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
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_study_uid` (`study_uid`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_reporting_physician` (`reporting_physician_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report Version History
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
    FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_report_id` (`report_id`),
    INDEX `idx_version_number` (`version_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- MEASUREMENTS & ANNOTATIONS
-- ============================================================================

-- Measurements Table
CREATE TABLE `measurements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_uid` VARCHAR(255) NOT NULL,
    `series_uid` VARCHAR(255) NOT NULL,
    `instance_uid` VARCHAR(255) NOT NULL,
    `tool_type` ENUM('length', 'angle', 'rectangle_roi', 'elliptical_roi', 'freehand_roi', 'probe') NOT NULL,
    `measurement_data` JSON NOT NULL,
    `value` VARCHAR(100),
    `unit` VARCHAR(20),
    `label` VARCHAR(255),
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_study_uid` (`study_uid`),
    INDEX `idx_series_uid` (`series_uid`),
    INDEX `idx_instance_uid` (`instance_uid`),
    INDEX `idx_tool_type` (`tool_type`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CLINICAL NOTES
-- ============================================================================

-- Clinical Notes Table
CREATE TABLE `clinical_notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_uid` VARCHAR(255) NOT NULL,
    `series_uid` VARCHAR(255) NULL,
    `instance_uid` VARCHAR(255) NULL,
    `note_type` ENUM('clinical_history', 'series_note', 'image_note', 'general') DEFAULT 'general',
    `content` TEXT NOT NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_study_uid` (`study_uid`),
    INDEX `idx_series_uid` (`series_uid`),
    INDEX `idx_note_type` (`note_type`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PRESCRIPTIONS
-- ============================================================================

-- Prescriptions Table
CREATE TABLE `prescriptions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `study_uid` VARCHAR(255) NOT NULL,
    `patient_id` VARCHAR(64) NOT NULL,
    `patient_name` VARCHAR(255) NOT NULL,
    `medication_name` VARCHAR(255) NOT NULL,
    `dosage` VARCHAR(100) NOT NULL,
    `frequency` VARCHAR(100) NOT NULL,
    `duration` VARCHAR(100) NOT NULL,
    `instructions` TEXT,
    `prescribed_by` INT UNSIGNED NOT NULL,
    `prescribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (`prescribed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_study_uid` (`study_uid`),
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_prescribed_by` (`prescribed_by`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- USER PREFERENCES
-- ============================================================================

-- User Preferences Table
CREATE TABLE `user_preferences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `preference_key` VARCHAR(100) NOT NULL,
    `preference_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_preference` (`user_id`, `preference_key`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AUDIT LOGS (HIPAA COMPLIANCE)
-- ============================================================================

-- Audit Logs Table
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(50),
    `action` VARCHAR(100) NOT NULL,
    `resource_type` VARCHAR(50),
    `resource_id` VARCHAR(255),
    `details` TEXT,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_resource_type` (`resource_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- AUTOMATED SYNC CONFIGURATION
-- ============================================================================

-- Sync Configuration Table
CREATE TABLE `sync_configuration` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `orthanc_storage_path` VARCHAR(500) NOT NULL,
    `hospital_data_path` VARCHAR(500),
    `ftp_host` VARCHAR(255),
    `ftp_username` VARCHAR(100),
    `ftp_password` VARCHAR(255),
    `ftp_port` INT DEFAULT 21,
    `ftp_path` VARCHAR(500) DEFAULT '/public_html/dicom_viewer/',
    `ftp_passive` BOOLEAN DEFAULT TRUE,
    `sync_enabled` BOOLEAN DEFAULT FALSE,
    `sync_interval` INT DEFAULT 120,
    `monitoring_enabled` BOOLEAN DEFAULT FALSE,
    `monitoring_interval` INT DEFAULT 30,
    `last_sync_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default sync configuration
INSERT INTO `sync_configuration` (
    `orthanc_storage_path`,
    `sync_enabled`,
    `monitoring_enabled`
) VALUES (
    'C:\\Orthanc\\OrthancStorage',
    FALSE,
    FALSE
);

-- Sync History Table
CREATE TABLE `sync_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sync_type` ENUM('manual', 'scheduled', 'monitoring') DEFAULT 'manual',
    `destination` ENUM('localhost', 'godaddy', 'both') DEFAULT 'both',
    `files_synced` INT DEFAULT 0,
    `total_size_bytes` BIGINT DEFAULT 0,
    `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
    `error_message` TEXT,
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_sync_type` (`sync_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- HOSPITAL DATA IMPORT
-- ============================================================================

-- Import Jobs Table
CREATE TABLE `import_jobs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_type` ENUM('initial', 'incremental', 'manual') DEFAULT 'manual',
    `source_path` VARCHAR(500) NOT NULL,
    `total_files` INT DEFAULT 0,
    `files_processed` INT DEFAULT 0,
    `files_imported` INT DEFAULT 0,
    `files_failed` INT DEFAULT 0,
    `total_size_bytes` BIGINT DEFAULT 0,
    `status` ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `error_message` TEXT,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import History Table (tracks each imported file)
CREATE TABLE `import_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `job_id` INT UNSIGNED NULL,
    `file_path` VARCHAR(1000) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size_bytes` BIGINT NOT NULL,
    `file_hash` VARCHAR(64),
    `orthanc_instance_id` VARCHAR(255),
    `patient_id` VARCHAR(64),
    `study_uid` VARCHAR(255),
    `series_uid` VARCHAR(255),
    `instance_uid` VARCHAR(255),
    `status` ENUM('imported', 'failed', 'duplicate', 'skipped') DEFAULT 'imported',
    `error_message` TEXT,
    `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`job_id`) REFERENCES `import_jobs`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_file_hash` (`file_hash`),
    INDEX `idx_job_id` (`job_id`),
    INDEX `idx_file_path` (`file_path`(255)),
    INDEX `idx_status` (`status`),
    INDEX `idx_imported_at` (`imported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- GOOGLE DRIVE BACKUP CONFIGURATION
-- ============================================================================

-- Google Drive Backup Configuration Table
CREATE TABLE `gdrive_backup_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` VARCHAR(255),
    `client_secret` VARCHAR(255),
    `refresh_token` TEXT,
    `folder_name` VARCHAR(255) DEFAULT 'DICOM_Viewer_Backups',
    `folder_id` VARCHAR(255),
    `backup_enabled` BOOLEAN DEFAULT FALSE,
    `backup_schedule` ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    `backup_time` TIME DEFAULT '02:00:00',
    `backup_database` BOOLEAN DEFAULT TRUE,
    `backup_php_files` BOOLEAN DEFAULT TRUE,
    `backup_js_files` BOOLEAN DEFAULT TRUE,
    `backup_config_files` BOOLEAN DEFAULT TRUE,
    `retention_days` INT DEFAULT 30,
    `last_backup_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default backup configuration
INSERT INTO `gdrive_backup_config` (
    `folder_name`,
    `backup_enabled`,
    `retention_days`
) VALUES (
    'DICOM_Viewer_Backups',
    FALSE,
    30
);

-- Backup History Table
CREATE TABLE `backup_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `backup_type` ENUM('manual', 'scheduled') DEFAULT 'manual',
    `backup_filename` VARCHAR(255) NOT NULL,
    `gdrive_file_id` VARCHAR(255),
    `backup_size_bytes` BIGINT DEFAULT 0,
    `backup_date` DATETIME,
    `includes_database` BOOLEAN DEFAULT TRUE,
    `includes_php` BOOLEAN DEFAULT TRUE,
    `includes_js` BOOLEAN DEFAULT TRUE,
    `includes_config` BOOLEAN DEFAULT TRUE,
    `status` ENUM('success', 'failed', 'partial') DEFAULT 'success',
    `error_message` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_backup_type` (`backup_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERT DEFAULT DATA
-- ============================================================================

-- Default Admin User (username: admin, password: Admin@123)
INSERT INTO `users` (
    `username`,
    `password_hash`,
    `full_name`,
    `email`,
    `role`,
    `is_active`
) VALUES (
    'admin',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYIq7c5kqGK',
    'System Administrator',
    'admin@hospital.com',
    'admin',
    TRUE
);

-- Default Radiologist User (username: radiologist, password: Radio@123)
INSERT INTO `users` (
    `username`,
    `password_hash`,
    `full_name`,
    `email`,
    `role`,
    `is_active`
) VALUES (
    'radiologist',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Dr. John Smith',
    'radiologist@hospital.com',
    'radiologist',
    TRUE
);

-- Default Technician User (username: technician, password: Tech@123)
INSERT INTO `users` (
    `username`,
    `password_hash`,
    `full_name`,
    `email`,
    `role`,
    `is_active`
) VALUES (
    'technician',
    '$2y$12$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
    'Sarah Johnson',
    'technician@hospital.com',
    'technician',
    TRUE
);

-- ============================================================================
-- VIEWS FOR REPORTING
-- ============================================================================

-- View for Active Reports with User Information
CREATE VIEW `vw_active_reports` AS
SELECT
    r.id,
    r.study_uid,
    r.patient_id,
    r.patient_name,
    r.template_name,
    r.title,
    r.status,
    u1.full_name AS created_by_name,
    u2.full_name AS reporting_physician_name,
    r.created_at,
    r.updated_at,
    r.finalized_at
FROM `medical_reports` r
LEFT JOIN `users` u1 ON r.created_by = u1.id
LEFT JOIN `users` u2 ON r.reporting_physician_id = u2.id
WHERE r.status IN ('draft', 'final');

-- View for Audit Trail
CREATE VIEW `vw_audit_trail` AS
SELECT
    a.id,
    a.username,
    u.full_name,
    a.action,
    a.resource_type,
    a.resource_id,
    a.ip_address,
    a.created_at
FROM `audit_logs` a
LEFT JOIN `users` u ON a.user_id = u.id
ORDER BY a.created_at DESC;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

DELIMITER //

-- Clean up old sessions
CREATE PROCEDURE `sp_cleanup_expired_sessions`()
BEGIN
    DELETE FROM `sessions` WHERE `expires_at` < NOW();
END //

-- Clean up old audit logs (keep last 90 days)
CREATE PROCEDURE `sp_cleanup_old_audit_logs`()
BEGIN
    DELETE FROM `audit_logs`
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //

-- Clean up old backups based on retention policy
CREATE PROCEDURE `sp_cleanup_old_backups`()
BEGIN
    DECLARE retention INT;

    SELECT `retention_days` INTO retention
    FROM `gdrive_backup_config`
    LIMIT 1;

    DELETE FROM `backup_history`
    WHERE `created_at` < DATE_SUB(NOW(), INTERVAL retention DAY);
END //

DELIMITER ;

-- ============================================================================
-- EVENTS (Automated Cleanup Tasks)
-- ============================================================================

SET GLOBAL event_scheduler = ON;

-- Clean expired sessions every hour
CREATE EVENT IF NOT EXISTS `evt_cleanup_sessions`
ON SCHEDULE EVERY 1 HOUR
DO CALL sp_cleanup_expired_sessions();

-- Clean old audit logs daily at 3 AM
CREATE EVENT IF NOT EXISTS `evt_cleanup_audit_logs`
ON SCHEDULE EVERY 1 DAY STARTS '2025-01-01 03:00:00'
DO CALL sp_cleanup_old_audit_logs();

-- Clean old backups daily at 4 AM
CREATE EVENT IF NOT EXISTS `evt_cleanup_backups`
ON SCHEDULE EVERY 1 DAY STARTS '2025-01-01 04:00:00'
DO CALL sp_cleanup_old_backups();

-- ============================================================================
-- DATABASE INITIALIZATION COMPLETE
-- ============================================================================

-- Display completion message
SELECT 'Database schema created successfully!' AS message;
SELECT 'Default users created:' AS info;
SELECT username, role, 'Check documentation for passwords' AS password_info FROM users;
