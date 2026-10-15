<?php
require_once __DIR__ . '/portal_helpers.php';
$supplierId = portal_supplier_id();

$stmt = $conn->prepare('SELECT email, phone, company, address, password FROM supplier_profiles WHERE supplier_id = ? LIMIT 1');
$stmt->bind_param('i', $supplierId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profile) {
    header('Location: logout.php');
    exit;
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || strlen($phone) > 50 || $company === '' || strlen($company) > 150 || strlen($address) > 255) {
            portal_flash('Enter a valid email and a company name. Keep each field within its length.', 'error');
            header('Location: profile.php?edit=1');
            exit;
        }
        $dup = $conn->prepare('SELECT supplier_id FROM supplier_profiles WHERE email = ? AND supplier_id <> ? LIMIT 1');
        $dup->bind_param('si', $email, $supplierId);
        $dup->execute();
        $taken = $dup->get_result()->num_rows > 0;
        $dup->close();
        if ($taken) {
            portal_flash('That email is already used by another supplier account.', 'error');
            header('Location: profile.php?edit=1');
            exit;
        }
        $up = $conn->prepare('UPDATE supplier_profiles SET email = ?, phone = ?, company = ?, address = ? WHERE supplier_id = ?');
        $up->bind_param('ssssi', $email, $phone, $company, $address, $supplierId);
        $up->execute();
        $up->close();
        $_SESSION['supplier_email'] = $email;
        $_SESSION['supplier_company'] = $company;
        portal_flash('Supplier profile updated.');
        header('Location: profile.php');
        exit;
    }

    if ($action === 'change_password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $stored = (string) $profile['password'];
        if ($new !== $confirm || strlen($new) < 8 || strlen($new) > 72) {
            portal_flash('The new password must be 8–72 characters and match its confirmation.', 'error');
            header('Location: profile.php?password=1');
            exit;
        }
        if (!(password_verify($current, $stored) || hash_equals($stored, $current))) {
            portal_flash('Your current password is incorrect.', 'error');
            header('Location: profile.php?password=1');
            exit;
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $up = $conn->prepare('UPDATE supplier_profiles SET password = ? WHERE supplier_id = ?');
        $up->bind_param('si', $hash, $supplierId);
        $up->execute();
        $up->close();
        portal_flash('Password updated successfully.');
        header('Location: profile.php');
        exit;
    }
    header('Location: profile.php');
    exit;
}

portal_header('profile', 'Profile', 'View and manage your supplier information.');
$token = portal_escape(portal_csrf_token());
?>
<section class="profile-card card">
    <h2 class="section-title"><span>Supplier Information</span></h2>
    <div class="profile-banner"><div class="profile-identity"><img class="profile-logo" src="../images/Arellano_University_New_Logo.png" alt="Arellano University logo"><div><p class="profile-name"><?php echo portal_escape($profile['company']); ?></p><div class="profile-role">Supplier</div></div></div><?php if (empty($_GET['edit'])): ?><a class="btn outline" href="profile.php?edit=1"><i class="fa-solid fa-pen"></i> Edit Profile</a><?php endif; ?></div>
    <?php if (!empty($_GET['edit'])): ?>
        <form method="post" action="profile.php"><input type="hidden" name="csrf_token" value="<?php echo $token; ?>"><input type="hidden" name="action" value="save_profile"><div class="form-grid">
            <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" maxlength="190" value="<?php echo portal_escape($profile['email']); ?>" required></div>
            <div class="field"><label for="phone">Contact Number</label><input id="phone" name="phone" type="tel" maxlength="50" value="<?php echo portal_escape($profile['phone']); ?>" placeholder="+63 912 345 6789"></div>
            <div class="field"><label for="company">Company Name</label><input id="company" name="company" maxlength="150" value="<?php echo portal_escape($profile['company']); ?>" required></div>
            <div class="field"><label for="address">Address</label><input id="address" name="address" maxlength="255" value="<?php echo portal_escape($profile['address']); ?>" placeholder="City, province"></div>
        </div><div class="form-actions"><a class="btn outline" href="profile.php">Cancel</a>&nbsp;&nbsp;<button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button></div></form>
    <?php else: ?>
        <div class="profile-info"><div class="info-box"><i class="fa-solid fa-envelope"></i><?php echo portal_escape($profile['email'] ?: 'Email not provided'); ?></div><div class="info-box"><i class="fa-solid fa-phone"></i><?php echo portal_escape($profile['phone'] ?: 'Contact number not provided'); ?></div><div class="info-box"><i class="fa-solid fa-building"></i><?php echo portal_escape($profile['company']); ?></div><div class="info-box"><i class="fa-solid fa-location-dot"></i><?php echo portal_escape($profile['address'] ?: 'Address not provided'); ?></div></div>
    <?php endif; ?>
    <div class="password-card"><div><strong><i class="fa-solid fa-lock"></i>&nbsp; Change Password</strong><p>Keep your account secure.</p></div><?php if (empty($_GET['password'])): ?><a class="btn outline" href="profile.php?password=1"><i class="fa-solid fa-key"></i> Update Password</a><?php endif; ?></div>
    <?php if (!empty($_GET['password'])): ?>
        <form class="password-form" method="post" action="profile.php"><input type="hidden" name="csrf_token" value="<?php echo $token; ?>"><input type="hidden" name="action" value="change_password"><div class="form-grid"><div class="field full"><label for="current_password">Current Password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></div><div class="field"><label for="new_password">New Password</label><input id="new_password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div><div class="field"><label for="confirm_password">Confirm New Password</label><input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required></div></div><div class="form-actions"><a class="btn outline" href="profile.php">Cancel</a>&nbsp;&nbsp;<button class="btn" type="submit"><i class="fa-solid fa-key"></i> Update Password</button></div></form>
    <?php endif; ?>
</section>
<?php portal_footer(); ?>
