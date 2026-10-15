<?php
require 'config.php';
require_admin();

if (!flow_ready($pdo)) {
    flash('Run inventory_migration.sql in phpMyAdmin first, then open Request a Stock again.', 'err');
    header('Location: dashboard.php');
    exit;
}

$products = $pdo->query('SELECT product_id, product_name, color, variation, stock, status FROM tbl_products ORDER BY product_name, color')->fetchAll();
$byId = [];
foreach ($products as $p) $byId[(int)$p['product_id']] = $p;

$in = ['product_id' => (int)($_GET['product_id'] ?? 0), 'qty' => '', 'variant' => '', 'notes' => ''];
if (!isset($byId[$in['product_id']])) $in['product_id'] = 0;
$error = '';

/* ---------- Send a new request ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $in = [
        'product_id' => (int)($_POST['product_id'] ?? 0),
        'qty'        => trim((string)($_POST['qty'] ?? '')),
        'variant'    => trim((string)($_POST['variant'] ?? '')),
        'notes'      => trim((string)($_POST['notes'] ?? '')),
    ];
    $qty = filter_var($in['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);

    if (!isset($byId[$in['product_id']]))   $error = 'Choose the product you want restocked.';
    elseif ($qty === false)                 $error = 'Quantity must be a whole number from 1 to 100,000.';
    elseif (mb_strlen($in['variant']) > 255) $error = 'Variant / size is too long (max 255 characters).';
    elseif (mb_strlen($in['notes']) > 1000)  $error = 'Notes are too long (max 1000 characters).';
    else {
        $pid     = $in['product_id'];
        $variant = $in['variant'] !== '' ? $in['variant'] : variant_for($byId[$pid]);
        $notes   = $in['notes'] !== '' ? $in['notes'] : null;
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO tbl_stock_requests (product_id, requested_qty, status, requested_by, variant, notes)
                           VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$pid, $qty, 'Submitted', admin_id(), $variant, $notes]);
            $rid = (int)$pdo->lastInsertId();
            log_supplier($pdo, $rid, $pid, 'Request Sent', $qty, null, null, $notes, 'Submitted');
            $pdo->commit();
            flash("Request #$rid sent to the supplier. You'll see it here once they approve and ship it.");
            header('Location: request_stock.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Could not send the request. Please try again.';
        }
    }
}

/* ---------- Low / out of stock panel ---------- */
$lowQ = $pdo->prepare("SELECT product_id, product_name, color, stock, status FROM tbl_products
                       WHERE status = 'Available' AND stock <= ? ORDER BY stock ASC, product_name LIMIT 20");
$lowQ->execute([LOW_STOCK]);
$lowRows = $lowQ->fetchAll();

$open = [];   // product_id => number of requests still in progress
foreach ($pdo->query("SELECT product_id, COUNT(*) AS c FROM tbl_stock_requests
                      WHERE status IN ('Submitted','Pending','Approved','Shipped') GROUP BY product_id") as $r) {
    $open[(int)$r['product_id']] = (int)$r['c'];
}

/* ---------- Request list ---------- */
$filters = [
    'Pending'  => ['Submitted', 'Pending'],
    'Approved' => ['Approved'],
    'Shipped'  => ['Shipped'],
    'Received' => ['Received'],
    'Rejected' => ['Rejected'],
];
$sf = (string)($_GET['status'] ?? '');
if (!isset($filters[$sf])) $sf = '';
$per  = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$w = '';
$args = [];
if ($sf !== '') {
    $w = ' WHERE r.status IN (' . implode(',', array_fill(0, count($filters[$sf]), '?')) . ')';
    $args = $filters[$sf];
}
$cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_stock_requests r $w");
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$page  = min($page, max(1, (int)ceil($total / $per)));
$off   = ($page - 1) * $per;

$list = $pdo->prepare('SELECT r.*, p.product_name, ' . REQ_VARIANT_SQL . " AS variant_label
                       FROM tbl_stock_requests r LEFT JOIN tbl_products p ON p.product_id = r.product_id
                       $w ORDER BY r.request_id DESC LIMIT $per OFFSET $off");
$list->execute($args);
$requests = $list->fetchAll();

function req_details(array $r): string {
    $when = fn(?string $d): string => $d ? date('M d, h:i A', strtotime($d)) : '';
    switch (strtolower($r['status'])) {
        case 'approved':
            return 'Approved' . ($r['approved_at'] ? ' ' . $when($r['approved_at']) : '') . ', waiting to ship';
        case 'shipped':
            return (int)$r['shipped_qty'] . ' pcs shipped' . ($r['reference_number'] ? ', ref ' . $r['reference_number'] : '') . '. Confirm it on Receive Stock.';
        case 'received':
            return (int)($r['received_qty'] ?? $r['shipped_qty']) . ' pcs added to stock' . ($r['received_at'] ? ' on ' . $when($r['received_at']) : '');
        case 'rejected':
            return 'Rejected by the supplier';
        default:
            return 'Waiting for the supplier to review';
    }
}

$title = 'Request a Stock'; $css = 'products'; $active = 'request_stock';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Request a Stock</span>
                        <span>Ask the supplier to restock a product. Once they ship it, confirm it on Receive Stock.</span>
                    </h1>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <form method="post" class="form-card" id="request-form">
                    <?= csrf_field() ?>
                    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
                    <div class="form-grid">
                        <div class="field full">
                            <label for="product_id">Product</label>
                            <select id="product_id" name="product_id" required>
                                <option value="">Select a product</option>
                                <?php foreach ($products as $p): $pid = (int)$p['product_id']; ?>
                                <option value="<?= $pid ?>"
                                        data-stock="<?= (int)$p['stock'] ?>"
                                        data-variant="<?= e(variant_for($p)) ?>"
                                        <?= $in['product_id'] === $pid ? 'selected' : '' ?>><?= e($p['product_name']) ?> (<?= e(variant_for($p)) ?>) - stock: <?= (int)$p['stock'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small id="stock-hint"></small>
                        </div>
                        <div class="field">
                            <label for="qty">Quantity needed</label>
                            <input type="number" id="qty" name="qty" min="1" max="100000" step="1" value="<?= e($in['qty']) ?>" placeholder="e.g. 50" required>
                        </div>
                        <div class="field">
                            <label for="variant">Variant / size <small>(optional)</small></label>
                            <input type="text" id="variant" name="variant" maxlength="255" value="<?= e($in['variant']) ?>" placeholder="e.g. Navy / S x10, M x20">
                        </div>
                        <div class="field full">
                            <label for="notes">Notes for the supplier <small>(optional)</small></label>
                            <textarea id="notes" name="notes" rows="3" maxlength="1000" placeholder="e.g. Needed before enrollment week"><?= e($in['notes']) ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary"><i class="fa fa-paper-plane"></i> Send request</button>
                    </div>
                </form>
            </div>
        </section>

        <section id="restock">
            <div class="max">
                <div class="recent-order">
                    <div class="head"><h1>Running low</h1></div>
                    <div class="table-2">
                        <table>
                            <thead>
                                <tr>
                                    <th class="left-radius">Product</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>In progress</th>
                                    <th class="right-radius">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($lowRows as $r): $pid = (int)$r['product_id']; [$bl, $bc] = stock_badge((int)$r['stock'], $r['status']); ?>
                                <tr>
                                    <td class="color-change tl"><?= e($r['product_name']) ?><small class="sub"><?= e($r['color']) ?></small></td>
                                    <td><?= (int)$r['stock'] ?></td>
                                    <td><div class="status <?= $bc ?>"><?= e($bl) ?></div></td>
                                    <td><?= !empty($open[$pid]) ? (int)$open[$pid] . ' open request' . ($open[$pid] > 1 ? 's' : '') : '—' ?></td>
                                    <td><a href="request_stock.php?product_id=<?= $pid ?>#request-form" class="btn-primary sm">Request stock</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$lowRows): ?>
                                <tr><td colspan="5">Nothing is running low right now.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="recent-order">
                    <div class="head">
                        <h1>Your requests</h1>
                        <form method="get" class="show-entries">
                            <span>Status</span>
                            <select name="status" onchange="this.form.submit()">
                                <option value="">All</option>
                                <?php foreach (array_keys($filters) as $label): ?>
                                <option <?= $sf === $label ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="table-2">
                        <table>
                            <thead>
                                <tr>
                                    <th class="left-radius">Request</th>
                                    <th>Product</th>
                                    <th>Variant / Size</th>
                                    <th>Requested</th>
                                    <th>Status</th>
                                    <th class="right-radius">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $r): [$bl, $bc] = request_badge($r['status']); ?>
                                <tr>
                                    <td class="color-change">#<?= (int)$r['request_id'] ?><small class="sub"><?= e(date('M d, Y h:i A', strtotime($r['created_at']))) ?></small></td>
                                    <td class="tl"><?= e($r['product_name'] ?? 'Removed product') ?><?php if (!empty($r['notes'])): ?><small class="sub"><?= e($r['notes']) ?></small><?php endif; ?></td>
                                    <td class="tl"><?= e($r['variant_label'] ?? '') ?: '—' ?></td>
                                    <td><?= (int)$r['requested_qty'] ?> pcs</td>
                                    <td><div class="status <?= $bc ?>"><?= e($bl) ?></div></td>
                                    <td class="tl"><?= e(req_details($r)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$requests): ?>
                                <tr><td colspan="6">No requests yet. Use the form above to send your first one.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php pager($total, $page, $per, 'requests'); ?>
                </div>
            </div>
        </section>

        <script>
            const sel = document.getElementById('product_id');
            const variant = document.getElementById('variant');
            const hint = document.getElementById('stock-hint');
            let auto = '';
            function syncProduct() {
                const o = sel.options[sel.selectedIndex];
                const has = o && o.value !== '';
                hint.textContent = has ? 'Current stock: ' + o.dataset.stock : '';
                const v = has ? o.dataset.variant : '';
                if (variant.value === '' || variant.value === auto) { variant.value = v; auto = v; }
            }
            sel.addEventListener('change', syncProduct);
            syncProduct();
        </script>
<?php require 'layout_bottom.php'; ?>
