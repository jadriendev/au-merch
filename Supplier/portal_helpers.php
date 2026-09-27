<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../connection/config.php';
$conn->set_charset('utf8mb4');

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$requestColumns = [];
$columnResult = $conn->query('SHOW FULL COLUMNS FROM tbl_stock_requests');
while ($column = $columnResult->fetch_assoc()) {
    $requestColumns[$column['Field']] = $column['Collation'];
}
foreach ([
    'variant' => "ALTER TABLE tbl_stock_requests ADD COLUMN variant VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''",
    'unit_price' => 'ALTER TABLE tbl_stock_requests ADD COLUMN unit_price DECIMAL(10,2) NULL DEFAULT NULL',
    'notes' => 'ALTER TABLE tbl_stock_requests ADD COLUMN notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL',
] as $columnName => $alterSql) {
    if (!array_key_exists($columnName, $requestColumns)) {
        $conn->query($alterSql);
    }
}
if (isset($requestColumns['variant']) && strpos((string) $requestColumns['variant'], 'utf8mb4_') !== 0) {
    $conn->query("ALTER TABLE tbl_stock_requests MODIFY COLUMN variant VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''");
}
if (isset($requestColumns['notes']) && strpos((string) $requestColumns['notes'], 'utf8mb4_') !== 0) {
    $conn->query('ALTER TABLE tbl_stock_requests MODIFY COLUMN notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
}

$conn->query("CREATE TABLE IF NOT EXISTS supplier_profiles (
    supplier_id VARCHAR(191) NOT NULL PRIMARY KEY,
    email VARCHAR(190) NOT NULL DEFAULT '',
    phone VARCHAR(50) NOT NULL DEFAULT '',
    company VARCHAR(150) NOT NULL DEFAULT '',
    address VARCHAR(255) NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function portal_supplier_id(): string
{
    return (string) $_SESSION['user_id'];
}

function portal_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function portal_products(mysqli $conn): array
{
    $fallbackImages = [
        'hoodie' => 'hoodie2.png', 'shirt' => 'tshirt.png', 'pants' => 'hoodie.png',
        'cap' => 'hat-svgrepo-com.svg', 'bag' => 'bag-smile-svgrepo-com.svg',
        'bottle' => 'water-bottle-bottle-svgrepo-com.svg',
    ];
    $products = [];
    $result = $conn->query("SELECT product_id, product_name, color, variation, price, image FROM tbl_products WHERE status = 'Available' ORDER BY product_name, color, variation");
    while ($row = $result->fetch_assoc()) {
        $imageName = basename((string) $row['image']);
        if ($imageName === '' || !is_file(__DIR__ . '/../images/' . $imageName)) {
            $name = strtolower((string) $row['product_name']);
            $imageName = 'Arellano_University_New_Logo.png';
            foreach ($fallbackImages as $keyword => $fallback) {
                if (strpos($name, $keyword) !== false) {
                    $imageName = $fallback;
                    break;
                }
            }
        }
        $row['image_path'] = '../images/' . $imageName;
        $color = trim((string) $row['color']);
        $variation = trim((string) $row['variation']);
        $row['variant_label'] = $variation !== '' && ($color === '' || stripos($variation, $color) !== false)
            ? $variation
            : trim($color . ($variation !== '' ? ' / ' . $variation : ''));
        $products[(int) $row['product_id']] = $row;
    }
    return $products;
}

function portal_status_label(string $status): string
{
    return in_array(strtolower($status), ['submitted', 'pending'], true) ? 'Pending' : ucfirst(strtolower($status));
}

function portal_status_class(string $status): string
{
    $label = strtolower(portal_status_label($status));
    return in_array($label, ['pending', 'approved', 'rejected'], true) ? $label : 'pending';
}

function portal_csrf_token(): string
{
    if (empty($_SESSION['portal_csrf'])) {
        $_SESSION['portal_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['portal_csrf'];
}

function portal_verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['portal_csrf'])
        && hash_equals($_SESSION['portal_csrf'], (string) $_POST['csrf_token']);
}

function portal_flash(string $message, string $type = 'success'): void
{
    $_SESSION['portal_flash'] = ['message' => $message, 'type' => $type];
}

function portal_take_flash(): ?array
{
    if (empty($_SESSION['portal_flash'])) {
        return null;
    }
    $flash = $_SESSION['portal_flash'];
    unset($_SESSION['portal_flash']);
    return $flash;
}

function portal_header(string $active, string $title, string $description): void
{
    $name = trim((string) ($_SESSION['first_name'] ?? '') . ' ' . (string) ($_SESSION['last_name'] ?? ''));
    $name = $name !== '' ? $name : 'Supplier';
    $links = [
        'dashboard' => ['dashboard.php', 'fa-gauge-high', 'Dashboard'],
        'add-stock' => ['add_stock.php', 'fa-boxes-stacked', 'Add Stock'],
        'stock-history' => ['stock_history.php', 'fa-clock-rotate-left', 'Stock History'],
        'profile' => ['profile.php', 'fa-user', 'Profile'],
    ];
    $flash = portal_take_flash();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo portal_escape($title); ?> | AU Merch Supplier Portal</title>
    <link rel="shortcut icon" href="../images/Arellano_University_New_Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="portal.css">
</head>
<body>
<div class="portal-shell">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php">
            <img src="../images/Arellano_University_New_Logo.png" alt="Arellano University logo">
            <span><strong>AU MERCH</strong><small>SUPPLIER PORTAL</small></span>
        </a>
        <nav class="side-nav" aria-label="Supplier navigation">
            <?php foreach ($links as $key => [$href, $icon, $label]): ?>
                <a class="nav-link <?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo portal_escape($href); ?>" <?php echo $active === $key ? 'aria-current="page"' : ''; ?>>
                    <i class="fa-solid <?php echo portal_escape($icon); ?>"></i><span><?php echo portal_escape($label); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <a class="logout-link" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <div class="mobile-brand"><img src="../images/Arellano_University_New_Logo.png" alt="AU logo"><strong>AU MERCH</strong></div>
            <div class="page-heading"><h1><?php echo portal_escape($title); ?></h1><p><?php echo portal_escape($description); ?></p></div>
            <div class="topbar-user"><span class="bell"><i class="fa-regular fa-bell"></i></span><span class="user-initial"><?php echo portal_escape(strtoupper(substr($name, 0, 1))); ?></span><span class="user-name"><?php echo portal_escape($name); ?></span></div>
        </header>
        <?php if ($flash): ?>
            <div class="flash <?php echo portal_escape($flash['type']); ?>" role="status"><i class="fa-solid <?php echo $flash['type'] === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i><?php echo portal_escape($flash['message']); ?></div>
        <?php endif; ?>
        <div class="page-content">
    <?php
}

function portal_footer(): void
{
    ?>
        </div>
    </main>
</div>
</body>
</html>
    <?php
}
?>
