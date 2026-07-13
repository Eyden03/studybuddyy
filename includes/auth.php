<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }

    $loaded = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    if (!$user) {
        unset($_SESSION['user_id']);
    }

    return $user;
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? 'home.php');
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $loginPath = str_contains($script, '/admin/') ? '../login.php' : 'login.php';
        redirect($loginPath . '?next=' . $next);
    }

    return $user;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('home.php');
    }
}

function is_admin(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && ($user['role'] ?? 'user') === 'admin';
}

function require_admin(): array
{
    $user = require_login();
    if (!is_admin($user)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
