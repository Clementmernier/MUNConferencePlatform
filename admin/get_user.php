<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);

try {
    if (!isset($_GET['id'])) {
        throw new Exception('User ID is required');
    }

    $userId = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if (!$userId) {
        throw new Exception('Invalid user ID');
    }

    $stmt = $pdo->prepare("
        SELECT u.*, c.name as committee_name 
        FROM users u 
        LEFT JOIN committees c ON u.committee_id = c.id 
        WHERE u.id = ?
    ");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found');
    }

    // Remove sensitive data
    unset($user['password_hash']);
    
    echo json_encode(['success' => true, 'data' => $user]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
