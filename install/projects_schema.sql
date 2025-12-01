-- Projects Management Tables
-- Add these tables to your existing database

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: projects
-- ----------------------------
-- Drop child tables first (if they exist)
DROP TABLE IF EXISTS `project_meetings`;
DROP TABLE IF EXISTS `project_checklist`;
DROP TABLE IF EXISTS `project_phases`;
DROP TABLE IF EXISTS `projects`;

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `package_type` enum('basic','professional','premium','custom') DEFAULT 'basic',
  `agreement_date` date NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('future','current','past') DEFAULT 'current',
  `current_phase` enum('agreement','planning','design','development','content','testing','final') DEFAULT 'agreement',
  `has_agreement` tinyint(1) DEFAULT 0,
  `meeting_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `current_phase` (`current_phase`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: project_phases
-- ----------------------------
DROP TABLE IF EXISTS `project_phases`;
CREATE TABLE `project_phases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_name` enum('agreement','planning','design','development','content','testing','final') NOT NULL,
  `duration_days` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_phases_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: project_checklist
-- ----------------------------
DROP TABLE IF EXISTS `project_checklist`;
CREATE TABLE `project_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `phase_name` enum('agreement','planning','design','development','content','testing','final') NOT NULL,
  `task` varchar(255) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `phase_name` (`phase_name`),
  CONSTRAINT `project_checklist_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: project_meetings
-- ----------------------------
DROP TABLE IF EXISTS `project_meetings`;
CREATE TABLE `project_meetings` (
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

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

