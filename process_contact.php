<?php
declare(strict_types=1);
require __DIR__ . '/includes/common.php';

$data = read_json();
require_csrf();
$name = field($data, 'name', 160);
$email = field($data, 'email', 190);
$message = field($data, 'message', 5000);
$phone = $data['phone'] ?? '';
if (!is_string($phone)) {
    json_response(['error' => 'Invalid phone number.'], 422);
}
$phone = trim($phone);
if (mb_strlen($name) < 2) {
    json_response(['error' => 'Enter your name.'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Enter a valid email address.'], 422);
}
if (mb_strlen($message) < 10) {
    json_response(['error' => 'Write a message of at least 10 characters.'], 422);
}
if ($phone !== '' && !preg_match('/^(?:\+639|09)\d{9}$/', $phone)) {
    json_response(['error' => 'Enter a valid Philippine mobile number.'], 422);
}

try {
    $savedPhone = $phone === '' ? null : $phone;
    $insert = db()->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)');
    $insert->execute([$name, $email, $savedPhone, $message]);
    json_response(['message' => 'Your message was saved.'], 201);
} catch (Throwable $error) {
    error_log((string)$error);
    json_response(['error' => 'Could not save your message. Please try again later.'], 503);
}
