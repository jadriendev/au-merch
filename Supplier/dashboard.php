<?php
require_once __DIR__ . '/portal_helpers.php';

$res = $conn->query("SELECT
    COALESCE(SUM(status IN ('Submitted', 'Pending')), 0) AS pending,
    COALESCE(SUM(status = 'Approved'), 0) AS approved,
    COALESCE(SUM(status = 'Shipped'), 0) AS shipped,
    COALESCE(SUM(status = 'Received'), 0) AS received
    FROM tbl_stock_requests");
$counts = $res->fetch_assoc();

$recent = $conn->query("SELECT r.request_id, r.requested_qty, r.status, r.created_at, p.product_name, p.image, " . PORTAL_VARIANT_SQL . " AS variant
    FROM tbl_stock_requests r
    LEFT JOIN tbl_products p ON p.product_id = r.product_id
    ORDER BY r.created_at DESC, r.request_id DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

portal_header('dashboard', 'Dashboard', "Here's a quick overview of your stock requests.");
?>
<section class="hero card">
    <div class="hero-brand"><img src="../images/Arellano_University_New_Logo.png" alt="Arellano University crest"><div class="hero-copy"><strong>AU MERCH</strong><span>Quality Products. One AU Community.</span></div></div>
</section>
<section class="stats-grid four" aria-label="Request summary">
    <article class="stat-card card"><div class="stat-icon amber"><i class="fa-regular fa-clock"></i></div><div><span class="stat-label">Waiting for You</span><strong class="stat-value"><?php echo (int) $counts['pending']; ?></strong><span class="stat-foot">requests to approve</span></div></article>
    <article class="stat-card card"><div class="stat-icon blue"><i class="fa-regular fa-circle-check"></i></div><div><span class="stat-label">Approved</span><strong class="stat-value"><?php echo (int) $counts['approved']; ?></strong><span class="stat-foot">ready to ship</span></div></article>
    <article class="stat-card card"><div class="stat-icon violet"><i class="fa-solid fa-truck-fast"></i></div><div><span class="stat-label">Shipped</span><strong class="stat-value"><?php echo (int) $counts['shipped']; ?></strong><span class="stat-foot">waiting for admin</span></div></article>
    <article class="stat-card card"><div class="stat-icon green"><i class="fa-solid fa-box-open"></i></div><div><span class="stat-label">Received by Admin</span><strong class="stat-value"><?php echo (int) $counts['received']; ?></strong><span class="stat-foot">added to store stock</span></div></article>
</section>
<section class="section-card card">
    <div class="section-title"><h2>Recent Requests</h2><a href="requests.php">View all <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="table-wrap"><table><thead><tr><th>Product</th><th>Requested</th><th>Date</th><th>Status</th></tr></thead><tbody>
        <?php if (!$recent): ?>
            <tr><td colspan="4" class="empty-row">No requests from the admin yet. They will appear here.</td></tr>
        <?php else: foreach ($recent as $row): ?>
            <tr>
                <td><div class="product-cell"><span class="product-thumb"><img src="<?php echo portal_escape(portal_image($row['image'])); ?>" alt=""></span><span><?php echo portal_escape($row['product_name'] ?? 'Removed product'); ?><small class="subtext"><?php echo portal_escape($row['variant']); ?></small></span></div></td>
                <td><?php echo (int) $row['requested_qty']; ?> pcs</td>
                <td><?php echo portal_escape(date('M j, Y g:i A', strtotime($row['created_at']))); ?></td>
                <td><span class="status <?php echo portal_escape(portal_status_class($row['status'])); ?>"><?php echo portal_escape(portal_status_label($row['status'])); ?></span></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody></table></div>
</section>
<?php portal_footer(); ?>
