<?php
/**
 * stock_deduct.php  -  customer side helper (uses the mysqli $conn from connection/config.php)
 *
 * WHAT IT DOES
 *   When an order is placed, subtract the ordered quantities from tbl_products.stock and
 *   write a "Stock Out" row to tbl_logs, so the inventory admin sees it on the dashboard,
 *   in Stock Logs, on the low-stock bell, and in "Running low" on Request a Stock.
 *
 * HOW TO USE (in the file that places the order - e.g. the checkout route in api/index.php)
 *   1. require_once __DIR__ . '/stock_deduct.php';
 *   2. Call it INSIDE the same transaction that inserts into tbl_orders / tbl_order_items,
 *      AFTER the order items are inserted and BEFORE you commit:
 *
 *          $conn->begin_transaction();
 *          ... insert tbl_orders, get $orderId and $orderCode ...
 *          ... insert tbl_order_items for $orderId ...
 *
 *          $problem = au_deduct_stock($conn, $orderId, $orderCode);
 *          if ($problem !== null) {
 *              $conn->rollback();
 *              // show $problem to the customer, e.g. "Only 2 left of AU Pants."
 *              return;
 *          }
 *          $conn->commit();
 *
 * Rows are locked (SELECT ... FOR UPDATE) in product_id order, so two customers buying the
 * last item at the same time cannot both succeed, and the check cannot deadlock.
 *
 * Returns null on success, or a customer-friendly error message (caller must roll back).
 */
function au_deduct_stock(mysqli $conn, int $orderId, string $orderCode): ?string
{
    $items = $conn->prepare('SELECT product_id, SUM(quantity) AS qty FROM tbl_order_items WHERE order_id = ? GROUP BY product_id ORDER BY product_id');
    $items->bind_param('i', $orderId);
    $items->execute();
    $rows = $items->get_result()->fetch_all(MYSQLI_ASSOC);
    $items->close();

    if (!$rows) {
        return 'This order has no items.';
    }

    $lock   = $conn->prepare('SELECT product_name, stock, status FROM tbl_products WHERE product_id = ? FOR UPDATE');
    $update = $conn->prepare('UPDATE tbl_products SET stock = ? WHERE product_id = ?');
    $log    = $conn->prepare("INSERT INTO tbl_logs (product_id, action, performed_by, quantity_changed, previous_stock, new_stock) VALUES (?, 'Stock Out', ?, ?, ?, ?)");

    foreach ($rows as $row) {
        $pid = (int) $row['product_id'];
        $qty = (int) $row['qty'];

        $lock->bind_param('i', $pid);
        $lock->execute();
        $p = $lock->get_result()->fetch_assoc();

        if (!$p || $p['status'] !== 'Available') {
            return 'A product in your order is no longer available.';
        }

        $prev = (int) $p['stock'];
        if ($prev < $qty) {
            return $prev > 0
                ? 'Only ' . $prev . ' left of ' . $p['product_name'] . '.'
                : $p['product_name'] . ' is out of stock.';
        }

        $new = $prev - $qty;
        $update->bind_param('ii', $new, $pid);
        $update->execute();

        $by = 'order ' . $orderCode;
        $log->bind_param('isiii', $pid, $by, $qty, $prev, $new);
        $log->execute();
    }

    $lock->close();
    $update->close();
    $log->close();
    return null;
}
