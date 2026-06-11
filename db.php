<?php
declare(strict_types=1);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=isd_library;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . $e->getMessage());
}
