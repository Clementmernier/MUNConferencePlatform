<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un délégué
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'delegate') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérifier les données reçues
if (!isset($_POST['delegate_id'], $_POST['committee_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la résolution en cours pour ce comité
    $stmt = $pdo->prepare("
        SELECT id FROM resolutions 
        WHERE committee_id = ? AND status = 'in_debate'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$_POST['committee_id']]);
    $resolution = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resolution) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Aucune résolution en cours de débat']);
        exit();
    }

    // Récupérer et décoder le contenu
    $content = $_POST['content'];
    if (!is_string($content)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Format de contenu invalide']);
        exit();
    }

    // Insérer l'amendement
    $stmt = $pdo->prepare("
        INSERT INTO amendments (committee_id, delegate_id, resolution_id, content) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['committee_id'],
        $_POST['delegate_id'],
        $resolution['id'],
        $content
    ]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Amendement soumis avec succès']);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la soumission: ' . $e->getMessage()]);
    exit();
}
?>
