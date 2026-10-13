<?php
require_once __DIR__ . '/portal_helpers.php';
$supplierId = portal_supplier_id();
$counts = ['total' => 0, 'pending' => 0, 'approved' => 0];
$userId = (int) $supplierId;
$stmt = $conn->prepare("SELECT COUNT(*) AS total, SUM(status IN ('Submitted', 'Pending')) AS pending, SUM(LOWER(status) = 'approved') AS approved FROM tbl_stock_requests WHERE requested_by = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$counts = array_merge($counts, $stmt->get_result()->fetch_assoc() ?: []);
$stmt->close();
$stmt = $conn->prepare("SELECT r.request_id, r.product_id, p.product_name, COALESCE(NULLIF(r.variant, ''), CONCAT_WS(' / ', NULLIF(p.color, ''), NULLIF(p.variation, ''))) AS variant, r.requested_qty AS quantity, r.status, r.created_at FROM tbl_stock_requests r INNER JOIN tbl_products p ON p.product_id = r.product_id WHERE r.requested_by = ? ORDER BY r.created_at DESC, r.request_id DESC LIMIT 6");
$stmt->bind_param('i', $userId);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$products = portal_products($conn);
portal_header('dashboard', 'Dashboard', "Here's a quick overview of your stock updates.");
?>
<section class="hero card">
    <div class="hero-brand"><img src="../images/Arellano_University_New_Logo.png" alt="Arellano University crest"><div class="hero-copy"><strong>AU MERCH</strong><span>Quality Products. One AU Community.</span></div></div>
</section>
<section class="stats-grid" aria-label="Stock summary">
    <article class="stat-card card"><div class="stat-icon blue"><i class="fa-solid fa-cube"></i></div><div><span class="stat-label">Total Stocks Added</span><strong class="stat-value"><?php echo (int) $counts['total']; ?></strong><span class="stat-foot">all submitted requests</span></div></article>
    <article class="stat-card card"><div class="stat-icon amber"><i class="fa-regular fa-clock"></i></div><div><span class="stat-label">Pending with Admin</span><strong class="stat-value"><?php echo (int) $counts['pending']; ?></strong><span class="stat-foot">awaiting review</span></div></article>
    <article class="stat-card card"><div class="stat-icon green"><i class="fa-regular fa-circle-check"></i></div><div><span class="stat-label">Approved by Admin</span><strong class="stat-value"><?php echo (int) $counts['approved']; ?></strong><span class="stat-foot">approved submissions</span></div></article>
</section>
<section class="section-card card">
    <div class="section-title"><h2>Recent Activity</h2><a href="stock_history.php">View all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="table-wrap"><table><thead><tr><th>Product</th><th>Quantity</th><th>Date Added</th><th>Status</th></tr></thead><tbody>
        <?php if (!$recent): ?>
            <tr><td colspan="4" class="empty-row">No stock submissions yet. Your recent activity will appear here.</td></tr>
        <?php else: ?>
        <?php foreach ($recent as $row): $image = $products[(int) $row['product_id']]['image_path'] ?? '../images/Arellano_University_New_Logo.png'; ?>
            <tr><td><div class="product-cell"><span class="product-thumb"><img src="<?php echo portal_escape($image); ?>" alt=""></span><span><?php echo portal_escape($row['product_name']); ?><small class="subtext"><?php echo portal_escape($row['variant']); ?></small></span></div></td><td>+ <?php echo (int) $row['quantity']; ?> pcs</td><td><?php echo portal_escape(date('M j, Y g:i A', strtotime($row['created_at']))); ?></td><td><span class="status <?php echo portal_escape(portal_status_class($row['status'])); ?>"><?php echo portal_escape(portal_status_label($row['status'])); ?></span></td></tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody></table></div>
</section>
<?php portal_footer(); ?>
