<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if required data is present
if (!isset($_POST['name']) || !isset($_POST['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute the insert statement
    $stmt = $pdo->prepare("INSERT INTO countries (code, name, full_name, is_member) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        strtoupper($_POST['code']),
        $_POST['name'],
        $_POST['full_name'] ?? $_POST['name'],
        isset($_POST['is_member']) ? 1 : 0
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Country added successfully']);
    
} catch(PDOException $e) {
    error_log("Database error in add_country: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred: ' . $e->getMessage()]);
}
