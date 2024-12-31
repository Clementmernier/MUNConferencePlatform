<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if committee ID is provided
if (!isset($_POST['committee_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Committee ID is required']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check if there are any users assigned to this committee
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE committee_id = ?");
    $stmt->execute([$_POST['committee_id']]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot delete committee with assigned users']);
        exit();
    }
    
    // Delete the committee
    $stmt = $pdo->prepare("DELETE FROM committees WHERE id = ?");
    $stmt->execute([$_POST['committee_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Committee deleted successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Committee not found']);
    }
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A database error occurred']);
}
