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

$books = [];
$booksError = '';

function books_db_connection()
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

function books_rows($connection, string $sql): array
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

try {
    $connection = books_db_connection();

    try {
        $books = books_rows(
            $connection,
            'SELECT book_id, title, author, category, isbn, year, stock
             FROM books
             ORDER BY title ASC, book_id ASC'
        );
    } catch (Throwable $firstException) {
        $books = books_rows(
            $connection,
            'SELECT book_id, title, author, category, isbn, publication_year AS year, stock_quantity AS stock
             FROM books
             ORDER BY title ASC, book_id ASC'
        );
    }
} catch (Throwable $exception) {
    $booksError = $exception->getMessage();
}

function books_text($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function books_int($value): int
{
    return (int) $value;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Books</title>
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin_books.php">Manage Books</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-secondary btn-sm" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4 py-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Manage Books</h1>
                <p class="text-muted mb-0">View, add, edit, and delete book records.</p>
            </div>
            <a class="btn btn-primary" href="admin_book_add.php">Add New Book</a>
        </div>

        <?php
        $flashClass = '';
        $flashMessage = '';

        if (isset($_GET['success'])) {
            $successMessages = [
                '1' => 'Book successfully added.',
                '2' => 'Book successfully updated.',
                '3' => 'Book successfully deleted.',
            ];

            if (isset($successMessages[$_GET['success']])) {
                $flashClass = 'alert-success';
                $flashMessage = $successMessages[$_GET['success']];
            }
        } elseif (isset($_GET['error'])) {
            $errorMessages = [
                'has_active_loans' => 'Cannot delete: this book has active loans.',
                'delete_failed' => 'Delete failed. Please try again.',
            ];

            if (isset($errorMessages[$_GET['error']])) {
                $flashClass = 'alert-danger';
                $flashMessage = $errorMessages[$_GET['error']];
            }
        }
        ?>

        <?php if ($flashMessage !== ''): ?>
            <div class="alert <?php echo $flashClass; ?>" role="alert">
                <?php echo books_text($flashMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($booksError !== ''): ?>
            <div class="alert alert-danger" role="alert">
                Unable to load books.
                <span class="d-block small mt-1"><?php echo books_text($booksError); ?></span>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Title</th>
                            <th scope="col">Author</th>
                            <th scope="col">Category</th>
                            <th scope="col">ISBN</th>
                            <th scope="col">Year</th>
                            <th scope="col">Stock</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($books) > 0): ?>
                            <?php foreach ($books as $index => $book): ?>
                                <?php $bookId = books_int($book['book_id'] ?? 0); ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td class="fw-semibold"><?php echo books_text($book['title'] ?? ''); ?></td>
                                    <td><?php echo books_text($book['author'] ?? ''); ?></td>
                                    <td><?php echo books_text($book['category'] ?? ''); ?></td>
                                    <td><?php echo books_text($book['isbn'] ?? ''); ?></td>
                                    <td><?php echo books_text($book['year'] ?? ''); ?></td>
                                    <td><?php echo books_text($book['stock'] ?? ''); ?></td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="admin_book_edit.php?id=<?php echo $bookId; ?>">Edit</a>
                                            <a class="btn btn-sm btn-outline-danger" href="admin_book_delete.php?id=<?php echo $bookId; ?>" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No books found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
