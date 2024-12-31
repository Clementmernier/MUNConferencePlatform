<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un président
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'chair') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer le comité du président
    $stmt = $pdo->prepare("SELECT committee_id FROM users WHERE id = ? AND role = 'chair'");
    $stmt->execute([$_SESSION['user_id']]);
    $committeeId = $stmt->fetchColumn();

    if (!$committeeId) {
        throw new Exception('No committee assigned.');
    }

    // Récupérer les amendements
    $amendmentsStmt = $pdo->prepare("
        SELECT a.*, u.firstname, u.lastname, 
               c.name as country_name, c.code as country_code,
               r.link as resolution_link
        FROM amendments a
        JOIN users u ON a.delegate_id = u.id
        JOIN countries c ON u.country_code = c.code
        JOIN resolutions r ON a.resolution_id = r.id
        WHERE a.committee_id = ?
        ORDER BY a.in_discussion DESC, a.created_at DESC
    ");
    $amendmentsStmt->execute([$committeeId]);
    $amendments = $amendmentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates pour l'affichage
    foreach ($amendments as &$amendment) {
        $amendment['formatted_date'] = date('F j, Y, g:i a', strtotime($amendment['created_at']));
    }

    echo json_encode(['success' => true, 'amendments' => $amendments]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
