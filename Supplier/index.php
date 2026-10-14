<?php
session_start();

require_once __DIR__ . '/../connection/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT supplier_id, email, password, company, role
             FROM supplier_profiles
             WHERE email = ?
             AND role = 'supplier'
             LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $supplier = $result->fetch_assoc();

            if ($supplier && $password === $supplier['password']) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $supplier['supplier_id'];
                $_SESSION['supplier_id'] = $supplier['supplier_id'];
                $_SESSION['supplier_email'] = $supplier['email'];
                $_SESSION['supplier_company'] = $supplier['company'];
                $_SESSION['role'] = 'supplier';

                header('Location: dashboard.php');
                exit;
            }

            $error = 'Invalid email or password.';
            $stmt->close();
        } else {
            $error = 'Unable to process login. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Login | AU Merch</title>
</head>
<body>

    <h2>Supplier Login</h2>

    <?php if ($error !== ''): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">Login</button>
    </form>

</body>
</html>