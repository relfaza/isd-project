<?php
declare(strict_types=1);

require_once 'db.php';

try {
    $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);

    if (!$loanId) {
        throw new RuntimeException('Invalid loan.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT book_id, status FROM loans WHERE loan_id = ? FOR UPDATE');
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan || $loan['status'] !== 'borrowed') {
        throw new RuntimeException('Loan is not active.');
    }

    $stmt = $pdo->prepare(
        "UPDATE loans SET status = 'returned', return_date = CURDATE() WHERE loan_id = ?"
    );
    $stmt->execute([$loanId]);

    $stmt = $pdo->prepare('UPDATE books SET stock = stock + 1 WHERE book_id = ?');
    $stmt->execute([(int) $loan['book_id']]);

    $pdo->commit();
    echo 'Book returned successfully.';
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo $e->getMessage();
}
