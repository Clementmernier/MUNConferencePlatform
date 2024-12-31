<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et est un président
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'chair') {
    http_response_code(403);
    die('Unauthorized');
}

// Vérifier les paramètres requis
if (!isset($_POST['resolution_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    die('Missing parameters');
}

$resolution_id = $_POST['resolution_id'];
$status = $_POST['status'];

// Valider le statut
$valid_statuses = ['draft', 'discussing', 'adopted', 'rejected'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    die('Invalid status');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier que la résolution appartient au comité du président
    $stmt = $pdo->prepare("
        SELECT pr.* 
        FROM purposed_resolutions pr
        JOIN users u ON u.committee_id = pr.committee_id
        WHERE pr.id = ? AND u.id = ? AND u.role = 'chair'
    ");
    $stmt->execute([$resolution_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        die('Unauthorized access to this resolution');
    }

    // Mettre à jour le statut
    $stmt = $pdo->prepare("
        UPDATE purposed_resolutions 
        SET status = ?
        WHERE id = ?
    ");
    $stmt->execute([$status, $resolution_id]);

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    error_log("Database error in update_resolution_status: " . $e->getMessage());
    http_response_code(500);
    die('Database error occurred');
}
