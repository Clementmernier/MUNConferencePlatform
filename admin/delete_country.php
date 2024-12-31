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

// Check if country code is provided
if (!isset($_POST['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Country code is required']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if country is being used by any users
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE country_code = ?");
    $stmt->execute([$_POST['code']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete country: it is assigned to one or more users');
    }
    
    // Delete the country
    $stmt = $pdo->prepare("DELETE FROM countries WHERE code = ?");
    $stmt->execute([$_POST['code']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Country not found');
    }
    
    echo json_encode(['success' => true, 'message' => 'Country deleted successfully']);
    
} catch(PDOException $e) {
    error_log("Database error in delete_country: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Error in delete_country: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
