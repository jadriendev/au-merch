<?php
require 'config.php';
require_admin();

$per  = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$pdo->query('SELECT COUNT(*) FROM tbl_logs')->fetchColumn();
$page = min($page, max(1, (int)ceil($total / $per)));
$off  = ($page - 1) * $per;
$rows = $pdo->query("SELECT l.*, p.product_name FROM tbl_logs l
                     LEFT JOIN tbl_products p ON p.product_id = l.product_id
                     ORDER BY l.log_id DESC LIMIT $per OFFSET $off")->fetchAll();

$title = 'Stock Logs'; $css = 'products'; $active = 'logs';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet"><h1><span>Stock Logs</span><span>A history of every product and stock change.</span></h1></div>
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
                                        <th>By</th>
                                        <th class="right-radius">Date &amp; Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td class="color-change"><?= e($r['product_name'] ?? 'Deleted product #' . $r['product_id']) ?></td>
                                        <td><div class="status <?= action_class($r['action']) ?>"><?= e($r['action']) ?></div></td>
                                        <td><?= $r['quantity_changed'] === null ? '—' : e($r['quantity_changed']) ?></td>
                                        <td><?= e($r['previous_stock'] ?? '—') ?> → <?= e($r['new_stock'] ?? '—') ?></td>
                                        <td><?= e($r['performed_by']) ?></td>
                                        <td><?= e(date('M d, Y h:i A', strtotime($r['date_time']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$rows): ?>
                                    <tr><td colspan="6">No activity yet.</td></tr>
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
