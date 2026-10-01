<?php
require 'config.php';
require_admin();

$s = $pdo->query('SELECT COUNT(*) AS total,
                         COALESCE(SUM(stock),0) AS units,
                         COALESCE(SUM(status = "Available" AND stock BETWEEN 1 AND ' . LOW_STOCK . '),0) AS low,
                         COALESCE(SUM(stock <= 0),0) AS out_of_stock
                  FROM tbl_products')->fetch();
$recent = $pdo->query('SELECT l.*, p.product_name FROM tbl_logs l
                       LEFT JOIN tbl_products p ON p.product_id = l.product_id
                       ORDER BY l.log_id DESC LIMIT 5')->fetchAll();

$title  = 'Dashboard';
$css    = 'dashboard';
$active = 'dashboard';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Good Day, <?= e($_SESSION['first_name']) ?>!</span>
                        <span>Here's a quick overview of your inventory.</span>
                    </h1>
                    <div class="date"><i class="far fa-calendar"></i> <?= date('M d, Y') ?></div>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="grid">
                    <?php foreach ([
                        ['fa fa-box', 'Total Products', $s['total'], 'in the catalog'],
                        ['fa fa-boxes-stacked', 'Total Units', $s['units'], 'items in stock'],
                        ['fa fa-triangle-exclamation', 'Low Stock', $s['low'], 'need restocking'],
                        ['fa fa-ban', 'Out of Stock', $s['out_of_stock'], 'unavailable to buy'],
                    ] as [$icon, $label, $val, $note]): ?>
                    <div class="cards">
                        <div class="card-con">
                            <div class="left"><i class="<?= e($icon) ?>"></i></div>
                            <div class="right">
                                <h3><?= e($label) ?></h3>
                                <h2><?= number_format((int)$val) ?></h2>
                                <h4><?= e($note) ?></h4>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="section-container">
                    <div class="recent-order">
                        <div class="head">
                            <h1>Recent Stock Activity</h1>
                            <a href="logs.php" class="view-all">View all <i class="fa fa-arrow-right"></i></a>
                        </div>
                        <div class="table">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="left-radius">Product</th>
                                        <th>Action</th>
                                        <th>Qty</th>
                                        <th>Stock</th>
                                        <th class="right-radius">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent as $r): ?>
                                    <tr>
                                        <td class="order"><?= e($r['product_name'] ?? 'Deleted product') ?></td>
                                        <td><div class="status <?= action_class($r['action']) ?>"><?= e($r['action']) ?></div></td>
                                        <td><?= $r['quantity_changed'] === null ? '—' : e($r['quantity_changed']) ?></td>
                                        <td><?= e($r['previous_stock'] ?? '—') ?> → <?= e($r['new_stock'] ?? '—') ?></td>
                                        <td><?= e(date('M d, Y', strtotime($r['date_time']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$recent): ?>
                                    <tr><td colspan="5">No activity yet.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="quick-action">
                        <div class="head"><h1>Quick Actions</h1></div>
                        <div class="action-card-grid">
                            <?php foreach ([
                                ['add_product.php', 'deployed_code', 'Add Product', 'Create a new product listing'],
                                ['products.php', 'inventory_2', 'Manage Products', 'Edit, restock, or remove items'],
                                ['logs.php', 'history', 'Stock Logs', 'See every stock movement'],
                            ] as [$href, $icon, $t, $d]): ?>
                            <a href="<?= e($href) ?>" class="action-card">
                                <div class="action-card-con">
                                    <span class="material-icon material-symbols-outlined"><?= e($icon) ?></span>
                                    <h3><span><?= e($t) ?></span><span><?= e($d) ?></span></h3>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
<?php require 'layout_bottom.php'; ?>
