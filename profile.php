<?php
require __DIR__ . '/includes/common.php';

$user = current_user();
if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';
$notice = '';
$name = $user['name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif ($action === 'details') {
        // The customer may change only their own name and email.
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 160) {
            $error = 'Enter a name between 2 and 160 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $error = 'Enter a valid email address.';
        } else {
            $check = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $check->execute([$email, $user['id']]);
            if ($check->fetch()) {
                $error = 'That email is already used by another account.';
            } else {
                try {
                    $save = db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $save->execute([$name, $email, $user['id']]);
                    $notice = 'Profile updated.';
                } catch (PDOException $exception) {
                    if ($exception->getCode() !== '23000') {
                        throw $exception;
                    }
                    $error = 'That email is already used by another account.';
                }
            }
        }
    } elseif ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $find = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $find->execute([$user['id']]);
        $savedHash = $find->fetchColumn();

        if (!$savedHash || !password_verify($current, $savedHash)) {
            $error = 'Your current password is incorrect.';
        } elseif (strlen($new) < 10) {
            $error = 'Use a new password with at least 10 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $save = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $save->execute([$newHash, $user['id']]);
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $notice = 'Password changed.';
        }
    } else {
        $error = 'Unknown form action.';
    }
}

$pageTitle = 'Profile and settings';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card">
    <p class="eyebrow">YOUR ACCOUNT</p>
    <div class="account-heading"><div><h1>Profile &amp; settings</h1><p class="account-intro">Update your account details here.</p></div><a class="outline-button" href="account.php">Back to account</a></div>
    <?php if ($error): ?><p class="form-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($notice): ?><p class="form-success-note" role="status"><?= e($notice) ?></p><?php endif; ?>
</section>
<section class="account-card">
    <h2>Personal details</h2>
    <form method="post" class="account-form settings-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="details">
        <label>Full name<input name="name" value="<?= e($name) ?>" maxlength="160" autocomplete="name" required></label>
        <label>Email address<input type="email" name="email" value="<?= e($email) ?>" maxlength="190" autocomplete="email" required></label>
        <button class="account-button" type="submit">Save details</button>
    </form>
</section>
<section class="account-card">
    <h2>Change password</h2>
    <form method="post" class="account-form settings-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="password">
        <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
        <label>New password<input type="password" name="new_password" minlength="10" autocomplete="new-password" required></label>
        <label>Confirm new password<input type="password" name="confirm_password" minlength="10" autocomplete="new-password" required></label>
        <button class="account-button" type="submit">Change password</button>
    </form>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
