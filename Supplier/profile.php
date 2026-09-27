<?php
require_once __DIR__ . '/portal_helpers.php';
$supplierId = portal_supplier_id();
$displayName = trim((string) ($_SESSION['first_name'] ?? '') . ' ' . (string) ($_SESSION['last_name'] ?? ''));
$displayName = $displayName !== '' ? $displayName : 'AU Merch Supplier';
$userId = (string) $_SESSION['user_id'];
$emailStmt = $conn->prepare('SELECT email FROM tbl_users WHERE user_id = ? LIMIT 1');
$emailStmt->bind_param('s', $userId);
$emailStmt->execute();
$userEmail = (string) (($emailStmt->get_result()->fetch_assoc()['email'] ?? ''));
$emailStmt->close();
$profile = ['email' => $userEmail, 'phone' => '', 'company' => 'AU Merch Supplier', 'address' => ''];
$stmt = $conn->prepare('SELECT email, phone, company, address FROM supplier_profiles WHERE supplier_id = ?');
$stmt->bind_param('s', $supplierId);
$stmt->execute();
$savedProfile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($savedProfile) {
    $profile = array_merge($profile, $savedProfile);
    if ($profile['email'] === '') {
        $profile['email'] = $userEmail;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!portal_verify_csrf()) {
        portal_flash('Your session expired. Please try again.', 'error');
        header('Location: profile.php');
        exit;
    }
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save_profile') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $company = trim((string) ($_POST['company'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        if (($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || strlen($email) > 190 || strlen($phone) > 50 || strlen($company) > 150 || strlen($address) > 255) {
            portal_flash('Enter a valid email and keep each profile field within its allowed length.', 'error');
            header('Location: profile.php?edit=1');
            exit;
        }
        $stmt = $conn->prepare('INSERT INTO supplier_profiles (supplier_id, email, phone, company, address) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email = VALUES(email), phone = VALUES(phone), company = VALUES(company), address = VALUES(address)');
        $stmt->bind_param('sssss', $supplierId, $email, $phone, $company, $address);
        $stmt->execute();
        $stmt->close();
        $emailStmt = $conn->prepare('UPDATE tbl_users SET email = ? WHERE user_id = ?');
        $emailStmt->bind_param('ss', $email, $userId);
        $emailStmt->execute();
        $emailStmt->close();
        portal_flash('Supplier profile updated.');
        header('Location: profile.php');
        exit;
    }
    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($newPassword !== $confirm || strlen($newPassword) < 8 || strlen($newPassword) > 72) {
            portal_flash('The new password must be 8–72 characters and match its confirmation.', 'error');
            header('Location: profile.php?password=1');
            exit;
        }
        $userId = (string) $_SESSION['user_id'];
        $stmt = $conn->prepare('SELECT password FROM tbl_users WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $storedPassword = (string) ($user['password'] ?? '');
        if (!$user || !(password_verify($current, $storedPassword) || hash_equals($storedPassword, $current))) {
            portal_flash('Your current password is incorrect.', 'error');
            header('Location: profile.php?password=1');
            exit;
        }
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE tbl_users SET password = ? WHERE user_id = ?');
        $stmt->bind_param('ss', $newHash, $userId);
        $stmt->execute();
        $stmt->close();
        portal_flash('Password updated successfully.');
        header('Location: profile.php');
        exit;
    }
    portal_flash('That profile action is not available.', 'error');
    header('Location: profile.php');
    exit;
}

portal_header('profile', 'Profile', 'View and manage your supplier information.');
?>
<section class="profile-card card">
    <h2 class="section-title"><span>Supplier Information</span></h2>
    <div class="profile-banner"><div class="profile-identity"><img class="profile-logo" src="../images/Arellano_University_New_Logo.png" alt="Arellano University logo"><div><p class="profile-name"><?php echo portal_escape($displayName); ?></p><div class="profile-role">Supplier</div></div></div><?php if (empty($_GET['edit'])): ?><a class="btn outline" href="profile.php?edit=1"><i class="fa-solid fa-pen"></i> Edit Profile</a><?php endif; ?></div>
    <?php if (!empty($_GET['edit'])): ?>
        <form method="post" action="profile.php"><input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>"><input type="hidden" name="action" value="save_profile"><div class="form-grid">
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="190" value="<?php echo portal_escape($profile['email']); ?>" placeholder="name@example.com"></div>
            <div class="field"><label for="phone">Contact Number</label><input id="phone" name="phone" type="tel" maxlength="50" value="<?php echo portal_escape($profile['phone']); ?>" placeholder="+63 912 345 6789"></div>
            <div class="field"><label for="company">Company Name</label><input id="company" name="company" maxlength="150" value="<?php echo portal_escape($profile['company']); ?>" required></div>
            <div class="field"><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?php echo portal_escape($profile['address']); ?>" placeholder="City, province"></div>
        </div><div class="form-actions"><a class="btn outline" href="profile.php">Cancel</a>&nbsp;&nbsp;<button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div></form>
    <?php else: ?>
        <div class="profile-info"><div class="info-box"><i class="fa-solid fa-envelope"></i><?php echo portal_escape($profile['email'] ?: 'Email not provided'); ?></div><div class="info-box"><i class="fa-solid fa-phone"></i><?php echo portal_escape($profile['phone'] ?: 'Contact number not provided'); ?></div><div class="info-box"><i class="fa-solid fa-building"></i><?php echo portal_escape($profile['company']); ?></div><div class="info-box"><i class="fa-solid fa-location-dot"></i><?php echo portal_escape($profile['address'] ?: 'Address not provided'); ?></div></div>
    <?php endif; ?>
    <div class="password-card"><div><strong><i class="fa-solid fa-lock"></i>&nbsp; Change Password</strong><p>Keep your account secure.</p></div><?php if (empty($_GET['password'])): ?><a class="btn outline" href="profile.php?password=1"><i class="fa-solid fa-key"></i> Update Password</a><?php endif; ?></div>
    <?php if (!empty($_GET['password'])): ?>
        <form class="password-form" method="post" action="profile.php"><input type="hidden" name="csrf_token" value="<?php echo portal_escape(portal_csrf_token()); ?>"><input type="hidden" name="action" value="change_password"><div class="form-grid"><div class="field full"><label for="current_password">Current Password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div><div class="field"><label for="new_password">New Password</label><input id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div><div class="field"><label for="confirm_password">Confirm New Password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div></div><div class="form-actions"><a class="btn outline" href="profile.php">Cancel</a>&nbsp;&nbsp;<button class="btn" type="submit"><i class="fa-solid fa-key"></i> Update Password</button></div></form>
    <?php endif; ?>
</section>
<?php portal_footer(); ?>
