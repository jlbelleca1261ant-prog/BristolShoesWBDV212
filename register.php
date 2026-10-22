<?php
require __DIR__ . '/includes/common.php';

$next = $_POST['next'] ?? $_GET['next'] ?? 'account.php';
if ($next !== 'products.php') {
    $next = 'account.php';
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 160) {
        $error = 'Enter your name (2 to 160 characters).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Use a password with at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = db()->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'That email already has an account. Sign in instead.';
        } else {
            try {
                // Save a password hash, never the actual password.
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $save = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
                $save->execute([$name, $email, $hash]);

                start_session();
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)db()->lastInsertId();
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ' . $next);
                exit;
            } catch (PDOException $exception) {
                // The unique email rule also catches two simultaneous sign-ups.
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $error = 'That email already has an account. Sign in instead.';
            }
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card auth-card">
    <p class="eyebrow">BRISTOL SHOES</p>
    <h1>Create your account</h1>
    <p class="account-intro">Keep your cart and orders in one place.</p>
    <?php if ($error): ?><p class="form-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="account-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label>Full name<input name="name" autocomplete="name" maxlength="160" value="<?= e($name) ?>" required></label>
        <label>Email address<input type="email" name="email" autocomplete="email" maxlength="190" value="<?= e($email) ?>" required></label>
        <label>Password<input type="password" name="password" autocomplete="new-password" minlength="10" required></label>
        <label>Confirm password<input type="password" name="confirm_password" autocomplete="new-password" minlength="10" required></label>
        <button class="account-button" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="login.php?next=<?= e($next) ?>">Sign in</a></p>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
