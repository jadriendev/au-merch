<?php
require 'config.php';
require_admin();
require 'product_form.php';

$cats = $pdo->query('SELECT category_id, category_name FROM tbl_categories ORDER BY category_name')->fetchAll();
$p = ['product_name' => '', 'category_id' => 0, 'new_category' => '', 'color' => '', 'variation' => '', 'mini_desc' => '',
      'description' => '', 'price' => '', 'stock' => 0, 'status' => 'Available', 'image' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    [$errors, $p] = validate_product($pdo, $_POST, false);
    $img = $errors ? null : save_image($errors);
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $cid = resolve_category($pdo, $p);
            $pdo->prepare('INSERT INTO tbl_products (category_id, product_name, color, variation, description, mini_desc, price, stock, image, status)
                           VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$cid, $p['product_name'], $p['color'], $p['variation'], $p['description'], $p['mini_desc'],
                           number_format((float)$p['price'], 2, '.', ''), $p['stock'], $img ?? '', $p['status']]);
            log_action($pdo, (int)$pdo->lastInsertId(), 'Product Added', $p['stock'], 0, $p['stock']);
            $pdo->commit();
            flash('Product added successfully.');
            header('Location: products.php');
            exit;
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Could not save the product. Please try again.';
        }
    }
}

$title = 'Add Product'; $css = 'products'; $active = 'products';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet"><h1><span>Add Product</span><span>Create a new product listing.</span></h1></div>
            </div>
        </section>
        <section><div class="max"><?php render_product_form($p, $cats, $errors, false); ?></div></section>
<?php require 'layout_bottom.php'; ?>
