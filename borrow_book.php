<?php
declare(strict_types=1);

require_once 'db.php';

try {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if (!$userId || !$bookId) {
        throw new RuntimeException('Invalid user or book.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT stock FROM books WHERE book_id = ? FOR UPDATE');
    $stmt->execute([$bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book || (int) $book['stock'] <= 0) {
        throw new RuntimeException('Book is out of stock.');
    }

    $borrowDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+7 days'));

    $stmt = $pdo->prepare(
        'INSERT INTO loans (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $bookId, $borrowDate, $dueDate, 'borrowed']);

    $stmt = $pdo->prepare('UPDATE books SET stock = stock - 1 WHERE book_id = ? AND stock > 0');
    $stmt->execute([$bookId]);

    $pdo->commit();
    echo 'Book borrowed successfully.';
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo $e->getMessage();
}
