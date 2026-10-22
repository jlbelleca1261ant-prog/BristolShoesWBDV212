<?php
// Old links to auth.php still work. New code uses login.php and register.php.
$next = ($_GET['next'] ?? '') === 'products.php' ? 'products.php' : 'account.php';
$page = ($_GET['mode'] ?? '') === 'register' ? 'register.php' : 'login.php';
header('Location: ' . $page . '?next=' . $next);
exit;
