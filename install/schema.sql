-- Start Smart HR Database Schema
-- Run this SQL to set up your database

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: admins
-- ----------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin accounts via the app or insert them manually.

-- ----------------------------
-- Table: admin_login_attempts
-- ----------------------------
DROP TABLE IF EXISTS `admin_login_attempts`;
CREATE TABLE `admin_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `fail_count` int(11) NOT NULL DEFAULT 0,
  `last_failed` datetime DEFAULT NULL,
  `banned_until` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(20) DEFAULT 'text',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'Start Smart HR', 'text'),
('site_email', 'info@startsmarthr.eu', 'email'),
('site_phone', '+385 99 123 4567', 'text'),
('site_location', 'Zagreb, Hrvatska', 'text'),
('turnstile_site_key', '0x4AAAAAACAsbbl9JPV5qKN3', 'text'),
('turnstile_secret_key', '0x4AAAAAACAsbam0KqzsMjxQ9thDQnn0e8U', 'text'),
('default_language', 'hr', 'text');

-- ----------------------------
-- Table: translations
-- ----------------------------
DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `translation_key` varchar(100) NOT NULL,
  `lang_hr` text DEFAULT NULL,
  `lang_en` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translation_key` (`translation_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: packages
-- ----------------------------
DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `title_hr` varchar(100) NOT NULL,
  `title_en` varchar(100) NOT NULL,
  `description_hr` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `eta_hr` varchar(50) DEFAULT NULL,
  `eta_en` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `badge_hr` varchar(50) DEFAULT NULL,
  `badge_en` varchar(50) DEFAULT NULL,
  `badge_type` varchar(20) DEFAULT 'default',
  `is_featured` tinyint(1) DEFAULT 0,
  `show_discount` tinyint(1) DEFAULT 1,
  `visit_url` varchar(255) DEFAULT NULL,
  `visit_url_2` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default packages
INSERT INTO `packages` (`slug`, `title_hr`, `title_en`, `description_hr`, `description_en`, `eta_hr`, `eta_en`, `price`, `original_price`, `badge_hr`, `badge_en`, `badge_type`, `is_featured`, `visit_url`, `visit_url_2`, `sort_order`) VALUES
('basic', 'Osnovna Stranica', 'Basic Website', 'Pojednostavljeno rješenje za vaš prvi online korak. Idealno za osobne projekte i male tvrtke.', 'Simplified solution for your first online step. Ideal for personal projects and small businesses.', 'ETA: 24-48 sati', 'ETA: 24-48 hours', 300.00, 600.00, 'Osnovni', 'Basic', 'default', 0, 'https://osnovna-stranica-1.netlify.app/', 'https://osnovna-stranica-2.netlify.app/', 1),
('professional', 'Profesionalna Stranica', 'Professional Website', 'Napredno rješenje s modernim CMS-om i interaktivnim elementima, idealno za srednje i velike tvrtke.', 'Advanced solution with modern CMS and interactive elements, ideal for medium and large companies.', 'ETA: 72 sata', 'ETA: 72 hours', 500.00, 1000.00, 'Preporučeno', 'Recommended', 'featured', 1, 'https://profesionalnastranica.pythonanywhere.com/', NULL, 2),
('premium', 'Premium Stranica', 'Premium Website', 'Kompletno rješenje s najnovijim tehnologijama, prilagođeno kompleksnim zahtjevima i integracijama.', 'Complete solution with the latest technologies, tailored to complex requirements and integrations.', 'ETA: 7 dana', 'ETA: 7 days', 1000.00, 2000.00, 'Premium', 'Premium', 'premium', 0, 'https://example.com', NULL, 3),
('custom', 'Custom Projekt', 'Custom Project', 'Potpuno prilagođeno rješenje za velike projekte s jedinstvenim zahtjevima. Kontaktirajte nas za detalje.', 'Fully customized solution for large projects with unique requirements. Contact us for details.', 'ETA: Po dogovoru', 'ETA: By agreement', NULL, NULL, 'Custom', 'Custom', 'custom', 0, NULL, NULL, 4);

-- ----------------------------
-- Table: package_features
-- ----------------------------
DROP TABLE IF EXISTS `package_features`;
CREATE TABLE `package_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `feature_hr` varchar(255) NOT NULL,
  `feature_en` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `package_features_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: package_details
-- ----------------------------
DROP TABLE IF EXISTS `package_details`;
CREATE TABLE `package_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `detail_hr` varchar(255) NOT NULL,
  `detail_en` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `package_details_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: optional_services
-- ----------------------------
DROP TABLE IF EXISTS `optional_services`;
CREATE TABLE `optional_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_hr` varchar(100) NOT NULL,
  `name_en` varchar(100) NOT NULL,
  `description_hr` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `price_text_hr` varchar(50) DEFAULT NULL,
  `price_text_en` varchar(50) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default optional services
INSERT INTO `optional_services` (`name_hr`, `name_en`, `description_hr`, `description_en`, `price`, `price_text_hr`, `price_text_en`, `sort_order`) VALUES
('Održavanje stranice', 'Site maintenance', 'Redovita sigurnosna ažuriranja, zaštita od hakiranja, DDoS zaštita, SSL certifikati, backup sustavi, praćenje performansi, optimizacija brzine, ažuriranje plugina i frameworka, tehnička podrška 24/7', 'Regular security updates, anti-hacking protection, DDoS protection, SSL certificates, backup solutions, performance monitoring, speed optimization, plugin and framework updates, 24/7 technical support', NULL, 'po dogovoru', 'by agreement', 1),
('Osnovne promjene sadržaja', 'Basic content changes', 'Promjene teksta i slika na vašoj stranici', 'Text and image changes on your website', 50.00, NULL, NULL, 2);

-- ----------------------------
-- Table: contact_submissions
-- ----------------------------
DROP TABLE IF EXISTS `contact_submissions`;
CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: pages
-- ----------------------------
DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `title_hr` varchar(100) NOT NULL,
  `title_en` varchar(100) NOT NULL,
  `meta_description_hr` text DEFAULT NULL,
  `meta_description_en` text DEFAULT NULL,
  `content_hr` longtext DEFAULT NULL,
  `content_en` longtext DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: chat_conversations
-- ----------------------------
DROP TABLE IF EXISTS `chat_conversations`;
CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` varchar(64) NOT NULL,
  `visitor_name` varchar(100) DEFAULT NULL,
  `visitor_email` varchar(100) DEFAULT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: chat_messages
-- ----------------------------
DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('visitor','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

