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

    // Récupérer tous les délégués du comité
    $stmt = $pdo->prepare("
        SELECT u.id, u.email as username, u.firstname, u.lastname, c.name as country_name
        FROM users u
        JOIN countries c ON u.country_code = c.code
        WHERE u.committee_id = ? AND u.role = 'delegate'
        ORDER BY c.name ASC
    ");
    $stmt->execute([$chair['committee_id']]);
    $delegates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retourner les données en JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'delegates' => $delegates
    ]);

} catch (Exception $e) {
    error_log('Error in get_delegates.php: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
