<?php
declare(strict_types=1);

require_once 'db.php';

$books = [];
$error = '';

try {
    $stmt = $pdo->query('SELECT book_id, title, author, stock FROM books ORDER BY title');
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

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$books): ?>
            <div class="alert alert-info">No books found.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($books as $book): ?>
                    <?php $stock = (int) $book['stock']; ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h2 class="h5 card-title"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="card-text text-muted mb-2"><?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="mb-3">
                                    <span class="badge <?= $stock > 0 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                        Stock: <?= $stock ?>
                                    </span>
                                </p>
                                <form action="borrow_book.php" method="post" class="mt-auto">
                                    <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                    <input type="hidden" name="user_id" value="1">
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
</body>
</html>
