<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un délégué
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'delegate') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=fonmunsimulator", "root", "mylocalserver");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si un lien Google Docs a été soumis
    if (!empty($_POST['resolution_link'])) {
        $resolutionLink = $_POST['resolution_link'];
        
        // Commencer une transaction
        $pdo->beginTransaction();
        
        try {
            // Insérer dans la table documents
            $query = "INSERT INTO documents (name, original_name, file_path, file_type, file_size, uploaded_by, committee_id, upload_date, document_type) 
                     VALUES (:name, :original_name, :file_path, 'gdoc', 0, :uploaded_by, :committee_id, NOW(), 'resolution')";
            
            $stmt = $pdo->prepare($query);
            $documentName = "Resolution_" . date('Y-m-d_H-i-s');
            
            $stmt->execute([
                ':name' => $documentName,
                ':original_name' => $documentName,
                ':file_path' => $resolutionLink,
                ':uploaded_by' => $_SESSION['user_id'],
                ':committee_id' => $_POST['committee_id']
            ]);
            
            // Récupérer l'ID du document
            $documentId = $pdo->lastInsertId();
            
            // Insérer dans la table purposed_resolutions
            $query = "INSERT INTO purposed_resolutions (document_id, committee_id, delegate_id, title, status, submission_date) 
                     VALUES (:document_id, :committee_id, :delegate_id, :title, 'draft', NOW())";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                ':document_id' => $documentId,
                ':committee_id' => $_POST['committee_id'],
                ':delegate_id' => $_SESSION['user_id'],
                ':title' => $documentName
            ]);
            
            // Valider la transaction
            $pdo->commit();
            
            $_SESSION['success'] = "Lien de résolution soumis avec succès";
            
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            throw $e;
        }
    } else {
        $_SESSION['error'] = "Aucun lien n'a été soumis";
    }
} catch(PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la soumission : " . $e->getMessage();
} catch(Exception $e) {
    error_log("General Error: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
}

// Rediriger vers la page du tableau de bord
header('Location: dashboard.php');
exit();
?>
