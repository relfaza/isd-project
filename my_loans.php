<?php
declare(strict_types=1);

require_once 'db.php';

$userId = 1;
$loans = [];
$error = '';

try {
    $stmt = $pdo->prepare(
        "SELECT l.loan_id, b.title, l.borrow_date, l.due_date
         FROM loans l
         JOIN books b ON b.book_id = l.book_id
         WHERE l.user_id = ? AND l.status = 'borrowed'
         ORDER BY l.due_date"
    );
    $stmt->execute([$userId]);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Loans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <h1 class="h3 mb-4">My Active Loans</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php elseif (!$loans): ?>
            <div class="alert alert-info">No active loans found.</div>
        <?php else: ?>
            <div class="table-responsive bg-white border rounded shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?= htmlspecialchars($loan['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($loan['borrow_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($loan['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <form action="return_book.php" method="post">
                                        <input type="hidden" name="loan_id" value="<?= (int) $loan['loan_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Return Book</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
