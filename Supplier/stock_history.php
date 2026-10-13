<?php
require_once __DIR__ . '/portal_helpers.php';
$supplierId = portal_supplier_id();
$userId = (int) $supplierId;
$allowedStatuses = ['Pending', 'Approved', 'Rejected'];
$statusFilter = trim((string) ($_GET['status'] ?? 'All'));
if ($statusFilter !== 'All' && !in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}
if ($statusFilter === 'All') {
    $stmt = $conn->prepare("SELECT r.product_id, p.product_name, COALESCE(NULLIF(r.variant, ''), CONCAT_WS(' / ', NULLIF(p.color, ''), NULLIF(p.variation, ''))) AS variant, r.requested_qty AS quantity, r.unit_price, r.status, r.notes, r.created_at FROM tbl_stock_requests r INNER JOIN tbl_products p ON p.product_id = r.product_id WHERE r.requested_by = ? ORDER BY r.created_at DESC, r.request_id DESC");
    $stmt->bind_param('i', $userId);
} elseif ($statusFilter === 'Pending') {
    $stmt = $conn->prepare("SELECT r.product_id, p.product_name, COALESCE(NULLIF(r.variant, ''), CONCAT_WS(' / ', NULLIF(p.color, ''), NULLIF(p.variation, ''))) AS variant, r.requested_qty AS quantity, r.unit_price, r.status, r.notes, r.created_at FROM tbl_stock_requests r INNER JOIN tbl_products p ON p.product_id = r.product_id WHERE r.requested_by = ? AND r.status IN ('Submitted', 'Pending') ORDER BY r.created_at DESC, r.request_id DESC");
    $stmt->bind_param('i', $userId);
} else {
    $stmt = $conn->prepare("SELECT r.product_id, p.product_name, COALESCE(NULLIF(r.variant, ''), CONCAT_WS(' / ', NULLIF(p.color, ''), NULLIF(p.variation, ''))) AS variant, r.requested_qty AS quantity, r.unit_price, r.status, r.notes, r.created_at FROM tbl_stock_requests r INNER JOIN tbl_products p ON p.product_id = r.product_id WHERE r.requested_by = ? AND LOWER(r.status) = LOWER(?) ORDER BY r.created_at DESC, r.request_id DESC");
    $stmt->bind_param('is', $userId, $statusFilter);
}
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$products = portal_products($conn);
portal_header('stock-history', 'Stock History', 'View all your submitted stocks and their review status.');
?>
<section class="section-card card">
    <div class="table-toolbar"><p><?php echo count($submissions); ?> submission<?php echo count($submissions) === 1 ? '' : 's'; ?> found</p><div class="history-tools"><form method="get" action="stock_history.php"><label class="sr-only" for="status-filter">Filter by status</label><select class="filter-select" id="status-filter" name="status" onchange="this.form.submit()"><option value="All" <?php echo $statusFilter === 'All' ? 'selected' : ''; ?>>All Status</option><?php foreach ($allowedStatuses as $status): ?><option value="<?php echo portal_escape($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo portal_escape($status); ?></option><?php endforeach; ?></select><noscript><button class="btn" type="submit">Apply</button></noscript></form></div></div>
    <div class="table-wrap"><table><thead><tr><th>Date</th><th>Product</th><th>Variant / Size</th><th>Quantity</th><th>Unit Price</th><th>Status</th></tr></thead><tbody>
        <?php if (!$submissions): ?>
            <tr><td colspan="6" class="empty-row">No submissions match this filter.</td></tr>
        <?php else: ?>
        <?php foreach ($submissions as $row): $image = $products[(int) $row['product_id']]['image_path'] ?? '../images/Arellano_University_New_Logo.png'; ?>
            <tr><td><?php echo portal_escape(date('M j, Y', strtotime($row['created_at']))); ?><small class="subtext"><?php echo portal_escape(date('g:i A', strtotime($row['created_at']))); ?></small></td><td><div class="product-cell"><span class="product-thumb"><img src="<?php echo portal_escape($image); ?>" alt=""></span><?php echo portal_escape($row['product_name']); ?></div><?php if (!empty($row['notes'])): ?><small class="subtext"><?php echo portal_escape($row['notes']); ?></small><?php endif; ?></td><td><?php echo portal_escape($row['variant']); ?></td><td><?php echo (int) $row['quantity']; ?> pcs</td><td><?php echo $row['unit_price'] === null ? '—' : '₱' . number_format((float) $row['unit_price'], 2); ?></td><td><span class="status <?php echo portal_escape(portal_status_class($row['status'])); ?>"><?php echo portal_escape(portal_status_label($row['status'])); ?></span></td></tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody></table></div>
</section>
<?php portal_footer(); ?>
