<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: documents.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $document = $stmt->fetch();

    if (!$document) {
        header('Location: documents.php');
        exit();
    }

    $file = $document['filepath'];
    
    if (!file_exists($file)) {
        $_SESSION['error'] = "Le fichier n'existe plus sur le serveur.";
        header('Location: documents.php');
        exit();
    }

    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($document['filename']) . '"');
    header('Content-Length: ' . filesize($file));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Lire et envoyer le fichier
    readfile($file);
    exit();

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors du téléchargement du fichier.";
    header('Location: documents.php');
    exit();
}
