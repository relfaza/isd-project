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

function delete_book_redirect(string $queryString = ''): void
{
    $location = 'admin_books.php';
    if ($queryString !== '') {
        $location .= '?' . $queryString;
    }

    header('Location: ' . $location);
    exit;
}

function delete_book_pdo(): PDO
{
    foreach (['pdo', 'db', 'conn'] as $name) {
        if (isset($GLOBALS[$name]) && $GLOBALS[$name] instanceof PDO) {
            return $GLOBALS[$name];
        }
    }

    throw new RuntimeException('PDO database connection not found.');
}

$bookId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$bookId) {
    delete_book_redirect();
}

try {
    $pdo = delete_book_pdo();

    $activeLoanStatement = $pdo->prepare(
        "SELECT COUNT(*)
         FROM loans
         WHERE book_id = :book_id
           AND status = 'borrowed'"
    );
    $activeLoanStatement->execute([':book_id' => $bookId]);
    $activeLoanCount = (int) $activeLoanStatement->fetchColumn();

    if ($activeLoanCount > 0) {
        delete_book_redirect('error=has_active_loans');
    }

    $deleteStatement = $pdo->prepare(
        'DELETE FROM books
         WHERE book_id = :book_id'
    );
    $deleteStatement->execute([':book_id' => $bookId]);

    delete_book_redirect('success=3');
} catch (Throwable $exception) {
    delete_book_redirect('error=delete_failed');
}
