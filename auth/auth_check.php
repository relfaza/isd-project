<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function auth_path_ends_with(string $value, string $suffix): bool
{
    return $suffix === '' || substr($value, -strlen($suffix)) === $suffix;
}

function auth_base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $directory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

    if (auth_path_ends_with($directory, '/admin') || auth_path_ends_with($directory, '/auth')) {
        $directory = rtrim(dirname($directory), '/');
    }

    return $directory === '' ? '' : $directory;
}

function auth_redirect(string $path): void
{
    header('Location: ' . auth_base_path() . '/' . ltrim($path, '/'));
    exit;
}

// Require an active authenticated session before rendering protected pages.
function require_login(): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        auth_redirect('auth/login.php');
    }
}

// Admin pages call this helper before any HTML output.
function require_admin(): void
{
    require_login();

    if ($_SESSION['role'] !== 'admin') {
        auth_redirect('catalog.php');
    }
}

// Member pages call this helper when both members and admins may view the page.
function require_member(): void
{
    require_login();

    if (!in_array($_SESSION['role'], ['member', 'admin'], true)) {
        auth_redirect('auth/login.php');
    }
}

function current_user(PDO $pdo): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['name'] = $user['name'];
    }

    return $user ?: null;
}
