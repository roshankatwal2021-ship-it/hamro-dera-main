<?php
// Database configuration
$host = '127.0.0.1';
$db   = 'hamro_dera';
$user = 'root'; // Update with your database username
$pass = '';     // Update with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Base URL configuration (update according to your local setup, e.g., http://localhost/hamro-dera-main/hamro-dera-main)
define('BASE_URL', 'http://localhost/hamro-dera-main/hamro-dera-main');
?>
