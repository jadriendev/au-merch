<?php
require 'config.php';
if (!empty($_SESSION['admin_id']) && ($_SESSION['role'] ?? '') === 'admin') {
    header('Location: dashboard.php');
    exit;
}
$error = '';
$user  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user = trim((string)($_POST['username'] ?? ''));
    if (time() < ($_SESSION['lock_until'] ?? 0)) {
        $error = 'Too many failed attempts. Please wait a few seconds and try again.';
    } else {
        $pass = (string)($_POST['password'] ?? '');
        $st = $pdo->prepare('SELECT * FROM tbl_admin WHERE username = ? LIMIT 1');
        $st->execute([$user]);
        $row = $st->fetch();
        $ok = false;
        if ($row && $row['role'] === 'admin') {
            if (password_get_info($row['password'])['algo']) {
                $ok = password_verify($pass, $row['password']);
            } else {                                   // legacy plain-text password: verify, then upgrade to a hash
                $ok = hash_equals($row['password'], $pass);
                if ($ok) {
                    $pdo->prepare('UPDATE tbl_admin SET password = ? WHERE admin_id = ?')
                        ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['admin_id']]);
                }
            }
        }
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int)$row['admin_id'];
            $_SESSION['username']   = $row['username'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['role']       = 'admin';
            header('Location: dashboard.php');
            exit;
        }
        $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
        if ($_SESSION['tries'] >= 5) { $_SESSION['lock_until'] = time() + 30; $_SESSION['tries'] = 0; }
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://www.auchiefslms.com/college/pluginfile.php/1/core_admin/logocompact/300x300/1784347206/au-logo-smaller.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="css/inventory.css">
    <title>Admin Login | AU Merch</title>
</head>
<body class="login-body">
    <main>
        <form method="post" class="login-card">
            <img src="../images/Arellano_University_New_Logo.png" alt="Arellano University logo">
            <h1>Inventory Admin</h1>
            <p>Sign in to manage products, stock and supplier requests.</p>

            <?php if ($error): ?>
            <div class="flash err" role="alert"><i class="fa fa-circle-exclamation"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <?= csrf_field() ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($user) ?>" autocomplete="username" required autofocus>

            <label for="password">Password</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" autocomplete="current-password" required>
                <button type="button" id="togglePw" aria-label="Show password"><i class="fa fa-eye"></i></button>
            </div>

            <button type="submit" class="btn-primary"><i class="fa fa-right-to-bracket"></i> Log in</button>
        </form>
    </main>
    <script>
        const pw = document.getElementById('password');
        document.getElementById('togglePw').addEventListener('click', (ev) => {
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            ev.currentTarget.innerHTML = '<i class="fa ' + (show ? 'fa-eye-slash' : 'fa-eye') + '"></i>';
        });
    </script>
</body>
</html>
