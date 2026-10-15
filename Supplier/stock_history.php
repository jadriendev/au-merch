<?php
require_once __DIR__ . '/portal_helpers.php';

$by = 'supplier#' . portal_supplier_id();
$allowed = ['Shipped', 'Received'];
$statusFilter = (string) ($_GET['status'] ?? 'All');
if ($statusFilter !== 'All' && !in_array($statusFilter, $allowed, true)) {
    $statusFilter = 'All';
}

$sql = "SELECT l.log_id, l.request_id, l.quantity, l.unit_price, l.reference_number, l.notes, l.date_time,
               r.status AS req_status, r.received_at, p.product_name, p.image, " . PORTAL_VARIANT_SQL . " AS variant
        FROM tbl_supplier_logs l
        LEFT JOIN tbl_stock_requests r ON r.request_id = l.request_id
        LEFT JOIN tbl_products p ON p.product_id = l.product_id
        WHERE l.performed_by = ?";
$params = [$by];
if ($statusFilter !== 'All') {
    $sql .= ' AND r.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY l.log_id DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

portal_header('stock-history', 'Stock History', 'Every shipment you sent to the admin and whether it was received.');
?>
<section class="section-card card">
    <div class="table-toolbar"><p><?php echo count($rows); ?> shipment<?php echo count($rows) === 1 ? '' : 's'; ?> found</p><div class="history-tools"><form method="get" action="stock_history.php"><label class="sr-only" for="status-filter">Filter by status</label><select class="filter-select" id="status-filter" name="status" onchange="this.form.submit()"><option value="All">All Status</option><?php foreach ($allowed as $label): ?><option value="<?php echo portal_escape($label); ?>" <?php echo $statusFilter === $label ? 'selected' : ''; ?>><?php echo portal_escape($label); ?></option><?php endforeach; ?></select><noscript><button class="btn" type="submit">Apply</button></noscript></form></div></div>
    <div class="table-wrap"><table><thead><tr><th>Date Shipped</th><th>Product</th><th>Variant / Size</th><th>Request</th><th>Quantity</th><th>Reference</th><th>Unit Price</th><th>Status</th></tr></thead><tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="8" class="empty-row">No shipments yet. Ship an approved request from Add Stock.</td></tr>
        <?php else: foreach ($rows as $row): $s = portal_status_class((string) ($row['req_status'] ?? 'Shipped')); ?>
            <tr>
                <td><?php echo portal_escape(date('M j, Y', strtotime($row['date_time']))); ?><small class="subtext"><?php echo portal_escape(date('g:i A', strtotime($row['date_time']))); ?></small></td>
                <td><div class="product-cell"><span class="product-thumb"><img src="<?php echo portal_escape(portal_image($row['image'])); ?>" alt=""></span><span><?php echo portal_escape($row['product_name'] ?? 'Removed product'); ?><?php if (!empty($row['notes'])): ?><small class="subtext"><?php echo portal_escape($row['notes']); ?></small><?php endif; ?></span></div></td>
                <td><?php echo portal_escape($row['variant']); ?></td>
                <td>#<?php echo (int) $row['request_id']; ?></td>
                <td>+ <?php echo (int) $row['quantity']; ?> pcs</td>
                <td><?php echo portal_escape($row['reference_number'] ?: '—'); ?></td>
                <td><?php echo $row['unit_price'] === null || $row['unit_price'] === '' ? '—' : '₱' . number_format((float) $row['unit_price'], 2); ?></td>
                <td><span class="status <?php echo portal_escape($s); ?>"><?php echo $s === 'received' ? 'Received by admin' : 'Shipped'; ?></span><?php if ($s === 'received' && !empty($row['received_at'])): ?><small class="subtext"><?php echo portal_escape(date('M j, Y g:i A', strtotime($row['received_at']))); ?></small><?php endif; ?></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody></table></div>
</section>
<?php portal_footer(); ?>
