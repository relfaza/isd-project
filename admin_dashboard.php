<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$dashboardError = '';

function dashboard_db_connection()
{
    foreach (['pdo', 'db', 'conn', 'mysqli'] as $name) {
        if (
            isset($GLOBALS[$name]) &&
            ($GLOBALS[$name] instanceof PDO || $GLOBALS[$name] instanceof mysqli)
        ) {
            return $GLOBALS[$name];
        }
    }

    return null;
}

function dashboard_scalar($connection, string $sql): int
{
    if ($connection instanceof PDO) {
        $statement = $connection->prepare($sql);
        $statement->execute();
        return (int) $statement->fetchColumn();
    }

    if ($connection instanceof mysqli) {
        $statement = $connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException($connection->error);
        }

        $statement->execute();
        $value = 0;
        $statement->bind_result($value);
        $statement->fetch();
        $statement->close();

        return (int) $value;
    }

    throw new RuntimeException('Database connection not found. Expected $pdo, $db, $conn, or $mysqli from db.php.');
}

function dashboard_rows($connection, string $sql): array
{
    if ($connection instanceof PDO) {
        $statement = $connection->prepare($sql);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($connection instanceof mysqli) {
        $statement = $connection->prepare($sql);
        if (!$statement) {
            throw new RuntimeException($connection->error);
        }

        $statement->execute();
        $metadata = $statement->result_metadata();
        $rows = [];

        if ($metadata) {
            $row = [];
            $bindings = [];

            while ($field = $metadata->fetch_field()) {
                $row[$field->name] = null;
                $bindings[] = &$row[$field->name];
            }

            call_user_func_array([$statement, 'bind_result'], $bindings);

            while ($statement->fetch()) {
                $rows[] = array_map(static function ($value) {
                    return $value;
                }, $row);
            }

            $metadata->free();
        }

        $statement->close();

        return $rows;
    }

    throw new RuntimeException('Database connection not found. Expected $pdo, $db, $conn, or $mysqli from db.php.');
}

$stats = [
    'total_books' => 0,
    'total_stock' => 0,
    'active_loans' => 0,
    'total_loans' => 0,
];
$recentLoans = [];

try {
    $connection = dashboard_db_connection();

    $stats['total_books'] = dashboard_scalar($connection, 'SELECT COUNT(*) FROM books');
    $stats['total_stock'] = dashboard_scalar($connection, 'SELECT COALESCE(SUM(stock), 0) FROM books');
    $stats['active_loans'] = dashboard_scalar($connection, "SELECT COUNT(*) FROM loans WHERE status = 'borrowed'");
    $stats['total_loans'] = dashboard_scalar($connection, 'SELECT COUNT(*) FROM loans');
    $recentLoans = dashboard_rows(
        $connection,
        'SELECT loans.loan_id, books.title AS book_title, loans.user_id, loans.borrow_date, loans.due_date, loans.status
         FROM loans
         INNER JOIN books ON books.book_id = loans.book_id
         ORDER BY loans.borrow_date DESC, loans.loan_id DESC
         LIMIT 10'
    );
} catch (Throwable $exception) {
    $dashboardError = $exception->getMessage();
}

function dashboard_number(int $value): string
{
    return number_format($value);
}

function dashboard_text($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="admin_dashboard.php">Library Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_books.php">Manage Books</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-secondary btn-sm" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Admin Dashboard</h1>
                <p class="text-muted mb-0">Catalog stock and borrowing summary.</p>
            </div>
        </div>

        <?php if ($dashboardError !== ''): ?>
            <div class="alert alert-danger" role="alert">
                Unable to load dashboard statistics.
                <span class="d-block small mt-1"><?php echo htmlspecialchars($dashboardError, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <div class="row g-3 g-lg-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Total Books</div>
                        <div class="display-6 fw-semibold"><?php echo dashboard_number($stats['total_books']); ?></div>
                        <div class="text-muted small mt-2">Books in the catalog</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Available Stock</div>
                        <div class="display-6 fw-semibold"><?php echo dashboard_number($stats['total_stock']); ?></div>
                        <div class="text-muted small mt-2">Total copies available</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Active Loans</div>
                        <div class="display-6 fw-semibold"><?php echo dashboard_number($stats['active_loans']); ?></div>
                        <div class="text-muted small mt-2">Loans currently borrowed</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold mb-2">Total Loans</div>
                        <div class="display-6 fw-semibold"><?php echo dashboard_number($stats['total_loans']); ?></div>
                        <div class="text-muted small mt-2">Loans ever recorded</div>
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-4 mt-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Recent Activity</h2>
                    <p class="text-muted mb-0">Last 10 loan records.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Loan ID</th>
                                <th scope="col">Book Title</th>
                                <th scope="col">User ID</th>
                                <th scope="col">Borrow Date</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentLoans) > 0): ?>
                                <?php foreach ($recentLoans as $loan): ?>
                                    <tr>
                                        <td><?php echo dashboard_text($loan['loan_id'] ?? ''); ?></td>
                                        <td><?php echo dashboard_text($loan['book_title'] ?? ''); ?></td>
                                        <td><?php echo dashboard_text($loan['user_id'] ?? ''); ?></td>
                                        <td><?php echo dashboard_text($loan['borrow_date'] ?? ''); ?></td>
                                        <td><?php echo dashboard_text($loan['due_date'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge text-bg-secondary">
                                                <?php echo dashboard_text($loan['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No recent loans found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
