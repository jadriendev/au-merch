<?php
require 'config.php';
require_admin();

if (!flow_ready($pdo)) {
    flash('Run inventory_migration.sql in phpMyAdmin first, then open Receive Stock again.', 'err');
    header('Location: dashboard.php');
    exit;
}

/* ---------- Confirm a delivery: add the received units to stock ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $rid = (int)($_POST['request_id'] ?? 0);
    $qty = filter_var($_POST['received_qty'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);

    if ($rid < 1 || $qty === false) {
        flash('Enter the quantity you actually received (a whole number, at least 1).', 'err');
    } else {
        try {
            $pdo->beginTransaction();

            $q = $pdo->prepare('SELECT product_id, status, shipped_qty, reference_number, unit_price
                                FROM tbl_stock_requests WHERE request_id = ? FOR UPDATE');
            $q->execute([$rid]);
            $req = $q->fetch();
            if (!$req || strtolower($req['status']) !== 'shipped') {
                throw new RuntimeException('That shipment was already received or is no longer waiting.');
            }
            $pid = (int)$req['product_id'];

            $s = $pdo->prepare('SELECT stock FROM tbl_products WHERE product_id = ? FOR UPDATE');
            $s->execute([$pid]);
            $prev = $s->fetchColumn();
            if ($prev === false) {
                throw new RuntimeException('The product for this request no longer exists, so the stock cannot be added.');
            }
            $prev = (int)$prev;
            $new  = $prev + $qty;

            $pdo->prepare('UPDATE tbl_products SET stock = ? WHERE product_id = ?')->execute([$new, $pid]);
            $pdo->prepare("UPDATE tbl_stock_requests SET status = 'Received', received_qty = ?, received_at = NOW() WHERE request_id = ?")
                ->execute([$qty, $rid]);

            log_action($pdo, $pid, 'Stock In', $qty, $prev, $new);

            $shipped = (int)$req['shipped_qty'];
            $note    = 'Received by ' . ($_SESSION['username'] ?? 'admin') . ($shipped !== $qty ? ". Shipped $shipped, received $qty." : '.');
            $price   = ($req['unit_price'] !== null && $req['unit_price'] !== '') ? (string)$req['unit_price'] : null;
            log_supplier($pdo, $rid, $pid, 'Stock Received', $qty, $price, $req['reference_number'] ?: null, $note, 'Received');

            $pdo->commit();
            flash("Request #$rid received. Added $qty unit(s). New stock: $new." . ($shipped !== $qty ? " (Supplier shipped $shipped.)" : ''));
        } catch (RuntimeException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash($ex->getMessage(), 'err');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('Could not receive the stock. Please try again.', 'err');
        }
    }
    header('Location: receive_stock.php');
    exit;
}

/* ---------- Data ---------- */
$base = 'SELECT r.request_id, r.requested_qty, r.shipped_qty, r.received_qty, r.reference_number, r.unit_price,
                r.supplier_notes, r.shipped_at, r.received_at, p.product_name, ' . REQ_VARIANT_SQL . ' AS variant_label
         FROM tbl_stock_requests r LEFT JOIN tbl_products p ON p.product_id = r.product_id';

$ready  = $pdo->query($base . " WHERE r.status = 'Shipped' ORDER BY r.shipped_at ASC, r.request_id ASC")->fetchAll();
$recent = $pdo->query($base . " WHERE r.status = 'Received' ORDER BY r.received_at DESC, r.request_id DESC LIMIT 10")->fetchAll();
$inProgress = (int)$pdo->query("SELECT COUNT(*) FROM tbl_stock_requests WHERE status IN ('Submitted','Pending','Approved')")->fetchColumn();

$fmt = fn(?string $d): string => $d ? date('M d, Y h:i A', strtotime($d)) : '—';

$title = 'Receive Stock'; $css = 'products'; $active = 'receive_stock';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Receive Stock</span>
                        <span>Check what the supplier delivered, then confirm it to add the units to your inventory.
                            <?= $inProgress ? $inProgress . ' request' . ($inProgress > 1 ? 's are' : ' is') . ' still with the supplier.' : '' ?></span>
                    </h1>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="recent-order">
                    <div class="head"><h1>Ready to receive</h1></div>
                    <div class="table-2">
                        <table>
                            <thead>
                                <tr>
                                    <th class="left-radius">Request</th>
                                    <th>Product</th>
                                    <th>Requested</th>
                                    <th>Shipped</th>
                                    <th>Reference</th>
                                    <th>Shipped on</th>
                                    <th class="right-radius">Received qty</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ready as $r): $shipped = (int)($r['shipped_qty'] ?? $r['requested_qty']); ?>
                                <tr>
                                    <td class="color-change">#<?= (int)$r['request_id'] ?></td>
                                    <td class="tl"><?= e($r['product_name'] ?? 'Removed product') ?><small class="sub"><?= e($r['variant_label'] ?? '') ?></small><?php if (!empty($r['supplier_notes'])): ?><small class="sub">Supplier note: <?= e($r['supplier_notes']) ?></small><?php endif; ?></td>
                                    <td><?= (int)$r['requested_qty'] ?> pcs</td>
                                    <td><?= $shipped ?> pcs</td>
                                    <td><?= e($r['reference_number'] ?: '—') ?><?php if ($r['unit_price'] !== null && $r['unit_price'] !== ''): ?><small class="sub"><?= peso($r['unit_price']) ?> each</small><?php endif; ?></td>
                                    <td><?= e($fmt($r['shipped_at'])) ?></td>
                                    <td>
                                        <?php if ($r['product_name'] === null): ?>
                                            <small class="sub">Product was removed</small>
                                        <?php else: ?>
                                        <form method="post" class="receive-form" onsubmit="return confirm('Add ' + this.received_qty.value + ' unit(s) to stock?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="request_id" value="<?= (int)$r['request_id'] ?>">
                                            <input type="number" name="received_qty" min="1" max="1000000" step="1" value="<?= $shipped > 0 ? $shipped : 1 ?>" aria-label="Quantity received" required>
                                            <button type="submit" class="btn-primary sm">Confirm</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ready): ?>
                                <tr><td colspan="7">No shipments waiting. When the supplier ships a request, it appears here.</td></tr>
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
                    <div class="head"><h1>Recently received</h1></div>
                    <div class="table-2">
                        <table>
                            <thead>
                                <tr>
                                    <th class="left-radius">Request</th>
                                    <th>Product</th>
                                    <th>Shipped</th>
                                    <th>Received</th>
                                    <th>Reference</th>
                                    <th class="right-radius">Received on</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recent as $r): ?>
                                <tr>
                                    <td class="color-change">#<?= (int)$r['request_id'] ?></td>
                                    <td class="tl"><?= e($r['product_name'] ?? 'Removed product') ?><small class="sub"><?= e($r['variant_label'] ?? '') ?></small></td>
                                    <td><?= (int)$r['shipped_qty'] ?> pcs</td>
                                    <td><?= (int)$r['received_qty'] ?> pcs</td>
                                    <td><?= e($r['reference_number'] ?: '—') ?></td>
                                    <td><?= e($fmt($r['received_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$recent): ?>
                                <tr><td colspan="6">Nothing received yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
<?php require 'layout_bottom.php'; ?>
