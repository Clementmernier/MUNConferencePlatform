<?php
require_once '../config/database.php';
session_start();

// Debug
error_log('Session: ' . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté et est un chair
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'chair') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
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

    // Récupérer la liste des speakers
    $stmt = $pdo->prepare("
        SELECT 
            sl.id,
            sl.delegate_id,
            sl.speaking_time,
            sl.status,
            u.firstname,
            u.lastname,
            c.name as country_name
        FROM speakers_list sl
        JOIN users u ON sl.delegate_id = u.id
        JOIN countries c ON u.country_code = c.code
        WHERE sl.committee_id = ?
        ORDER BY sl.created_at ASC
    ");
    $stmt->execute([$chair['committee_id']]);
    $speakers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner la liste en JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'speakers' => $speakers
    ]);

} catch (Exception $e) {
    error_log('Error in get_speakers.php: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
