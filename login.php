<?php
require __DIR__ . '/includes/common.php';

$next = $_POST['next'] ?? $_GET['next'] ?? 'account.php';
if ($next !== 'products.php') {
    $next = 'account.php';
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        start_session();
        $attempts = (int)($_SESSION['login_attempts'] ?? 0);
        $lastAttempt = (int)($_SESSION['last_login_attempt'] ?? 0);

        if ($attempts >= 5 && time() - $lastAttempt < 300) {
            $error = 'Too many attempts. Try again in a few minutes.';
        } else {
            $find = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $find->execute([$email]);
            $account = $find->fetch();

            if (!$account || !password_verify($password, $account['password_hash'])) {
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['last_login_attempt'] = time();
                $error = 'Email or password is incorrect.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$account['id'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
                header('Location: ' . $next);
                exit;
            }
        }
    }
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/account_header.php';
?>
<section class="account-card auth-card">
    <p class="eyebrow">BRISTOL SHOES</p>
    <h1>Welcome back</h1>
    <p class="account-intro">Sign in to continue shopping.</p>
    <?php if ($error): ?><p class="form-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="account-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <label>Email address<input type="email" name="email" autocomplete="email" maxlength="190" value="<?= e($email) ?>" required></label>
        <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
        <button class="account-button" type="submit">Sign in</button>
    </form>
    <p class="auth-switch">New to Bristol Shoes? <a href="register.php?next=<?= e($next) ?>">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/account_footer.php'; ?>
