AU MERCH - INVENTORY (ADMIN ONLY)  -  PART 1

FOLDER LAYOUT
  au-merch/
    inventory/            <- all the PHP files here, plus inventory_migration.sql and this README
      css/                <- dashboard.css, products.css, order_history.css (yours, untouched)
                             + inventory.css (NEW)
    api/stock_deduct.php  <- NEW, customer-side helper (see "Auto-deduct" below)

INSTALL (in this order)
 1. Copy the files in this package over your inventory folder.
    NEW:      request_stock.php, receive_stock.php, css/inventory.css, inventory_migration.sql
    CHANGED:  config.php, layout_top.php, index.php (login), logs.php
    UNCHANGED (keep yours): dashboard.php, products.php, add_product.php, edit.php, delete.php,
              stock.php, product_form.php, order_history.php, layout_bottom.php, logout.php
 2. Run inventory_migration.sql ONCE in phpMyAdmin (database: aumerch -> SQL tab).
    It is safe to run again. It replaces the old migration.sql, and it already includes
    supplier/migration_supplier.sql, so you can skip that one.
 3. Check config.php (DB user/password). Default is XAMPP: root / no password.
 4. Make sure au-merch/images exists and is writable (product image uploads).
 5. Open /inventory/index.php and log in with your tbl_admin account.

STOCK FLOW
  Request a Stock (inventory)  -> status Submitted
  Supplier portal               -> Approved (or Rejected) -> Shipped
  Receive Stock (inventory)     -> Received, stock goes up, logged in Stock Logs
  Add Product                   -> brand-new items only (restocks go through Request / Receive)
  Stock In / Out (Products)     -> manual corrections only

AUTO-DEDUCT WHEN A CUSTOMER BUYS
  The customer checkout code was not part of this package, so inventory cannot change it.
  Give api/stock_deduct.php to whoever owns checkout. The instructions are at the top of the file.

QUICK TEST
  1. Request a Stock -> send a request for any product.
  2. Supplier portal -> Requests -> Approve -> Add Stock -> Send to Admin.
  3. Receive Stock   -> the shipment shows up (sidebar badge too). Confirm it.
  4. Products shows the new stock. Stock Logs shows Request Sent, Stock Shipped, Stock In and Stock Received.
