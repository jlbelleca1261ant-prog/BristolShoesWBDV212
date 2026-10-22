<?php
declare(strict_types=1);

const SHIPPING_FEE = 150;

function db(): PDO
{
    // Reuse one database connection for the whole PHP request.
    static $connection = null;
    if ($connection !== null) {
        return $connection;
    }

    $path = __DIR__ . '/../config/database.local.php';
    if (!is_file($path)) {
        $path = __DIR__ . '/../config/database.example.php';
    }
    $config = require $path;
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] .
        ';dbname=' . $config['name'] . ';charset=utf8mb4';

    $connection = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $connection;
}

function start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function current_user(): ?array
{
    // The session stores only the ID. The rest comes from the users table.
    start_session();
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id)) {
        return null;
    }
    $query = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

function require_user_id(): int
{
    $user = current_user();
    if (!$user) {
        json_response(['error' => 'Sign in to continue.'], 401);
    }
    return (int)$user['id'];
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_csrf(): void
{
    // A form/request must send the token created for this session.
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        json_response(['error' => 'Session expired. Refresh the page and try again.'], 419);
    }
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function read_json(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['error' => 'Method not allowed.'], 405);
    }
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        json_response(['error' => 'Expected JSON.'], 415);
    }
    $body = file_get_contents('php://input', false, null, 0, 32769);
    if ($body === false || strlen($body) > 32768) {
        json_response(['error' => 'Request is too large.'], 413);
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        json_response(['error' => 'Invalid JSON.'], 400);
    }
    return $data;
}

function field(array $data, string $key, int $limit): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value)) {
        json_response(['error' => "Invalid $key."], 422);
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $limit) {
        json_response(['error' => "Invalid $key."], 422);
    }
    return $value;
}

function product_for_browser(array $row): array
{
    // Database column names differ from the names used by shop.js.
    return [
        'id' => (int)$row['id'],
        'category' => $row['category'],
        'type' => $row['type'],
        'name' => $row['name'],
        'price' => (int)$row['price'],
        'originalPrice' => $row['original_price'] === null ? null : (int)$row['original_price'],
        'img' => $row['image_url'],
        'imgs' => json_decode($row['images_json'], true),
        'sizes' => json_decode($row['sizes_json'], true),
        'unavailable' => json_decode($row['unavailable_json'], true),
        'desc' => $row['description'],
        'sale' => (bool)$row['is_sale'],
    ];
}

