-- Expenses/Troskovnik Table
-- Add this table to your existing database

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `expenses`;

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(500) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `paid_by` varchar(255) DEFAULT NULL,
  `payment_type` enum('one_time','monthly','weekly','yearly') DEFAULT 'one_time',
  `next_payment_date` date DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `expense_date` (`expense_date`),
  KEY `category` (`category`),
  KEY `company_name` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

