<?php
declare(strict_types=1);

require_once 'db.php';

$userId = 1;
$result = null;
$error = '';

function return_book_text(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function return_book_rupiah(int $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request method.');
    }

    $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);

    if (!$loanId) {
        throw new RuntimeException('Invalid loan.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT l.book_id, l.user_id, l.due_date, l.status, b.title
         FROM loans l
         INNER JOIN books b ON b.book_id = l.book_id
         WHERE l.loan_id = ?
         FOR UPDATE'
    );
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan || (int) $loan['user_id'] !== $userId || $loan['status'] !== 'borrowed') {
        throw new RuntimeException('Loan is not active.');
    }

    $returnDate = date('Y-m-d');
    $dueDate = new DateTimeImmutable($loan['due_date']);
    $returnedAt = new DateTimeImmutable($returnDate);
    $overdueDays = $returnedAt > $dueDate ? $dueDate->diff($returnedAt)->days : 0;
    $fineAmount = min($overdueDays * 1000, 50000);

    $stmt = $pdo->prepare(
        "UPDATE loans
         SET status = 'returned', return_date = ?, fine_amount = ?
         WHERE loan_id = ?"
    );
    $stmt->execute([$returnDate, $fineAmount, $loanId]);

    $stmt = $pdo->prepare('UPDATE books SET stock = stock + 1 WHERE book_id = ?');
    $stmt->execute([(int) $loan['book_id']]);

    $pdo->commit();

    $result = [
        'title' => (string) $loan['title'],
        'return_date' => $returnDate,
        'overdue_days' => $overdueDays,
        'fine_amount' => $fineAmount,
    ];
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    $error = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Return Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="mx-auto" style="max-width: 720px;">
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm" role="alert">
                    <h1 class="h4 alert-heading">Unable to return book</h1>
                    <p class="mb-0"><?= return_book_text($error) ?></p>
                </div>
                <a href="my_loans.php" class="btn btn-outline-secondary">Back to My Loans</a>
            <?php elseif ($result): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h1 class="h4 mb-0">Book Returned Successfully</h1>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Book Title</dt>
                            <dd class="col-sm-8"><?= return_book_text($result['title']) ?></dd>

                            <dt class="col-sm-4">Return Date</dt>
                            <dd class="col-sm-8"><?= return_book_text($result['return_date']) ?></dd>

                            <dt class="col-sm-4">Days Overdue</dt>
                            <dd class="col-sm-8">
                                <?php if ($result['overdue_days'] > 0): ?>
                                    <?= (int) $result['overdue_days'] ?> day<?= $result['overdue_days'] === 1 ? '' : 's' ?>
                                <?php else: ?>
                                    None
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Fine Amount</dt>
                            <dd class="col-sm-8 fw-semibold"><?= return_book_rupiah((int) $result['fine_amount']) ?></dd>
                        </dl>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="my_loans.php" class="btn btn-primary">Back to My Loans</a>
                        <a href="catalog.php" class="btn btn-outline-secondary">Browse Catalog</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
