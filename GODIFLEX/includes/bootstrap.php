<?php

require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        exit('Your session expired. Refresh the page and try again.');
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();

    $statement = database()->prepare('SELECT role FROM users WHERE id = :id');
    $statement->execute(['id' => $user['id']]);
    $role = $statement->fetchColumn();

    if ($role !== false) {
        $_SESSION['user']['role'] = $role;
    }

    if ($role !== 'admin') {
        http_response_code(403);
        header('Location: index.php');
        exit;
    }

    $_SESSION['user']['role'] = 'admin';
    return $_SESSION['user'];
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function movie_posters(): array
{
    $directory = __DIR__ . '/../Pics';
    $files = scandir($directory);
    if ($files === false) {
        throw new RuntimeException('The Pics folder could not be read.');
    }

    return array_values(array_filter($files, static function ($file) use ($directory) {
        return is_file($directory . '/' . $file)
            && preg_match('/\.(jpe?g|png|webp)$/i', $file) === 1;
    }));
}
