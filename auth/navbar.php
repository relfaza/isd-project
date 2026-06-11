<?php
$loggedInUserName = $loggedInUserName ?? ($_SESSION['name'] ?? 'User');
$loggedInUserRole = $loggedInUserRole ?? ($_SESSION['role'] ?? '');
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= $loggedInUserRole === 'admin' ? '../admin/dashboard.php' : '../catalog.php' ?>">
            Library Management
        </a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($loggedInUserRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/books.php">Manage Books</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/loans.php">All Loans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/members.php">Members</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../catalog.php">Catalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../my_loans.php">My Loans</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text">
                    <?= htmlspecialchars($loggedInUserName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
