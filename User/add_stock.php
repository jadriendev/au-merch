<?php
require_once __DIR__ . '/portal_helpers.php';
$products = portal_products($conn);
$groupedProducts = [];
foreach ($products as $product) {
    $groupedProducts[$product['product_name']][] = [
        'id' => (int) $product['product_id'],
        'variant' => $product['variant_label'] !== '' ? $product['variant_label'] : 'Standard',
    ];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf()) {
        portal_flash('Your session expired. Please try again.', 'error');
        header('Location: add_stock.php');
        exit;
    }
    $productName = trim((string) ($_POST['product_name'] ?? ''));
    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
    $priceInput = trim((string) ($_POST['unit_price'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $validProduct = $productId !== false && isset($products[$productId]) && $products[$productId]['product_name'] === $productName;
    $validPrice = $priceInput === '' || (is_numeric($priceInput) && (float) $priceInput >= 0 && (float) $priceInput <= 99999999.99);
    if (!$validProduct || $quantity === false || !$validPrice) {
        portal_flash('Please check the product, variant, quantity, and unit price.', 'error');
    } else {
        $unitPrice = $priceInput === '' ? null : number_format((float) $priceInput, 2, '.', '');
        $notes = substr($notes, 0, 2000);
        $status = 'Submitted';
        $userId = (int) portal_supplier_id();
        $stmt = $conn->prepare('INSERT INTO tbl_stock_requests (product_id, requested_qty, status, requested_by, variant, unit_price, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $variantLabel = $products[$productId]['variant_label'];
        $stmt->bind_param('iisisss', $productId, $quantity, $status, $userId, $variantLabel, $unitPrice, $notes);
        $stmt->execute();
        $stmt->close();
        portal_flash('Stock request submitted to admin for review.');
        header('Location: stock_history.php');
        exit;
    }
    header('Location: add_stock.php');
    exit;
}
$variantMap = $groupedProducts;
portal_header('add-stock', 'Add Stock', 'Fill in the details below to add new stock. It will be sent directly to the admin for review.');
?>
<section class="form-card card">
    <h2>Product Details</h2>
    <form method="post" action="add_stock.php">
        <input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>">
        <div class="form-grid">
            <div class="field"><label for="product_name">Product</label><select id="product_name" name="product_name" required><option value="">Select product</option><?php foreach (array_keys($groupedProducts) as $name): ?><option value="<?php echo portal_escape($name); ?>"><?php echo portal_escape($name); ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="product_id">Variant / Size</label><select id="product_id" name="product_id" required disabled><option value="">Select product first</option></select></div>
            <div class="field"><label for="quantity">Quantity</label><input id="quantity" name="quantity" type="number" min="1" max="1000000" step="1" placeholder="Enter quantity" required></div>
            <div class="field"><label for="unit_price">Unit Price (₱)</label><input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" placeholder="Optional (for reference)"></div>
        </div>
        <h3 class="form-section-title">Additional Information</h3>
        <div class="field"><label for="notes">Notes (optional)</label><textarea id="notes" name="notes" maxlength="2000" placeholder="e.g. new batch, special notes, etc."></textarea></div>
        <div class="form-actions"><button class="btn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send to Admin</button></div>
    </form>
</section>
<script>
const variantMap = <?php echo json_encode($variantMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
const productSelect = document.getElementById('product_name');
const variantSelect = document.getElementById('product_id');
productSelect.addEventListener('change', () => {
    const variants = variantMap[productSelect.value] || [];
    variantSelect.replaceChildren(new Option(variants.length ? 'Select variant' : 'Select product first', ''));
    variantSelect.disabled = variants.length === 0;
    variants.forEach((variant) => variantSelect.add(new Option(variant.variant, variant.id)));
});
</script>
<?php portal_footer(); ?>
