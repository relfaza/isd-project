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

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$bookId) {
    header('Location: admin_books.php');
    exit;
}

$errors = [];
$formError = '';
$notFound = false;
$form = [
    'title' => '',
    'author' => '',
    'category' => '',
    'isbn' => '',
    'publication_year' => '',
    'description' => '',
    'stock' => '',
];

function edit_book_pdo(): PDO
{
    foreach (['pdo', 'db', 'conn'] as $name) {
        if (isset($GLOBALS[$name]) && $GLOBALS[$name] instanceof PDO) {
            return $GLOBALS[$name];
        }
    }

    throw new RuntimeException('PDO database connection not found. Expected $pdo, $db, or $conn from db.php.');
}

function edit_book_text($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function edit_book_load(PDO $pdo, int $bookId): ?array
{
    $statement = $pdo->prepare(
        'SELECT book_id, title, author, category, isbn, publication_year, description, stock
         FROM books
         WHERE book_id = :book_id
         LIMIT 1'
    );
    $statement->execute([':book_id' => $bookId]);
    $book = $statement->fetch(PDO::FETCH_ASSOC);

    return $book ?: null;
}

try {
    $pdo = edit_book_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($form as $field => $value) {
            $form[$field] = trim($_POST[$field] ?? '');
        }

        if ($form['title'] === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($form['author'] === '') {
            $errors['author'] = 'Author is required.';
        }

        if ($form['stock'] === '') {
            $errors['stock'] = 'Stock is required.';
        } elseif (!ctype_digit($form['stock'])) {
            $errors['stock'] = 'Stock must be a whole number.';
        }

        if (
            $form['publication_year'] !== '' &&
            !preg_match('/^\d{4}$/', $form['publication_year'])
        ) {
            $errors['publication_year'] = 'Publication year must be 4 digits.';
        }

        if (empty($errors)) {
            $statement = $pdo->prepare(
                'UPDATE books
                 SET title = :title,
                     author = :author,
                     category = :category,
                     isbn = :isbn,
                     publication_year = :publication_year,
                     description = :description,
                     stock = :stock
                 WHERE book_id = :book_id'
            );

            $statement->execute([
                ':title' => $form['title'],
                ':author' => $form['author'],
                ':category' => $form['category'] !== '' ? $form['category'] : null,
                ':isbn' => $form['isbn'] !== '' ? $form['isbn'] : null,
                ':publication_year' => $form['publication_year'] !== '' ? (int) $form['publication_year'] : null,
                ':description' => $form['description'] !== '' ? $form['description'] : null,
                ':stock' => (int) $form['stock'],
                ':book_id' => $bookId,
            ]);

            header('Location: admin_books.php?success=2');
            exit;
        }
    } else {
        $book = edit_book_load($pdo, $bookId);

        if ($book === null) {
            $notFound = true;
        } else {
            foreach ($form as $field => $value) {
                $form[$field] = (string) ($book[$field] ?? '');
            }
        }
    }
} catch (Throwable $exception) {
    $formError = $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Book</title>
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
                <h1 class="h3 mb-1">Edit Book</h1>
                <p class="text-muted mb-0">Update catalog record details.</p>
            </div>
            <a class="btn btn-outline-secondary" href="admin_books.php">Back to Book List</a>
        </div>

        <?php if ($notFound): ?>
            <div class="alert alert-warning" role="alert">
                Book not found.
            </div>
            <a class="btn btn-outline-secondary" href="admin_books.php">Back to Book List</a>
        <?php else: ?>
            <?php if ($formError !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    Unable to save book.
                    <span class="d-block small mt-1"><?php echo edit_book_text($formError); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-warning" role="alert">
                    Please fix the highlighted fields before saving.
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="post" action="admin_book_edit.php?id=<?php echo $bookId; ?>" novalidate>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control<?php echo isset($errors['title']) ? ' is-invalid' : ''; ?>" id="title" name="title" value="<?php echo edit_book_text($form['title']); ?>" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback"><?php echo edit_book_text($errors['title']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="author" class="form-label">Author <span class="text-danger">*</span></label>
                                <input type="text" class="form-control<?php echo isset($errors['author']) ? ' is-invalid' : ''; ?>" id="author" name="author" value="<?php echo edit_book_text($form['author']); ?>" required>
                                <?php if (isset($errors['author'])): ?>
                                    <div class="invalid-feedback"><?php echo edit_book_text($errors['author']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control" id="category" name="category" value="<?php echo edit_book_text($form['category']); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo edit_book_text($form['isbn']); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="publication_year" class="form-label">Publication Year</label>
                                <input type="number" class="form-control<?php echo isset($errors['publication_year']) ? ' is-invalid' : ''; ?>" id="publication_year" name="publication_year" min="1000" max="9999" value="<?php echo edit_book_text($form['publication_year']); ?>">
                                <?php if (isset($errors['publication_year'])): ?>
                                    <div class="invalid-feedback"><?php echo edit_book_text($errors['publication_year']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                                <input type="number" class="form-control<?php echo isset($errors['stock']) ? ' is-invalid' : ''; ?>" id="stock" name="stock" min="0" value="<?php echo edit_book_text($form['stock']); ?>" required>
                                <?php if (isset($errors['stock'])): ?>
                                    <div class="invalid-feedback"><?php echo edit_book_text($errors['stock']); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo edit_book_text($form['description']); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end mt-4">
                            <a class="btn btn-outline-secondary" href="admin_books.php">Back to Book List</a>
                            <button type="submit" class="btn btn-primary">Update Book</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
