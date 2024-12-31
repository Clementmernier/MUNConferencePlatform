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
if (!isset($_POST['code']) || !isset($_POST['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Prepare and execute the update statement
    $stmt = $pdo->prepare("UPDATE countries SET name = ?, full_name = ?, is_member = ? WHERE code = ?");
    $stmt->execute([
        $_POST['name'],
        $_POST['full_name'] ?? $_POST['name'],
        isset($_POST['is_member']) ? 1 : 0,
        $_POST['code']
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Country not found');
    }
    
    echo json_encode(['success' => true, 'message' => 'Country updated successfully']);
    
} catch(PDOException $e) {
    error_log("Database error in update_country: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Error in update_country: " . $e->getMessage());
    http_response_code(404);
    echo json_encode(['error' => $e->getMessage()]);
}
