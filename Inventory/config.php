<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

const DB_HOST   = '127.0.0.1';
const DB_NAME   = 'aumerch';
const DB_USER   = 'root';
const DB_PASS   = '';
const LOW_STOCK = 5;                       // stock <= this = "Low Stock"
const IMG_PATH  = __DIR__ . '/../images/'; // product images folder

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $ex) {
    http_response_code(500);
    exit('Database connection failed. Check config.php.');
}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function peso($n): string { return '₱' . number_format((float)$n, 2); }

function require_admin(): void {
    if (empty($_SESSION['admin_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Invalid or expired form. Go back, refresh the page, and try again.');
    }
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }
function pull_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

/** [label, css class] shown in the Status column */
function stock_badge(int $stock, string $status): array {
    if ($status !== 'Available') return ['Unavailable', 'inactive'];
    if ($stock <= 0)             return ['Out of Stock', 'inactive'];
    if ($stock <= LOW_STOCK)     return ['Low Stock', 'processing'];
    return ['Available', 'activated'];
}

function action_class(string $a): string {
    return match (true) {
        $a === 'Stock In'                 => 'activated',
        $a === 'Stock Out'                => 'processing',
        str_contains($a, 'Deleted')       => 'inactive',
        default                           => 'shipped',
    };
}

function log_action(PDO $pdo, ?int $pid, string $action, ?int $qty, ?int $prev, ?int $new): void {
    $pdo->prepare('INSERT INTO tbl_logs (product_id, action, performed_by, quantity_changed, previous_stock, new_stock)
                   VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$pid, $action, $_SESSION['username'] ?? 'admin', $qty, $prev, $new]);
}

function pager(int $total, int $page, int $per, string $noun): void {
    $pages = max(1, (int)ceil($total / $per));
    $from  = $total ? ($page - 1) * $per + 1 : 0;
    $to    = min($total, $page * $per);
    $qs    = $_GET;
    $url   = function (int $p) use ($qs): string { $qs['page'] = $p; return '?' . http_build_query($qs); };
    echo '<div class="pagination"><span class="showing-products">Showing ' . $from . '–' . $to . ' of ' . $total . ' ' . e($noun) . '</span><div class="pagination-buttons">';
    if ($page > 1) echo '<a href="' . e($url($page - 1)) . '" class="page-arrow"><i class="fa fa-chevron-left"></i></a>';
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) {
        echo '<a href="' . e($url($i)) . '" class="page' . ($i === $page ? ' active-page' : '') . '">' . $i . '</a>';
    }
    if ($page < $pages) echo '<a href="' . e($url($page + 1)) . '" class="page-arrow"><i class="fa fa-chevron-right"></i></a>';
    echo '</div></div>';
}
