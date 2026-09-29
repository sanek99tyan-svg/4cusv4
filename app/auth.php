<?php
declare(strict_types=1);

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $config = require __DIR__ . '/config.php';
    session_name($config['app']['session_name']);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function admin_user(): ?array
{
    boot_session();
    return $_SESSION['admin'] ?? null;
}

function require_admin(): void
{
    if (!admin_user()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function csrf_token(): string
{
    boot_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    boot_session();
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', (string)$token)) {
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}