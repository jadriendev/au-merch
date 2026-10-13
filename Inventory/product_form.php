<?php
/** Validate product form input. Returns [errors, cleaned values]. */
function validate_product(PDO $pdo, array $in, bool $edit): array {
    $err = [];
    $t = fn(string $k): string => trim((string)($in[$k] ?? ''));
    $p = [
        'product_name' => $t('product_name'),
        'category_id'  => (int)($in['category_id'] ?? 0),
        'new_category' => $t('new_category'),
        'color'        => $t('color'),
        'variation'    => $t('variation'),
        'mini_desc'    => $t('mini_desc'),
        'description'  => $t('description'),
        'price'        => $t('price'),
        'stock'        => $edit ? 0 : (int)($in['stock'] ?? 0),
        'status'       => ($in['status'] ?? '') === 'Unavailable' ? 'Unavailable' : 'Available',
    ];
    if ($p['product_name'] === '' || mb_strlen($p['product_name']) > 55) $err[] = 'Product name is required (max 55 characters).';
    if ($p['color'] === '' || mb_strlen($p['color']) > 50)               $err[] = 'Color is required (max 50 characters).';
    if (mb_strlen($p['variation']) > 255)   $err[] = 'Variation is too long (max 255).';
    if (mb_strlen($p['mini_desc']) > 255)   $err[] = 'Short description is too long (max 255).';
    if (mb_strlen($p['description']) > 1000) $err[] = 'Description is too long (max 1000).';
    if (!is_numeric($p['price']) || (float)$p['price'] < 0) $err[] = 'Price must be a valid number.';
    if (!$edit && $p['stock'] < 0) $err[] = 'Stock cannot be negative.';
    if ($p['new_category'] !== '') {
        if (mb_strlen($p['new_category']) > 255) $err[] = 'New category name is too long.';
    } else {
        $c = $pdo->prepare('SELECT COUNT(*) FROM tbl_categories WHERE category_id = ?');
        $c->execute([$p['category_id']]);
        if (!$c->fetchColumn()) $err[] = 'Please choose a category or type a new one.';
    }
    return [$err, $p];
}

/** Find or create the category; returns its id. */
function resolve_category(PDO $pdo, array $p): int {
    if ($p['new_category'] === '') return $p['category_id'];
    $s = $pdo->prepare('SELECT category_id FROM tbl_categories WHERE LOWER(category_name) = LOWER(?)');
    $s->execute([$p['new_category']]);
    if ($id = $s->fetchColumn()) return (int)$id;
    $pdo->prepare('INSERT INTO tbl_categories (category_name) VALUES (?)')->execute([$p['new_category']]);
    return (int)$pdo->lastInsertId();
}

/** Validate + move the uploaded image. Returns the new filename, or null if none uploaded. */
function save_image(array &$errors): ?string {
    $f = $_FILES['image'] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK)  { $errors[] = 'Image upload failed.'; return null; }
    if ($f['size'] > 2 * 1024 * 1024)   { $errors[] = 'Image must be 2MB or smaller.'; return null; }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext  = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) { $errors[] = 'Image must be PNG, JPG, or WEBP.'; return null; }
    $name = 'p_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], IMG_PATH . $name)) {
        $errors[] = 'Could not save the image. Make sure the images folder exists and is writable.';
        return null;
    }
    return $name;
}

function render_product_form(array $p, array $cats, array $errors, bool $edit): void { ?>
<form method="post" enctype="multipart/form-data" class="form-card">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
    <div class="flash err"><?php foreach ($errors as $m): ?><div><?= e($m) ?></div><?php endforeach; ?></div>
    <?php endif; ?>
    <div class="form-grid">
        <div class="field">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" maxlength="55" value="<?= e($p['product_name']) ?>" required>
        </div>
        <div class="field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="0">Select category</option>
                <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['category_id'] ?>" <?= (int)$p['category_id'] === (int)$c['category_id'] ? 'selected' : '' ?>><?= e($c['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="new_category">Or New Category <small>(optional)</small></label>
            <input type="text" id="new_category" name="new_category" maxlength="255" value="<?= e($p['new_category'] ?? '') ?>" placeholder="e.g. Accessories">
        </div>
        <div class="field">
            <label for="color">Color</label>
            <input type="text" id="color" name="color" maxlength="50" value="<?= e($p['color']) ?>" required>
        </div>
        <div class="field">
            <label for="variation">Variation</label>
            <input type="text" id="variation" name="variation" maxlength="255" value="<?= e($p['variation']) ?>" placeholder="e.g. Premium Cotton | Navy">
        </div>
        <div class="field">
            <label for="price">Price (₱)</label>
            <input type="number" id="price" name="price" min="0" step="0.01" value="<?= e($p['price']) ?>" required>
        </div>
        <div class="field">
            <?php if ($edit): ?>
            <label>Current Stock</label>
            <input type="text" value="<?= (int)$p['stock'] ?>" disabled>
            <small>Change stock using the <a href="stock.php?id=<?= (int)$p['product_id'] ?>">Stock In / Out</a> page so it's logged.</small>
            <?php else: ?>
            <label for="stock">Initial Stock</label>
            <input type="number" id="stock" name="stock" min="0" step="1" value="<?= (int)$p['stock'] ?>">
            <?php endif; ?>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option <?= $p['status'] === 'Available' ? 'selected' : '' ?>>Available</option>
                <option <?= $p['status'] === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
            </select>
        </div>
        <div class="field full">
            <label for="image">Product Image <small>(PNG, JPG, WEBP, max 2MB<?= $edit ? '; leave empty to keep current' : '' ?>)</small></label>
            <?php if ($edit && !empty($p['image'])): ?><img class="thumb" src="../images/<?= e($p['image']) ?>" alt="" onerror="this.style.display='none'"><?php endif; ?>
            <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp">
        </div>
        <div class="field full">
            <label for="mini_desc">Short Description</label>
            <input type="text" id="mini_desc" name="mini_desc" maxlength="255" value="<?= e($p['mini_desc']) ?>">
        </div>
        <div class="field full">
            <label for="description">Full Description</label>
            <textarea id="description" name="description" rows="5" maxlength="1000"><?= e($p['description']) ?></textarea>
        </div>
    </div>
    <div class="form-actions">
        <a href="products.php" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary"><?= $edit ? 'Save Changes' : 'Add Product' ?></button>
    </div>
</form>
<?php }
