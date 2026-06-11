<?php
declare(strict_types=1);

require_once 'db.php';

$userId = 1;
$fines = [];
$totalFines = 0;
$error = '';

function fine_history_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fine_history_rupiah(float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function fine_history_overdue_days(string $dueDate, string $returnDate): int
{
    $due = new DateTimeImmutable($dueDate);
    $returned = new DateTimeImmutable($returnDate);

    return $returned > $due ? $due->diff($returned)->days : 0;
}

try {
    $stmt = $pdo->prepare(
        "SELECT b.title, l.borrow_date, l.due_date, l.return_date, l.fine_amount
         FROM loans l
         INNER JOIN books b ON b.book_id = l.book_id
         WHERE l.user_id = ?
           AND l.status = 'returned'
           AND l.fine_amount > 0
         ORDER BY l.return_date DESC, l.loan_id DESC"
    );
    $stmt->execute([$userId]);
    $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($fines as $fine) {
        $totalFines += (float) $fine['fine_amount'];
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fine History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Fine History</h1>
                <p class="text-muted mb-0">Returned loans with late return fines.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="catalog.php" class="btn btn-outline-primary">Catalog</a>
                <a href="my_loans.php" class="btn btn-primary">My Loans</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= fine_history_text($error) ?></div>
        <?php elseif (!$fines): ?>
            <div class="bg-white border rounded shadow-sm p-5 text-center">
                <h2 class="h4 mb-2">No fines found</h2>
                <p class="text-muted mb-3">You do not have any returned loans with late fees.</p>
                <a href="catalog.php" class="btn btn-outline-primary">Browse Catalog</a>
            </div>
        <?php else: ?>
            <div class="table-responsive bg-white border rounded shadow-sm">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Book Title</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th class="text-end">Days Overdue</th>
                            <th class="text-end">Fine Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fines as $fine): ?>
                            <?php
                            $daysOverdue = fine_history_overdue_days($fine['due_date'], $fine['return_date']);
                            $fineAmount = (float) $fine['fine_amount'];
                            ?>
                            <tr>
                                <td><?= fine_history_text($fine['title']) ?></td>
                                <td><?= fine_history_text($fine['borrow_date']) ?></td>
                                <td><?= fine_history_text($fine['due_date']) ?></td>
                                <td><?= fine_history_text($fine['return_date']) ?></td>
                                <td class="text-end"><?= $daysOverdue ?></td>
                                <td class="text-end"><?= fine_history_rupiah($fineAmount) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total Fines</th>
                            <th class="text-end"><?= fine_history_rupiah($totalFines) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
