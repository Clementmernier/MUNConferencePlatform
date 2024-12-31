<?php
require_once '../config/database.php';
session_start();

// Debug
error_log('Session: ' . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté et est un chair
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chair') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Vérifier si les données nécessaires sont présentes
if (!isset($_POST['delegateId']) || !isset($_POST['speakingTime'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
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

    // Vérifier si le délégué appartient au même comité
    $stmt = $pdo->prepare("
        SELECT u.id, u.firstname, u.lastname, c.name as country_name
        FROM users u
        JOIN countries c ON u.country_code = c.code
        WHERE u.id = ? AND u.committee_id = ? AND u.role = 'delegate'
    ");
    $stmt->execute([$_POST['delegateId'], $chair['committee_id']]);
    $delegate = $stmt->fetch();

    if (!$delegate) {
        throw new Exception('Delegate not found or not in your committee');
    }

    // Ajouter le speaker à la liste
    $stmt = $pdo->prepare("
        INSERT INTO speakers_list (committee_id, delegate_id, speaking_time, status, created_at)
        VALUES (?, ?, ?, 'waiting', NOW())
    ");
    $stmt->execute([
        $chair['committee_id'],
        $_POST['delegateId'],
        $_POST['speakingTime']
    ]);

    // Retourner les informations du speaker ajouté
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'speaker' => [
            'id' => $pdo->lastInsertId(),
            'delegate_id' => $delegate['id'],
            'firstname' => $delegate['firstname'],
            'lastname' => $delegate['lastname'],
            'country_name' => $delegate['country_name'],
            'speaking_time' => $_POST['speakingTime'],
            'status' => 'waiting'
        ]
    ]);

} catch (Exception $e) {
    error_log('Error in add_speaker.php: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
