-- Run this ONCE in phpMyAdmin (database: aumerch) before using the inventory pages.
-- tbl_categories is empty and the products point to category_id 0, which doesn't exist.

INSERT INTO tbl_categories (category_id, category_name) VALUES
  (1, 'Apparel'),
  (2, 'Accessories'),
  (3, 'Others');

-- All 4 current products are apparel
UPDATE tbl_products SET category_id = 1 WHERE category_id NOT IN (1, 2, 3);
