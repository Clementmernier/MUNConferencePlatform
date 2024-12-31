<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'chair') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amendment_id'])) {
    try {
        // Récupérer le committee_id du président
        $stmt = $pdo->prepare("SELECT committee_id FROM users WHERE id = ? AND role = 'chair'");
        $stmt->execute([$_SESSION['user_id']]);
        $committeeId = $stmt->fetchColumn();

        if (!$committeeId) {
            throw new Exception('No committee assigned.');
        }

        // Commencer une transaction
        $pdo->beginTransaction();

        // Réinitialiser tous les amendements en discussion pour ce comité
        $resetStmt = $pdo->prepare("
            UPDATE amendments 
            SET in_discussion = FALSE 
            WHERE committee_id = ?
        ");
        $resetStmt->execute([$committeeId]);

        // Définir le nouvel amendement en cours de discussion
        $updateStmt = $pdo->prepare("
            UPDATE amendments 
            SET in_discussion = TRUE 
            WHERE id = ? AND committee_id = ?
        ");
        $updateStmt->execute([$_POST['amendment_id'], $committeeId]);

        // Valider la transaction
        $pdo->commit();

        header('Location: ../amendments.php?success=1&message=current_updated');
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: ../amendments.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: ../amendments.php');
}
exit;
?>
