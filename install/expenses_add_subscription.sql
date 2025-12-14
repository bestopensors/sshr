-- Add subscription fields to expenses table
-- Run this if the expenses table already exists

ALTER TABLE `expenses` 
  ADD COLUMN `payment_type` enum('one_time','monthly','weekly','yearly') DEFAULT 'one_time' AFTER `paid_by`,
  ADD COLUMN `next_payment_date` date DEFAULT NULL AFTER `payment_type`;

