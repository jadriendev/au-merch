-- =====================================================================
-- inventory_migration.sql
-- Run in phpMyAdmin: select database `aumerch` -> SQL tab -> paste -> Go.
-- Safe to run more than once (nothing is dropped or overwritten).
-- This REPLACES the old migration.sql (its category seed is included below).
-- It also includes everything in supplier/migration_supplier.sql, so you do
-- not need to run that one separately.
-- =====================================================================

-- 1) Columns for the Admin <-> Supplier stock flow (received_qty is new)
ALTER TABLE tbl_stock_requests
  ADD COLUMN IF NOT EXISTS shipped_qty INT NULL,
  ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS supplier_notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS received_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS received_qty INT NULL;

-- 2) Allow notes with special characters in supplier logs
ALTER TABLE tbl_supplier_logs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 3) Categories: tbl_categories is empty and products point to category_id 0
INSERT IGNORE INTO tbl_categories (category_id, category_name) VALUES
  (1, 'Apparel'),
  (2, 'Accessories'),
  (3, 'Others');

-- Products whose category does not exist (category_id 0) are apparel.
-- Only orphaned products are touched, so re-running never changes products
-- that already have a real category.
UPDATE tbl_products SET category_id = 1
WHERE category_id NOT IN (SELECT category_id FROM tbl_categories);

-- Request statuses used: Submitted -> Approved (or Rejected) -> Shipped -> Received
