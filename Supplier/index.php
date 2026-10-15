<?php
session_start();
require_once __DIR__ . '/../connection/config.php';

if (!empty($_SESSION['supplier_id']) && ($_SESSION['role'] ?? '') === 'supplier') {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT supplier_id, email, password, company FROM supplier_profiles WHERE email = ? AND role = 'supplier' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $supplier = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stored = (string) ($supplier['password'] ?? '');
        if ($supplier && (password_verify($password, $stored) || hash_equals($stored, $password))) {
            session_regenerate_id(true);
            $_SESSION['supplier_id'] = (int) $supplier['supplier_id'];
            $_SESSION['supplier_email'] = $supplier['email'];
            $_SESSION['supplier_company'] = $supplier['company'];
            $_SESSION['role'] = 'supplier';
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Login | AU Merch</title>
    <link rel="shortcut icon" href="../images/Arellano_University_New_Logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="portal.css">
    <link rel="stylesheet" href="portal_extra.css">
</head>
<body class="login-page">
    <main class="login-card">
        <img src="../images/Arellano_University_New_Logo.png" alt="Arellano University logo">
        <h1>Supplier Portal</h1>
        <p>Sign in to manage AU Merch stock requests.</p>

        <?php if ($error !== ''): ?>
            <div class="flash error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="name@company.com" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" id="togglePw" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <button class="btn" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Log in</button>
        </form>
    </main>
    <script>
        const pw = document.getElementById('password');
        document.getElementById('togglePw').addEventListener('click', (e) => {
            const show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            e.currentTarget.innerHTML = '<i class="fa-solid ' + (show ? 'fa-eye-slash' : 'fa-eye') + '"></i>';
        });
    </script>
</body>
</html>
