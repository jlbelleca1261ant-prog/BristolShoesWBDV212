<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';
start_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    is_string($_POST['csrf_token'] ?? null) &&
    hash_equals(csrf_token(), $_POST['csrf_token'])) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: index.php');
    exit;
}
http_response_code(405);
echo 'Method not allowed.';

