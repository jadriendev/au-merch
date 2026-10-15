<?php
require_once __DIR__ . '/portal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf()) {
        portal_flash('Your session expired. Please try again.', 'error');
        header('Location: requests.php');
        exit;
    }
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($requestId > 0 && in_array($action, ['approve', 'reject'], true)) {
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
        $stmt = $conn->prepare("UPDATE tbl_stock_requests SET status = ?, approved_at = IF(? = 'Approved', NOW(), NULL) WHERE request_id = ? AND status IN ('Submitted', 'Pending')");
        $stmt->bind_param('ssi', $newStatus, $newStatus, $requestId);
        $stmt->execute();
        $changed = $stmt->affected_rows === 1;
        $stmt->close();
        if ($changed && $action === 'approve') {
            portal_flash('Request #' . $requestId . ' approved. You can now ship the stock.');
            header('Location: add_stock.php?request_id=' . $requestId);
            exit;
        }
        portal_flash($changed ? 'Request #' . $requestId . ' rejected.' : 'That request was already handled.', $changed ? 'success' : 'error');
    }
    header('Location: requests.php');
    exit;
}

$filters = portal_status_filters();
$statusFilter = (string) ($_GET['status'] ?? 'All');
if ($statusFilter !== 'All' && !isset($filters[$statusFilter])) {
    $statusFilter = 'All';
}

$sql = "SELECT r.request_id, r.requested_qty, r.status, r.notes, r.created_at, p.product_name, p.image, " . PORTAL_VARIANT_SQL . " AS variant
        FROM tbl_stock_requests r LEFT JOIN tbl_products p ON p.product_id = r.product_id";
$params = [];
if ($statusFilter !== 'All') {
    $list = $filters[$statusFilter];
    $sql .= ' WHERE r.status IN (' . implode(',', array_fill(0, count($list), '?')) . ')';
    $params = $list;
}
$sql .= ' ORDER BY r.created_at DESC, r.request_id DESC';
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

portal_header('requests', 'Stock Requests', 'Review stock requests sent by the admin. Approve them, then ship the stock.');
?>
<section class="section-card card">
    <div class="table-toolbar"><p><?php echo count($rows); ?> request<?php echo count($rows) === 1 ? '' : 's'; ?> found</p><div class="history-tools"><form method="get" action="requests.php"><label class="sr-only" for="status-filter">Filter by status</label><select class="filter-select" id="status-filter" name="status" onchange="this.form.submit()"><option value="All">All Status</option><?php foreach (array_keys($filters) as $label): ?><option value="<?php echo portal_escape($label); ?>" <?php echo $statusFilter === $label ? 'selected' : ''; ?>><?php echo portal_escape($label); ?></option><?php endforeach; ?></select><noscript><button class="btn" type="submit">Apply</button></noscript></form></div></div>
    <div class="table-wrap"><table><thead><tr><th>Request</th><th>Product</th><th>Variant / Size</th><th>Quantity</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="6" class="empty-row">No requests match this filter.</td></tr>
        <?php else: foreach ($rows as $row): $s = portal_status_class($row['status']); ?>
            <tr>
                <td>#<?php echo (int) $row['request_id']; ?><small class="subtext"><?php echo portal_escape(date('M j, Y g:i A', strtotime($row['created_at']))); ?></small></td>
                <td><div class="product-cell"><span class="product-thumb"><img src="<?php echo portal_escape(portal_image($row['image'])); ?>" alt=""></span><span><?php echo portal_escape($row['product_name'] ?? 'Removed product'); ?><?php if (!empty($row['notes'])): ?><small class="subtext"><?php echo portal_escape($row['notes']); ?></small><?php endif; ?></span></div></td>
                <td><?php echo portal_escape($row['variant']); ?></td>
                <td><?php echo (int) $row['requested_qty']; ?> pcs</td>
                <td><span class="status <?php echo portal_escape($s); ?>"><?php echo portal_escape(portal_status_label($row['status'])); ?></span></td>
                <td>
                    <?php if ($s === 'pending'): ?>
                        <div class="row-actions">
                            <form method="post"><input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>"><input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>"><input type="hidden" name="action" value="approve"><button class="btn small success" type="submit"><i class="fa-solid fa-check"></i> Approve</button></form>
                            <form method="post" onsubmit="return confirm('Reject this request?');"><input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>"><input type="hidden" name="request_id" value="<?php echo (int) $row['request_id']; ?>"><input type="hidden" name="action" value="reject"><button class="btn small danger" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button></form>
                        </div>
                    <?php elseif ($s === 'approved'): ?>
                        <a class="btn small" href="add_stock.php?request_id=<?php echo (int) $row['request_id']; ?>"><i class="fa-solid fa-truck-fast"></i> Ship stock</a>
                    <?php else: ?>
                        <span class="subtext">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody></table></div>
</section>
<?php portal_footer(); ?>
