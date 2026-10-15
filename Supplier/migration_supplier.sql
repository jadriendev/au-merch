-- Run this ONCE in phpMyAdmin (database: aumerch) for the Admin <-> Supplier stock flow.
ALTER TABLE tbl_stock_requests
  ADD COLUMN IF NOT EXISTS shipped_qty INT NULL,
  ADD COLUMN IF NOT EXISTS reference_number VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS supplier_notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS received_at DATETIME NULL;

-- allow notes with special characters in supplier logs
ALTER TABLE tbl_supplier_logs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Request statuses used: Submitted -> Approved (or Rejected) -> Shipped -> Received
