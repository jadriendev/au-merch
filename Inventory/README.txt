AU MERCH - INVENTORY (ADMIN ONLY)

1. Put everything in your Inventory folder (css/inventory.css goes next to your existing css files).
   Your existing dashboard.css and products.css stay as they are. These files REPLACE your old
   dashboard.php, products.php and the empty index.php.
2. Run migration.sql once in phpMyAdmin.
3. Check config.php (DB user/password) - default is XAMPP: root / no password.
4. Make sure the ../images folder exists and is writable (for product image uploads).
5. Open /Inventory/index.php and log in with your tbl_admin account.
   The plain-text admin password is automatically converted to a secure hash on first login.
