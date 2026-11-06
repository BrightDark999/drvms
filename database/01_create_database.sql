-- ---------------------------------------------------------------------------
-- DRMS: Step 1 — Create database only (phpMyAdmin or MySQL CLI)
-- ---------------------------------------------------------------------------
-- After this, either:
--   A) Run migrations (recommended):  php spark migrate --all
--   B) Import full schema:           database/drms_db_schema.sql
-- ---------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `drms_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `drms_db`;
