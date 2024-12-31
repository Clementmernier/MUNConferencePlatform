<?php
require_once '../config/database.php';
session_start();

// Vérifier si l'utilisateur est connecté et est un chair
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'chair') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['speaker_id']) || !isset($data['status'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Vérifier que le speaker appartient au comité du chair
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM speakers_list s
        JOIN users u ON u.committee_id = s.committee_id
        WHERE s.id = ? AND u.id = ? AND u.role = 'chair'
    ");
    $stmt->execute([$data['speaker_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Speaker not found or unauthorized');
    }

    // Mettre à jour le statut du speaker
    $updateStmt = $pdo->prepare("UPDATE speakers_list SET status = ? WHERE id = ?");
    $updateStmt->execute([$data['status'], $data['speaker_id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Error in update_speaker_status.php: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
