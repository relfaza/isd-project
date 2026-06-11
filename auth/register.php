<?php
session_start();

require_once __DIR__ . '/../db.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Database connection not found. Expected PDO $pdo from db.php.');
}

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    header('Location: ../catalog.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

function registerEmailExists(string $email): bool
{
    $stmt = $GLOBALS['pdo']->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute([$email]);

    return (int) $stmt->fetchColumn() > 0;
}

function createMemberUser(string $name, string $email, string $passwordHash): void
{
    $role = 'member';

    $stmt = $GLOBALS['pdo']->prepare(
        'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email, $passwordHash, $role]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            if (registerEmailExists($email)) {
                $errors[] = 'Email is already registered.';
            } else {
                createMemberUser($name, $email, password_hash($password, PASSWORD_DEFAULT));
                header('Location: login.php');
                exit;
            }
        } catch (Throwable $exception) {
            $errors[] = 'Unable to register right now. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="card shadow-sm w-100" style="max-width: 480px;">
            <div class="card-body p-4">
                <h1 class="h4 mb-4 text-center">Create Member Account</h1>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="name"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input
                            type="password"
                            class="form-control"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account?</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
