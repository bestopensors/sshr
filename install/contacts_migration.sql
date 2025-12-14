-- Migration: Add notes_type column to contacts table
-- This script safely adds the column only if it doesn't exist

SET FOREIGN_KEY_CHECKS = 0;

-- Check if column exists and add it if it doesn't
SET @dbname = DATABASE();
SET @tablename = 'contacts';
SET @columnname = 'notes_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1', -- Column exists, do nothing
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' enum(\'regular\',\'bullets\') DEFAULT \'regular\' AFTER notes')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET FOREIGN_KEY_CHECKS = 1;

