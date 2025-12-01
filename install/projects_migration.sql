-- Projects Management Migration
-- Use this if you already have the projects tables and want to add new fields
-- without losing existing data

-- ----------------------------
-- Update projects table
-- ----------------------------

-- Add new columns if they don't exist
ALTER TABLE `projects` 
  MODIFY COLUMN `current_phase` enum('agreement','planning','design','development','content','testing','final') DEFAULT 'agreement';

-- Add has_agreement column (run only if column doesn't exist)
-- If you get an error that column already exists, skip this line
ALTER TABLE `projects` 
  ADD COLUMN `has_agreement` tinyint(1) DEFAULT 0 AFTER `current_phase`;

-- Add meeting_date column (run only if column doesn't exist)
-- If you get an error that column already exists, skip this line
ALTER TABLE `projects` 
  ADD COLUMN `meeting_date` datetime DEFAULT NULL AFTER `has_agreement`;

-- ----------------------------
-- Update project_phases table
-- ----------------------------
ALTER TABLE `project_phases` 
  MODIFY COLUMN `phase_name` enum('agreement','planning','design','development','content','testing','final') NOT NULL;

-- ----------------------------
-- Update project_checklist table
-- ----------------------------
ALTER TABLE `project_checklist` 
  MODIFY COLUMN `phase_name` enum('agreement','planning','design','development','content','testing','final') NOT NULL;

-- ----------------------------
-- Create project_meetings table if it doesn't exist
-- ----------------------------
CREATE TABLE IF NOT EXISTS `project_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `meeting_date` datetime NOT NULL,
  `meeting_type` enum('agreement','consultation','review','other') DEFAULT 'agreement',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_meetings_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

