<?php
require 'config.php';
require_admin();

$src = (string)($_GET['source'] ?? '');
if (!in_array($src, ['inventory', 'supplier'], true)) $src = '';

$per  = 15;
$page = max(1, (int)($_GET['page'] ?? 1));

// Both log tables are normalised to the same columns. CONVERT keeps the UNION safe
// even if the two tables use different character sets.
$utf = fn(string $col): string => "CONVERT($col USING utf8mb4) COLLATE utf8mb4_unicode_ci";
$parts = [];
if ($src !== 'supplier') {
    $parts[] = "SELECT 'Inventory' AS source, l.log_id, l.product_id, " . $utf('l.action') . " AS action, " . $utf('l.performed_by') . " AS performed_by,
                       l.quantity_changed AS qty, l.previous_stock, l.new_stock, NULL AS ref_no, l.date_time
                FROM tbl_logs l";
}
if ($src !== 'inventory') {
    $parts[] = "SELECT 'Supplier' AS source, s.log_id, s.product_id, " . $utf('s.action') . " AS action, " . $utf('s.performed_by') . " AS performed_by,
                       s.quantity AS qty, NULL AS previous_stock, NULL AS new_stock, " . $utf('s.reference_number') . " AS ref_no, s.date_time
                FROM tbl_supplier_logs s";
}
$union = implode(' UNION ALL ', $parts);

$total = (int)$pdo->query("SELECT COUNT(*) FROM ($union) u")->fetchColumn();
$page  = min($page, max(1, (int)ceil($total / $per)));
$off   = ($page - 1) * $per;
$rows  = $pdo->query("SELECT u.*, p.product_name
                      FROM ($union) u LEFT JOIN tbl_products p ON p.product_id = u.product_id
                      ORDER BY u.date_time DESC, u.source, u.log_id DESC
                      LIMIT $per OFFSET $off")->fetchAll();

function who(string $by): string {
    return preg_match('/^supplier#(\d+)$/', $by, $m) ? 'Supplier #' . $m[1] : $by;
}

$title = 'Stock Logs'; $css = 'products'; $active = 'logs';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet"><h1><span>Stock Logs</span><span>A history of every product, stock and supplier activity.</span></h1></div>
            </div>
        </section>

        <section>
            <div class="max">
                <form method="get" class="input-fields">
                    <div class="status-filter">
                        <select name="source" onchange="this.form.submit()">
                            <option value="">All activity</option>
                            <option value="inventory" <?= $src === 'inventory' ? 'selected' : '' ?>>Inventory only</option>
                            <option value="supplier" <?= $src === 'supplier' ? 'selected' : '' ?>>Supplier only</option>
                        </select>
                    </div>
                </form>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="section-container">
                    <div class="recent-order">
                        <div class="head"><h1>Activity</h1></div>
                        <div class="table-2">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="left-radius">Product</th>
                                        <th>Action</th>
                                        <th>Qty</th>
                                        <th>Before → After</th>
                                        <th>Reference</th>
                                        <th>By</th>
                                        <th class="right-radius">Date &amp; Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="color-change"><?= e($r['product_name'] ?? 'Deleted product #' . $r['product_id']) ?></td>
                                        <td><div class="status <?= action_class($r['action']) ?>"><?= e($r['action']) ?></div></td>
                                        <td><?= $r['qty'] === null ? '—' : e($r['qty']) ?></td>
                                        <td><?= e($r['previous_stock'] ?? '—') ?> → <?= e($r['new_stock'] ?? '—') ?></td>
                                        <td><?= e($r['ref_no'] ?: '—') ?></td>
                                        <td><?= e(who((string)$r['performed_by'])) ?><small class="sub"><?= e($r['source']) ?></small></td>
                                        <td><?= e(date('M d, Y h:i A', strtotime((string)$r['date_time']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$rows): ?>
                                    <tr><td colspan="7">No activity yet.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php pager($total, $page, $per, 'entries'); ?>
                    </div>
                </div>
            </div>
        </section>
<?php require 'layout_bottom.php'; ?>
