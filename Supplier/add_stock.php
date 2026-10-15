<?php
require_once __DIR__ . '/portal_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf()) {
        portal_flash('Your session expired. Please try again.', 'error');
        header('Location: add_stock.php');
        exit;
    }
    $requestId = filter_var($_POST['request_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
    $reference = trim((string) ($_POST['reference_number'] ?? ''));
    $priceInput = trim((string) ($_POST['unit_price'] ?? ''));
    $notes = substr(trim((string) ($_POST['notes'] ?? '')), 0, 2000);
    $validPrice = $priceInput === '' || (is_numeric($priceInput) && (float) $priceInput >= 0 && (float) $priceInput <= 99999999.99);

    if ($requestId === false || $quantity === false || $reference === '' || strlen($reference) > 100 || !$validPrice) {
        portal_flash('Check the request, quantity, reference number, and unit price.', 'error');
        header('Location: add_stock.php' . ($requestId ? '?request_id=' . $requestId : ''));
        exit;
    }
    $unitPrice = $priceInput === '' ? null : number_format((float) $priceInput, 2, '.', '');
    $by = 'supplier#' . portal_supplier_id();
    $shipped = 'Shipped';

    try {
        $conn->begin_transaction();
        $lock = $conn->prepare('SELECT product_id, status FROM tbl_stock_requests WHERE request_id = ? FOR UPDATE');
        $lock->bind_param('i', $requestId);
        $lock->execute();
        $req = $lock->get_result()->fetch_assoc();
        $lock->close();
        if (!$req || strtolower($req['status']) !== 'approved') {
            throw new RuntimeException('This request is no longer waiting to be shipped.');
        }
        $productId = (int) $req['product_id'];

        $up = $conn->prepare("UPDATE tbl_stock_requests SET status = 'Shipped', shipped_qty = ?, reference_number = ?, unit_price = ?, supplier_notes = ?, shipped_at = NOW() WHERE request_id = ?");
        $up->bind_param('isssi', $quantity, $reference, $unitPrice, $notes, $requestId);
        $up->execute();
        $up->close();

        $action = 'Stock Shipped';
        $log = $conn->prepare('INSERT INTO tbl_supplier_logs (request_id, product_id, action, performed_by, quantity, unit_price, reference_number, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $log->bind_param('iississss', $requestId, $productId, $action, $by, $quantity, $unitPrice, $reference, $notes, $shipped);
        $log->execute();
        $log->close();

        $conn->commit();
        portal_flash('Stock sent to admin. They will confirm once it is received.');
        header('Location: stock_history.php');
        exit;
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        portal_flash($e instanceof RuntimeException ? $e->getMessage() : 'Could not send the stock. Please try again.', 'error');
        header('Location: add_stock.php');
        exit;
    }
}

$approved = $conn->query("SELECT r.request_id, r.requested_qty, r.notes, r.created_at, p.product_name, " . PORTAL_VARIANT_SQL . " AS variant
    FROM tbl_stock_requests r LEFT JOIN tbl_products p ON p.product_id = r.product_id
    WHERE r.status = 'Approved' ORDER BY r.created_at ASC, r.request_id ASC")->fetch_all(MYSQLI_ASSOC);

$map = [];
foreach ($approved as $a) {
    $map[(int) $a['request_id']] = [
        'product' => (string) $a['product_name'],
        'variant' => (string) $a['variant'],
        'qty' => (int) $a['requested_qty'],
        'notes' => (string) $a['notes'],
    ];
}
$selected = (int) ($_GET['request_id'] ?? 0);
if (!isset($map[$selected])) {
    $selected = 0;
}

portal_header('add-stock', 'Add Stock', 'Send stock for an approved request. The admin will confirm when it arrives.');
?>
<div class="center-page">
<?php if (!$approved): ?>
    <section class="card empty-state">
        <i class="fa-solid fa-inbox"></i>
        No approved requests to ship yet.<br>
        <a class="btn small" style="margin-top:14px" href="requests.php">Go to requests</a>
    </section>
<?php else: ?>
    <section class="form-card card">
        <h2>Shipment Details</h2>
        <form method="post" action="add_stock.php">
            <input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>">
            <div class="form-grid">
                <div class="field full"><label for="request_id">Approved request</label>
                    <select id="request_id" name="request_id" required>
                        <option value="">Select a request</option>
                        <?php foreach ($approved as $a): ?>
                            <option value="<?php echo (int) $a['request_id']; ?>" <?php echo $selected === (int) $a['request_id'] ? 'selected' : ''; ?>>#<?php echo (int) $a['request_id']; ?> — <?php echo portal_escape($a['product_name'] . ' (' . $a['variant'] . ')'); ?> — <?php echo (int) $a['requested_qty']; ?> pcs</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field full"><div class="request-summary" id="summary" hidden></div></div>
                <div class="field"><label for="quantity">Quantity to send</label><input id="quantity" name="quantity" type="number" min="1" max="1000000" step="1" placeholder="Enter quantity" required></div>
                <div class="field"><label for="reference_number">Reference / delivery no.</label><input id="reference_number" name="reference_number" maxlength="100" placeholder="e.g. DR-2026-0015" required></div>
                <div class="field"><label for="unit_price">Unit price (₱)</label><input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" placeholder="Optional"></div>
                <div class="field full"><label for="notes">Notes (optional)</label><textarea id="notes" name="notes" maxlength="2000" placeholder="e.g. delivered by courier, batch details"></textarea></div>
            </div>
            <div class="form-actions"><button class="btn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send to Admin</button></div>
        </form>
    </section>
<?php endif; ?>
</div>
<script>
const requests = <?php echo json_encode($map, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
const sel = document.getElementById('request_id');
const qty = document.getElementById('quantity');
const box = document.getElementById('summary');
function showRequest() {
    if (!sel) return;
    const r = requests[sel.value];
    if (!r) { box.hidden = true; return; }
    box.hidden = false;
    box.textContent = '';
    const s = document.createElement('strong');
    s.textContent = r.product + ' (' + r.variant + ')';
    box.append(s, ' — admin requested ' + r.qty + ' pcs' + (r.notes ? '. Note: ' + r.notes : '.'));
    qty.value = r.qty;
}
if (sel) { sel.addEventListener('change', showRequest); showRequest(); }
</script>
<?php portal_footer(); ?>
