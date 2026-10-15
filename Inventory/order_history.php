<?php
require 'config.php';
require_admin();

const OH_STATUSES = [
    'placed'    => 'Order Placed',
    'paid'      => 'Order Paid',
    'shipped'   => 'Order Shipped Out',
    'received'  => 'Order Received',
    'delivered' => 'Order Delivered',
];
const OH_ICONS = [
    'placed'    => 'fa-cart-shopping',
    'paid'      => 'fa-peso-sign',
    'shipped'   => 'fa-truck',
    'received'  => 'fa-box-open',
    'delivered' => 'fa-circle-check',
];

function oh_badge(string $s): string {
    return '<span class="oh-badge oh-' . e($s) . '">' . e(OH_STATUSES[$s] ?? ucfirst($s)) . '</span>';
}
function oh_payment(string $p): string {
    $map = ['cod' => 'Cash on Delivery', 'gcash' => 'GCash', 'maya' => 'Maya', 'card' => 'Card'];
    return $map[strtolower($p)] ?? ($p === '' ? '—' : ucfirst($p));
}
function oh_date(?string $d): string {
    return $d ? date('M d, Y h:i A', strtotime($d)) : '—';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $id   = (int)($_POST['order_id'] ?? 0);
    $new  = (string)($_POST['status'] ?? '');
    $note = trim((string)($_POST['note'] ?? ''));
    parse_str((string)($_POST['qs'] ?? ''), $back);
    $dest = 'order_history.php' . ($back ? '?' . http_build_query($back) : '');

    if (!isset(OH_STATUSES[$new])) {
        flash('Invalid status.', 'err');
    } elseif (mb_strlen($note) > 255) {
        flash('Note is too long (max 255 characters).', 'err');
    } else {
        try {
            $pdo->beginTransaction();
            $cur = $pdo->prepare('SELECT status FROM tbl_orders WHERE order_id = ? FOR UPDATE');
            $cur->execute([$id]);
            $old = $cur->fetchColumn();
            if ($old === false) {
                $pdo->rollBack();
                flash('Order not found.', 'err');
            } elseif ($old === $new) {
                $pdo->rollBack();
                flash('That order is already "' . OH_STATUSES[$new] . '".', 'err');
            } else {

                $pdo->prepare("UPDATE tbl_orders
                               SET status = ?, confirmed_at = CASE WHEN ? = 'paid' AND confirmed_at IS NULL THEN NOW() ELSE confirmed_at END
                               WHERE order_id = ?")
                    ->execute([$new, $new, $id]);
                $pdo->prepare('INSERT INTO tbl_order_status_history (order_id, status, note, changed_by) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $new, $note !== '' ? $note : 'Status changed to ' . OH_STATUSES[$new], (int)$_SESSION['admin_id']]);
                $pdo->commit();
                flash('Order status updated to "' . OH_STATUSES[$new] . '".');
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('Could not update the order status.', 'err');
        }
    }
    header('Location: ' . $dest);
    exit;
}

$q    = trim((string)($_GET['q'] ?? ''));
$st   = (string)($_GET['status'] ?? '');
if (!isset(OH_STATUSES[$st])) $st = '';
$per  = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = '(o.order_code LIKE ? OR o.fullname LIKE ? OR o.email LIKE ?)';
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($st !== '') { $where[] = 'o.status = ?'; $args[] = $st; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$counts = array_fill_keys(array_keys(OH_STATUSES), 0);
foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM tbl_orders GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];
$allCount = array_sum($counts);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders o $w");
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$page  = min($page, max(1, (int)ceil($total / $per)));
$off   = ($page - 1) * $per;

$list = $pdo->prepare("SELECT o.* FROM tbl_orders o $w ORDER BY o.created_at DESC, o.order_id DESC LIMIT $per OFFSET $off");
$list->execute($args);
$orders = $list->fetchAll();

$items = $hist = [];
if ($orders) {
    $ids = array_column($orders, 'order_id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $s = $pdo->prepare("SELECT * FROM tbl_order_items WHERE order_id IN ($in) ORDER BY item_id");
    $s->execute($ids);
    foreach ($s as $r) $items[$r['order_id']][] = $r;
    $s = $pdo->prepare("SELECT * FROM tbl_order_status_history WHERE order_id IN ($in) ORDER BY created_at, history_id");
    $s->execute($ids);
    foreach ($s as $r) $hist[$r['order_id']][] = $r;
}

function oh_tab_url(string $q, string $status): string {
    $qs = array_filter(['q' => $q, 'status' => $status], fn($v) => $v !== '');
    return 'order_history.php' . ($qs ? '?' . http_build_query($qs) : '');
}

$title = 'Order History'; $css = 'dashboard'; $extra_css = 'order_history'; $active = 'order_history';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Order History</span>
                        <span>See every customer order and control its tracking status.</span>
                    </h1>
                    <div class="date"><i class="fa fa-receipt"></i> <?= number_format($allCount) ?> total orders</div>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="oh-toolbar">
                    <div class="oh-tabs">
                        <a href="<?= e(oh_tab_url($q, '')) ?>" class="oh-tab<?= $st === '' ? ' active-tab' : '' ?>">All <span><?= $allCount ?></span></a>
                        <?php foreach (OH_STATUSES as $k => $label): ?>
                        <a href="<?= e(oh_tab_url($q, $k)) ?>" class="oh-tab<?= $st === $k ? ' active-tab' : '' ?>"><?= e($label) ?> <span><?= $counts[$k] ?></span></a>
                        <?php endforeach; ?>
                    </div>
                    <form method="get" class="oh-search">
                        <?php if ($st !== ''): ?><input type="hidden" name="status" value="<?= e($st) ?>"><?php endif; ?>
                        <div class="search-input">
                            <i class="fa fa-magnifying-glass"></i>
                            <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search order code, name or email... (Enter)">
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="recent-order">
                    <div class="head"><h1>Orders</h1></div>
                    <div class="table-2">
                        <table>
                            <thead>
                                <tr>
                                    <th class="left-radius">Order Code</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date Placed</th>
                                    <th class="right-radius">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orders as $o):
                                $oid = (int)$o['order_id'];
                                $its = $items[$oid] ?? [];
                                $units = array_sum(array_column($its, 'quantity'));
                            ?>
                                <tr>
                                    <td class="color-change"><?= e($o['order_code'] ?? '#' . $oid) ?></td>
                                    <td class="oh-left"><?= e($o['fullname']) ?><br><small class="oh-sub"><?= e($o['email']) ?></small></td>
                                    <td class="oh-left">
                                        <?php if ($its): ?>
                                            <?= e($its[0]['product_name']) ?> &times;<?= (int)$its[0]['quantity'] ?>
                                            <?php if (count($its) > 1): ?><br><small class="oh-sub">+<?= count($its) - 1 ?> more &middot; <?= (int)$units ?> units</small><?php endif; ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?= peso($o['total']) ?></td>
                                    <td><?= e(oh_payment((string)$o['payment'])) ?></td>
                                    <td><?= oh_badge($o['status']) ?></td>
                                    <td><?= e(oh_date($o['created_at'])) ?></td>
                                    <td>
                                        <button type="button" class="oh-btn" data-open="oh-<?= $oid ?>"><i class="fa fa-location-dot"></i> Track</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$orders): ?>
                                <tr><td colspan="8">No orders found.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php pager($total, $page, $per, 'orders'); ?>
                </div>
            </div>
        </section>

        <?php foreach ($orders as $o):
            $oid     = (int)$o['order_id'];
            $its     = $items[$oid] ?? [];
            $keys    = array_keys(OH_STATUSES);
            $reached = (int)array_search($o['status'], $keys, true);
            $when    = [];
            foreach ($hist[$oid] ?? [] as $h) $when[$h['status']] = $h['created_at'];
            if (!isset($when['placed'])) $when['placed'] = $o['created_at'];
        ?>
        <dialog class="oh-modal" id="oh-<?= $oid ?>">
            <div class="oh-modal-body">
                <div class="oh-modal-head">
                    <div>
                        <h2><?= e($o['order_code'] ?? '#' . $oid) ?></h2>
                        <small class="oh-sub">Placed <?= e(oh_date($o['created_at'])) ?></small>
                    </div>
                    <div class="oh-modal-head-right">
                        <?= oh_badge($o['status']) ?>
                        <button type="button" class="oh-close" data-close aria-label="Close"><i class="fa fa-xmark"></i></button>
                    </div>
                </div>

                <h3 class="oh-h3">Tracking</h3>
                <div class="oh-steps-wrap">
                    <ol class="oh-steps">
                        <?php foreach ($keys as $i => $k): ?>
                        <li class="oh-step<?= $i <= $reached ? ' done' : '' ?><?= $i === $reached ? ' current' : '' ?>">
                            <span class="oh-dot"><i class="fa <?= e(OH_ICONS[$k]) ?>"></i></span>
                            <b><?= e(OH_STATUSES[$k]) ?></b>
                            <small><?= isset($when[$k]) && $i <= $reached ? e(date('M d, h:i A', strtotime($when[$k]))) : '&nbsp;' ?></small>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <form method="post" class="oh-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $oid ?>">
                    <input type="hidden" name="qs" value="<?= e($_SERVER['QUERY_STRING'] ?? '') ?>">
                    <div class="oh-form-row">
                        <label>
                            <span>Update status</span>
                            <select name="status">
                                <?php foreach (OH_STATUSES as $k => $label): ?>
                                <option value="<?= e($k) ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="oh-grow">
                            <span>Note <small>(optional, shown in the history)</small></span>
                            <input type="text" name="note" maxlength="255" placeholder="e.g. Handed to courier, ref #12345">
                        </label>
                        <button type="submit" class="oh-save">Save</button>
                    </div>
                </form>

                <div class="oh-cols">
                    <div>
                        <h3 class="oh-h3">Customer &amp; Shipping</h3>
                        <p class="oh-info">
                            <b><?= e($o['fullname']) ?></b><br>
                            <?= e($o['email']) ?><br>
                            <?= e($o['contact']) ?><br>
                            <?= e($o['address']) ?>, <?= e($o['city']) ?>, <?= e($o['province']) ?> <?= e($o['postal']) ?><br>
                            Payment: <?= e(oh_payment((string)$o['payment'])) ?>
                            <?php if ($o['confirmed_at']): ?><br>Confirmed: <?= e(oh_date($o['confirmed_at'])) ?><?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <h3 class="oh-h3">Status History</h3>
                        <ul class="oh-log">
                            <?php foreach (array_reverse($hist[$oid] ?? []) as $h): ?>
                            <li>
                                <?= oh_badge($h['status']) ?>
                                <span><?= e($h['note'] ?? '') ?></span>
                                <small><?= e(oh_date($h['created_at'])) ?></small>
                            </li>
                            <?php endforeach; ?>
                            <?php if (empty($hist[$oid])): ?><li><span>No tracking activity yet.</span></li><?php endif; ?>
                        </ul>
                    </div>
                </div>

                <h3 class="oh-h3">Items</h3>
                <div class="oh-items">
                    <table>
                        <thead><tr><th>Product</th><th>Size / Color</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($its as $it): ?>
                            <tr>
                                <td><?= e($it['product_name']) ?></td>
                                <td><?= e(trim($it['size'] . ' / ' . $it['color'], ' /') ?: '—') ?></td>
                                <td><?= (int)$it['quantity'] ?></td>
                                <td><?= peso($it['unit_price']) ?></td>
                                <td><?= peso($it['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$its): ?><tr><td colspan="5">No items recorded for this order.</td></tr><?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4">Subtotal</td><td><?= peso($o['subtotal']) ?></td></tr>
                            <tr><td colspan="4">Shipping</td><td><?= peso($o['shipping_fee']) ?></td></tr>
                            <tr class="oh-total"><td colspan="4">Total</td><td><?= peso($o['total']) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </dialog>
        <?php endforeach; ?>

        <script>
            document.querySelectorAll('[data-open]').forEach(btn =>
                btn.addEventListener('click', () => document.getElementById(btn.dataset.open).showModal()));
            document.querySelectorAll('.oh-modal').forEach(dlg => {
                dlg.addEventListener('click', ev => { if (ev.target === dlg) dlg.close(); });
                dlg.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => dlg.close()));
            });
        </script>
<?php require 'layout_bottom.php'; ?>