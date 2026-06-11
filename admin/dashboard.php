<?php
session_start();

require_once '../db.php';
require_once '../auth/auth_check.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Database connection not found. Expected PDO $pdo from db.php.');
}

require_admin();

$currentUser = current_user($pdo);

if (!$currentUser) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$loggedInUserName = $currentUser['name'];
$loggedInUserRole = $currentUser['role'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once '../auth/navbar.php'; ?>

    <main class="container py-5">
        <div class="mb-4">
            <h1 class="h3 mb-2">Welcome, <?= htmlspecialchars($loggedInUserName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-muted mb-0">Use the admin dashboard to manage books, members, and loan records.</p>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <a class="card h-100 text-decoration-none text-dark shadow-sm" href="books.php">
                    <div class="card-body">
                        <h2 class="h5">Manage Books</h2>
                        <p class="mb-0 text-muted">Create, update, and review library book records.</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a class="card h-100 text-decoration-none text-dark shadow-sm" href="loans.php">
                    <div class="card-body">
                        <h2 class="h5">View All Loans</h2>
                        <p class="mb-0 text-muted">Monitor active borrowings, returns, due dates, and fines.</p>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a class="card h-100 text-decoration-none text-dark shadow-sm" href="members.php">
                    <div class="card-body">
                        <h2 class="h5">View Members</h2>
                        <p class="mb-0 text-muted">Review registered member accounts and borrowing history.</p>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
