<?php
require 'config.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: products.php'); exit; }
check_csrf();

$id = (int)($_POST['id'] ?? 0);
try {
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT stock FROM tbl_products WHERE product_id = ? FOR UPDATE');
    $s->execute([$id]);
    $stock = $s->fetchColumn();
    if ($stock === false) {
        $pdo->rollBack();
        flash('Product not found.', 'err');
    } else {
        $pdo->prepare('DELETE FROM tbl_products WHERE product_id = ?')->execute([$id]);
        log_action($pdo, $id, 'Product Deleted', (int)$stock, (int)$stock, 0);
        $pdo->commit();
        flash('Product deleted.');
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('Could not delete the product.', 'err');
}
header('Location: products.php');
exit;
