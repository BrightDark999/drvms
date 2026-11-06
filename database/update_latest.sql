-- =============================================================================
-- DRMS Database Update Script (for InfinityFree phpMyAdmin)
-- Run this in phpMyAdmin -> SQL tab to update your existing database with new tables.
-- =============================================================================

-- 1. Create Volunteer Groups Table
CREATE TABLE IF NOT EXISTS `volunteer_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `head_name` varchar(180) NOT NULL,
  `head_contact` varchar(64) NOT NULL,
  `head_email` varchar(180) DEFAULT NULL,
  `barangay_id` int(11) unsigned DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `approval_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Create Volunteer Group Members Table (min 7 per group)
CREATE TABLE IF NOT EXISTS `volunteer_group_members` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) unsigned NOT NULL,
  `full_name` varchar(180) NOT NULL,
  `contact_number` varchar(64) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `role_or_skills` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `change_type` varchar(32) NOT NULL DEFAULT 'none',
  `pending_data` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_group_members_group_id` FOREIGN KEY (`group_id`) REFERENCES `volunteer_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Add volunteer_type, preferred_hours, and group_id to volunteers table (if not existing)
SET @col_vol_type = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteers' AND COLUMN_NAME = 'volunteer_type');
SET @sql_vol_type = IF(@col_vol_type = 0, 'ALTER TABLE `volunteers` ADD COLUMN `volunteer_type` varchar(32) NOT NULL DEFAULT \'individual\' AFTER `availability_status`', 'SELECT 1');
PREPARE stmt1 FROM @sql_vol_type; EXECUTE stmt1; DEALLOCATE PREPARE stmt1;

SET @col_pref_hrs = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteers' AND COLUMN_NAME = 'preferred_hours');
SET @sql_pref_hrs = IF(@col_pref_hrs = 0, 'ALTER TABLE `volunteers` ADD COLUMN `preferred_hours` tinyint(3) unsigned NOT NULL DEFAULT 8 AFTER `volunteer_type`', 'SELECT 1');
PREPARE stmt2 FROM @sql_pref_hrs; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

SET @col_grp_id = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteers' AND COLUMN_NAME = 'group_id');
SET @sql_grp_id = IF(@col_grp_id = 0, 'ALTER TABLE `volunteers` ADD COLUMN `group_id` int(11) unsigned DEFAULT NULL AFTER `preferred_hours`', 'SELECT 1');
PREPARE stmt3 FROM @sql_grp_id; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

-- Verification query: check tables
SHOW TABLES LIKE 'volunteer%';
