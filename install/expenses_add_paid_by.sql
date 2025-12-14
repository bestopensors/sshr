-- Add paid_by column to expenses table
-- Run this if the expenses table already exists

ALTER TABLE `expenses` ADD COLUMN `paid_by` varchar(255) DEFAULT NULL AFTER `company_name`;

