<?php
declare(strict_types=1);

require_once 'db.php';

$books = [];
$categories = [];
$error = '';
$userId = 1;

$search = trim((string) filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW));
$availability = (string) filter_input(INPUT_GET, 'availability', FILTER_UNSAFE_RAW);
$category = trim((string) filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW));

if (!in_array($availability, ['available', 'unavailable'], true)) {
    $availability = '';
}

function catalog_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function catalog_like_value(string $value): string
{
    return '%' . addcslashes($value, "\\%_") . '%';
}

try {
    $categoryStmt = $pdo->query(
        "SELECT DISTINCT category
         FROM books
         WHERE category IS NOT NULL AND category <> ''
         ORDER BY category"
    );
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(LOWER(title) LIKE LOWER(?) ESCAPE '\\\\' OR LOWER(author) LIKE LOWER(?) ESCAPE '\\\\')";
        $searchTerm = catalog_like_value($search);
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($availability === 'available') {
        $where[] = 'stock > 0';
    } elseif ($availability === 'unavailable') {
        $where[] = 'stock = 0';
    }

    if ($category !== '') {
        $where[] = 'category = ?';
        $params[] = $category;
    }

    $sql = 'SELECT book_id, title, author, category, stock FROM books';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY title';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Book Catalog</h1>
        </div>

        <form method="get" action="catalog.php" class="bg-white border rounded shadow-sm p-3 mb-4" id="catalogFilterForm">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-5">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="search"
                        class="form-control"
                        id="search"
                        name="search"
                        placeholder="Search by title or author"
                        value="<?= catalog_text($search) ?>"
                    >
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="availability" class="form-label">Availability</label>
                    <select class="form-select" id="availability" name="availability">
                        <option value="" <?= $availability === '' ? 'selected' : '' ?>>All Books</option>
                        <option value="available" <?= $availability === 'available' ? 'selected' : '' ?>>Available Only</option>
                        <option value="unavailable" <?= $availability === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="" <?= $category === '' ? 'selected' : '' ?>>All Categories</option>
                        <?php foreach ($categories as $categoryOption): ?>
                            <option value="<?= catalog_text((string) $categoryOption) ?>" <?= $category === (string) $categoryOption ? 'selected' : '' ?>>
                                <?= catalog_text((string) $categoryOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-1 d-grid">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= catalog_text($error) ?></div>
        <?php else: ?>
            <p class="text-muted mb-3">Showing <?= count($books) ?> books</p>
        <?php endif; ?>

        <?php if (!$error && !$books): ?>
            <div class="bg-white border rounded shadow-sm p-5 text-center">
                <h2 class="h4 mb-2">No matching books found</h2>
                <p class="text-muted mb-3">Try adjusting your search terms or filters to see more catalog results.</p>
                <a href="catalog.php" class="btn btn-outline-primary">Clear Filters</a>
            </div>
        <?php elseif (!$error): ?>
            <div class="row g-3">
                <?php foreach ($books as $book): ?>
                    <?php $stock = (int) $book['stock']; ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 card-title"><?= catalog_text($book['title']) ?></h2>
                                <p class="card-text text-muted mb-2"><?= catalog_text($book['author']) ?></p>
                                <?php if (!empty($book['category'])): ?>
                                    <p class="mb-2">
                                        <span class="badge text-bg-light border"><?= catalog_text($book['category']) ?></span>
                                    </p>
                                <?php endif; ?>
                                <p class="mb-3">
                                    <span class="badge <?= $stock > 0 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        Stock: <?= $stock ?>
                                    </span>
                                </p>
                                <form action="borrow_book.php" method="post" class="mt-auto">
                                    <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                                    <button type="submit" class="btn btn-primary w-100" <?= $stock <= 0 ? 'disabled' : '' ?>>
                                        Borrow
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <script>
        document.querySelectorAll('#availability, #category').forEach((field) => {
            field.addEventListener('change', () => {
                document.getElementById('catalogFilterForm').submit();
            });
        });
    </script>
</body>
</html>
