<?php
/**
 * UniMove Res Essentials — Database Connection (PDO)
 *
 * Provides a single shared $pdo instance using prepared-statement defaults.
 * All queries throughout the project MUST use $pdo->prepare(...) to prevent
 * SQL injection.
 */

require_once __DIR__ . '/config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Generic message — never leak DB details to the user.
    http_response_code(500);
    die('Database connection failed. Please try again later.');
}
