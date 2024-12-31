<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fonmunsimulator');
define('DB_USER', 'root');
define('DB_PASSWORD', 'mylocalserver');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?>
