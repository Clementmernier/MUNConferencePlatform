<?php
require_once '../config/database.php';
session_start();

// Vérifier si l'utilisateur est connecté et est un chair
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chair') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Vérifier si l'ID du speaker est présent
if (!isset($_POST['speakerId'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Speaker ID is required']);
    exit;
}

try {
    // Récupérer l'ID du comité du chair
    $stmt = $pdo->prepare("SELECT committee_id FROM users WHERE id = ? AND role = 'chair'");
    $stmt->execute([$_SESSION['user_id']]);
    $chair = $stmt->fetch();

    if (!$chair) {
        throw new Exception('Chair not found');
    }

    // Vérifier si le speaker appartient au comité du chair
    $stmt = $pdo->prepare("
        SELECT id FROM speakers_list 
        WHERE id = ? AND committee_id = ?
    ");
    $stmt->execute([$_POST['speakerId'], $chair['committee_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Speaker not found or not in your committee');
    }

    // Supprimer le speaker
    $stmt = $pdo->prepare("DELETE FROM speakers_list WHERE id = ?");
    $stmt->execute([$_POST['speakerId']]);

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
