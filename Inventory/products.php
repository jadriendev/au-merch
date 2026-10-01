<?php
require 'config.php';
require_admin();

$q    = trim((string)($_GET['q'] ?? ''));
$cat  = (int)($_GET['cat'] ?? 0);
$st   = (string)($_GET['status'] ?? '');
$per  = (int)($_GET['entries'] ?? 10);
if (!in_array($per, [10, 25, 50, 100], true)) $per = 10;
$page = max(1, (int)($_GET['page'] ?? 1));

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = '(p.product_name LIKE ? OR p.color LIKE ?)';
    $args[]  = "%$q%";
    $args[]  = "%$q%";
}
if ($cat > 0) { $where[] = 'p.category_id = ?'; $args[] = $cat; }
$L = LOW_STOCK;
$where[] = match ($st) {
    'Available'    => "(p.status = 'Available' AND p.stock > $L)",
    'Low Stock'    => "(p.status = 'Available' AND p.stock BETWEEN 1 AND $L)",
    'Out of Stock' => "(p.status = 'Available' AND p.stock <= 0)",
    'Unavailable'  => "(p.status <> 'Available')",
    default        => '1=1',
};
$w = 'WHERE ' . implode(' AND ', $where);

$cnt = $pdo->prepare("SELECT COUNT(*) FROM tbl_products p $w");
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$page  = min($page, max(1, (int)ceil($total / $per)));
$off   = ($page - 1) * $per;

$list = $pdo->prepare("SELECT p.*, COALESCE(c.category_name, 'Uncategorized') AS category_name
                       FROM tbl_products p LEFT JOIN tbl_categories c ON c.category_id = p.category_id
                       $w ORDER BY p.product_id DESC LIMIT $per OFFSET $off");
$list->execute($args);
$rows = $list->fetchAll();

$cats = $pdo->query('SELECT category_id, category_name FROM tbl_categories ORDER BY category_name')->fetchAll();
$s = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(stock),0) AS units,
                         COALESCE(SUM(status = 'Available' AND stock BETWEEN 1 AND $L),0) AS low,
                         COALESCE(SUM(stock <= 0),0) AS out_of_stock FROM tbl_products")->fetch();

$title  = 'Products';
$css    = 'products';
$active = 'products';
require 'layout_top.php';
?>
        <section class="top">
            <div class="max">
                <div class="greet">
                    <h1>
                        <span>Products</span>
                        <span>Manage all products in your store. You can add, edit, restock, or remove products here.</span>
                    </h1>
                    <a href="add_product.php" class="add-products"><i class="fa fa-plus"></i> Add Product</a>
                </div>
            </div>
        </section>

        <section>
            <div class="max">
                <form method="get" class="input-fields" id="filters">
                    <div class="input">
                        <i class="fa fa-magnifying-glass"></i>
                        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search products... (press Enter)">
                    </div>
                    <div class="categories">
                        <select name="cat" onchange="this.form.submit()">
                            <option value="0">All Categories</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="status-filter">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <?php foreach (['Available', 'Low Stock', 'Out of Stock', 'Unavailable'] as $o): ?>
                            <option <?= $st === $o ? 'selected' : '' ?>><?= e($o) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="clear">
                        <button type="button" onclick="location.href='products.php'"><i class="fa fa-filter"></i> Clear Filters</button>
                    </div>
                </form>
            </div>
        </section>

        <section>
            <div class="max">
                <div class="grid">
                    <?php foreach ([
                        ['fa fa-box', 'Total Products', $s['total'], 'in the catalog'],
                        ['fa fa-boxes-stacked', 'Total Units', $s['units'], 'items in stock'],
                        ['fa fa-triangle-exclamation', 'Low Stock', $s['low'], "stock of $L or less"],
                        ['fa fa-ban', 'Out of Stock', $s['out_of_stock'], 'need restocking'],
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
                            <h1>Product List</h1>
                            <div class="show-entries">
                                <span>Show</span>
                                <select name="entries" form="filters" onchange="this.form.submit()">
                                    <?php foreach ([10, 25, 50, 100] as $n): ?>
                                    <option value="<?= $n ?>" <?= $per === $n ? 'selected' : '' ?>><?= $n ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span>entries</span>
                            </div>
                        </div>

                        <div class="table-2">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="left-radius">Image</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th class="right-radius">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $r): [$label, $cls] = stock_badge((int)$r['stock'], $r['status']); ?>
                                    <tr>
                                        <td class="order-2"><img src="../images/<?= e($r['image']) ?>" alt="" onerror="this.style.visibility='hidden'"></td>
                                        <td class="color-change"><?= e($r['product_name']) ?><br><small class="sub"><?= e($r['color']) ?></small></td>
                                        <td><?= e($r['category_name']) ?></td>
                                        <td><?= peso($r['price']) ?></td>
                                        <td><?= (int)$r['stock'] ?></td>
                                        <td><div class="status <?= $cls ?>"><?= e($label) ?></div></td>
                                        <td>
                                            <div class="buttons">
                                                <a href="edit.php?id=<?= (int)$r['product_id'] ?>" class="btn btn-edit">Edit</a>
                                                <a href="stock.php?id=<?= (int)$r['product_id'] ?>" class="btn btn-stock">Stock</a>
                                                <form method="post" action="delete.php" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= (int)$r['product_id'] ?>">
                                                    <button type="submit" class="btn btn-del">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$rows): ?>
                                    <tr><td colspan="7">No products found.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php pager($total, $page, $per, 'products'); ?>
                    </div>
                </div>
            </div>
        </section>
<?php require 'layout_bottom.php'; ?>
