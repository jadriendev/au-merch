<?php
require 'config.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM tbl_products WHERE product_id = ?');
$st->execute([$id]);
$prod = $st->fetch();
if (!$prod) { flash('Product not found.', 'err'); header('Location: products.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $type = ($_POST['type'] ?? '') === 'out' ? 'out' : 'in';
    $qty  = (int)($_POST['qty'] ?? 0);
    if ($qty < 1 || $qty > 100000) {
        $error = 'Enter a quantity of at least 1.';
    } else {
        try {
            $pdo->beginTransaction();
            $lock = $pdo->prepare('SELECT stock FROM tbl_products WHERE product_id = ? FOR UPDATE');
            $lock->execute([$id]);
            $prev = (int)$lock->fetchColumn();
            $new  = $type === 'in' ? $prev + $qty : $prev - $qty;
            if ($new < 0) {
                $pdo->rollBack();
                $error = "Not enough stock. Only $prev available.";
            } else {
                $pdo->prepare('UPDATE tbl_products SET stock = ? WHERE product_id = ?')->execute([$new, $id]);
                log_action($pdo, $id, $type === 'in' ? 'Stock In' : 'Stock Out', $qty, $prev, $new);
                $pdo->commit();
                flash(($type === 'in' ? 'Added ' : 'Removed ') . "$qty unit(s). New stock: $new.");
                header('Location: products.php');
                exit;
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Could not update stock. Please try again.';
        }
    }
}

$title = 'Stock In / Out'; $css = 'products'; $active = 'products';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet"><h1><span>Stock In / Out</span><span><?= e($prod['product_name']) ?> — current stock: <b><?= (int)$prod['stock'] ?></b></span></h1></div>
            </div>
        </section>
        <section>
            <div class="max">
                <form method="post" class="form-card narrow">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <?php if ($error): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="type">Action</label>
                            <select id="type" name="type">
                                <option value="in">Stock In (add units)</option>
                                <option value="out" <?= ($_POST['type'] ?? '') === 'out' ? 'selected' : '' ?>>Stock Out (remove units)</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="qty">Quantity</label>
                            <input type="number" id="qty" name="qty" min="1" step="1" value="<?= e($_POST['qty'] ?? 1) ?>" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <a href="products.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </section>
<?php require 'layout_bottom.php'; ?>
