-- ============================================
-- SQL Debugging Script for Foreign Key Issues
-- Run these queries in phpMyAdmin to diagnose
-- ============================================

-- ========== 1. Check Database Info ==========
SELECT DATABASE() AS current_database;
SHOW VARIABLES LIKE 'sql_mode';
SHOW VARIABLES LIKE 'default_storage_engine';
SHOW VARIABLES LIKE 'character_set%';

-- ========== 2. List All Tables ==========
SHOW TABLES;

-- ========== 3. Check User Table (CRITICAL) ==========
-- Change 'pengguna' to your actual user table name
DESCRIBE pengguna;
SHOW CREATE TABLE pengguna\G

-- Get charset and engine of user table
SELECT 
  TABLE_NAME,
  TABLE_COLLATION,
  ENGINE,
  TABLE_CHARSET
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'pengguna';

-- ========== 4. Check Produk Table ==========
DESCRIBE produk;
SHOW CREATE TABLE produk\G

SELECT 
  TABLE_NAME,
  TABLE_COLLATION,
  ENGINE
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'produk';

-- ========== 5. Check Column Types (MUST MATCH!) ==========
-- User table id_user
SELECT 
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'pengguna' AND COLUMN_NAME = 'id_user';

-- ========== 6. Check Produk id_produk ==========
SELECT 
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'produk' AND COLUMN_NAME = 'id_produk';

-- ========== 7. Check Existing Foreign Keys ==========
SELECT 
  CONSTRAINT_NAME,
  TABLE_NAME,
  COLUMN_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_NAME IS NOT NULL
AND TABLE_SCHEMA = DATABASE();

-- ========== 8. Check InnoDB Status ==========
SHOW ENGINE INNODB STATUS;

-- ========== 9. Check if tables can be dropped ==========
-- Check for locks
SHOW OPEN TABLES WHERE In_use > 0;

-- ========== 10. Safety Check: Count Users ==========
SELECT COUNT(*) as total_users FROM pengguna;
SELECT COUNT(*) as total_produk FROM produk;

-- ========== 11. List All Constraints ==========
SELECT 
  CONSTRAINT_NAME,
  TABLE_NAME,
  CONSTRAINT_TYPE
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- ========== 12. Check Table Creation Errors ==========
SHOW WARNINGS;
SHOW ERRORS;

-- ========== 13. If dropping needed, do it safely ==========
-- ⚠️ UNCOMMENT ONLY IF YOU WANT TO DELETE TABLES
-- DROP TABLE IF EXISTS `detail_pesanan`;
-- DROP TABLE IF EXISTS `pesanan`;
-- DROP TABLE IF EXISTS `pengiriman`;

-- ========== 14. Verify tables don't exist ==========
SHOW TABLES LIKE 'pengiriman';
SHOW TABLES LIKE 'pesanan';
SHOW TABLES LIKE 'detail_pesanan';

-- ========== SUMMARY QUERY ==========
-- Run this to get complete overview
SELECT
  'pengguna' as table_name,
  COUNT(*) as row_count,
  (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_NAME='pengguna' AND COLUMN_NAME='id_user') as id_user_type,
  (SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES 
   WHERE TABLE_NAME='pengguna') as engine
UNION ALL
SELECT
  'produk',
  COUNT(*),
  (SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_NAME='produk' AND COLUMN_NAME='id_produk'),
  (SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES 
   WHERE TABLE_NAME='produk')
FROM produk;

-- ========== AFTER CREATING TABLES ==========
-- Run these to verify creation was successful

-- Check pengiriman table
DESCRIBE pengiriman;
SHOW CREATE TABLE pengiriman\G

-- Check pesanan table
DESCRIBE pesanan;
SHOW CREATE TABLE pesanan\G

-- Check detail_pesanan table
DESCRIBE detail_pesanan;
SHOW CREATE TABLE detail_pesanan\G

-- Verify all FK constraints
SELECT 
  CONSTRAINT_NAME,
  TABLE_NAME,
  COLUMN_NAME,
  REFERENCED_TABLE_NAME,
  REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME IN ('pengiriman', 'pesanan', 'detail_pesanan')
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- ========== NOTES ==========
-- 
-- If you get FK error:
-- 1. Run queries 1-11 above
-- 2. Note down the COLUMN_TYPE of id_user in pengguna table
-- 3. Make sure it matches INT in pengiriman table
-- 4. Check ENGINE - should be InnoDB for both
-- 5. Check CHARSET - should match or be utf8mb4
-- 6. If all match and error persists:
--    a. Drop tables (query 13)
--    b. Use shipping-migration-no-fk.sql instead
--    c. Or check table name - maybe it's 'users' not 'pengguna'
--
