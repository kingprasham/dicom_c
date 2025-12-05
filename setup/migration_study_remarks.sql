-- Migration: Add study_remarks table
-- Date: 2025-11-25
-- Description: Table to store remarks/comments for DICOM studies

CREATE TABLE IF NOT EXISTS `study_remarks` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `study_instance_uid` VARCHAR(255) NOT NULL,
  `remark` TEXT NOT NULL,
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_study_uid` (`study_instance_uid`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_remarks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for faster lookups
CREATE INDEX idx_study_created ON study_remarks(study_instance_uid, created_at DESC);