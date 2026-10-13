<?php
require 'config.php';
require_admin();
require 'product_form.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM tbl_products WHERE product_id = ?');
$st->execute([$id]);
$orig = $st->fetch();
if (!$orig) { flash('Product not found.', 'err'); header('Location: products.php'); exit; }

$cats = $pdo->query('SELECT category_id, category_name FROM tbl_categories ORDER BY category_name')->fetchAll();
$p = $orig + ['new_category' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    [$errors, $p] = validate_product($pdo, $_POST, true);
    $p['product_id'] = $id; $p['stock'] = (int)$orig['stock']; $p['image'] = $orig['image'];
    $img = $errors ? null : save_image($errors);
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $cid = resolve_category($pdo, $p);
            $pdo->prepare('UPDATE tbl_products SET category_id=?, product_name=?, color=?, variation=?, description=?, mini_desc=?, price=?, image=?, status=?
                           WHERE product_id=?')
                ->execute([$cid, $p['product_name'], $p['color'], $p['variation'], $p['description'], $p['mini_desc'],
                           number_format((float)$p['price'], 2, '.', ''), $img ?? $orig['image'], $p['status'], $id]);
            log_action($pdo, $id, 'Product Edited', null, (int)$orig['stock'], (int)$orig['stock']);
            $pdo->commit();
            flash('Product updated.');
            header('Location: products.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not update the product. Please try again.';
        }
    }
}

$title = 'Edit Product'; $css = 'products'; $active = 'products';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet"><h1><span>Edit Product</span><span>Update the details of <?= e($orig['product_name']) ?>.</span></h1></div>
            </div>
        </section>
        <section><div class="max"><?php render_product_form($p, $cats, $errors, true); ?></div></section>
<?php require 'layout_bottom.php'; ?>
