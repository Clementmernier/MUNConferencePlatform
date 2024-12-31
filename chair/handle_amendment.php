<?php
// Supprimer toute sortie précédente
ob_clean();
ob_start();

// Désactiver l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');

// Debug: Enregistrer les données reçues
error_log("POST data: " . print_r($_POST, true));

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Démarrer la session
session_start();

try {
    // Debug: Enregistrer l'état de la session
    error_log("Session data: " . print_r($_SESSION, true));

    // Vérifier l'authentification
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'chair') {
        throw new Exception('Unauthorized access');
    }

    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Récupérer et valider les paramètres
    $amendmentId = filter_input(INPUT_POST, 'amendment_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    // Debug: Enregistrer les paramètres validés
    error_log("Validated parameters - amendmentId: $amendmentId, action: $action");

    if (!$amendmentId || !in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid parameters');
    }

    // Vérifier que l'amendement existe et appartient au comité du président
    $stmt = $pdo->prepare("
        SELECT a.* FROM amendments a
        JOIN users u ON u.committee_id = a.committee_id
        WHERE a.id = ? AND u.id = ? AND u.role = 'chair'
    ");
    $stmt->execute([$amendmentId, $_SESSION['user_id']]);
    $amendment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Enregistrer le résultat de la requête
    error_log("Amendment query result: " . print_r($amendment, true));

    if (!$amendment) {
        throw new Exception('Amendment not found or unauthorized');
    }

    // Mettre à jour le statut de l'amendement
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $updateStmt = $pdo->prepare("
        UPDATE amendments 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$status, $amendmentId]);

    // Préparer la réponse
    $debug = [
        'post' => $_POST,
        'session' => $_SESSION,
        'method' => $_SERVER['REQUEST_METHOD'],
        'params' => [
            'amendmentId' => $amendmentId,
            'action' => $action
        ],
        'amendment' => $amendment
    ];

    $response = [
        'success' => true,
        'message' => 'Amendment ' . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully',
        'debug' => $debug
    ];

} catch (Exception $e) {
    error_log("Exception caught: " . $e->getMessage());
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'post' => $_POST,
            'session' => $_SESSION,
            'method' => $_SERVER['REQUEST_METHOD'],
            'error' => $e->getMessage()
        ]
    ];
} catch (PDOException $e) {
    error_log("PDO Exception caught: " . $e->getMessage());
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Database error',
        'debug' => [
            'post' => $_POST,
            'session' => $_SESSION,
            'method' => $_SERVER['REQUEST_METHOD'],
            'error' => $e->getMessage()
        ]
    ];
}

// Debug: Enregistrer la réponse avant l'envoi
$jsonResponse = json_encode($response);
error_log("Final JSON response: " . $jsonResponse);

// Nettoyer toute sortie précédente
while (ob_get_level()) {
    ob_end_clean();
}

// Vérifier si le JSON est valide
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON encode error: " . json_last_error_msg());
    http_response_code(500);
    $jsonResponse = json_encode([
        'success' => false,
        'message' => 'Internal server error: Invalid JSON response'
    ]);
}

// Envoyer la réponse JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
echo $jsonResponse;
exit;
