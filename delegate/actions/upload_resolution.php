<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté et est un délégué
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delegate') {
    http_response_code(403);
    die('Unauthorized');
}

// Vérifier si tous les champs requis sont présents
if (!isset($_POST['title']) || !isset($_FILES['resolution_file'])) {
    http_response_code(400);
    die('Missing required fields');
}

$title = trim($_POST['title']);
$file = $_FILES['resolution_file'];

// Valider le titre
if (empty($title)) {
    http_response_code(400);
    die('Title cannot be empty');
}

// Valider le fichier
$allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.oasis.opendocument.text'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    die('Invalid file type. Only PDF, DOC, DOCX, and ODT files are allowed.');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer le committee_id du délégué
    $stmt = $pdo->prepare("SELECT committee_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $committee_id = $stmt->fetchColumn();

    if (!$committee_id) {
        throw new Exception('Delegate not assigned to any committee');
    }

    // Commencer la transaction
    $pdo->beginTransaction();

    // 1. Insérer le document
    $upload_dir = '../../uploads/resolutions/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('resolution_') . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to upload file');
    }

    $stmt = $pdo->prepare("
        INSERT INTO documents (
            original_name, 
            file_path, 
            file_type, 
            upload_date, 
            uploaded_by, 
            committee_id,
            document_type
        ) VALUES (?, ?, ?, NOW(), ?, ?, 'resolution')
    ");
    $stmt->execute([
        $file['name'],
        'uploads/resolutions/' . $new_filename,
        $file_extension,
        $_SESSION['user_id'],
        $committee_id
    ]);

    $document_id = $pdo->lastInsertId();

    // 2. Insérer la résolution proposée
    $stmt = $pdo->prepare("
        INSERT INTO purposed_resolutions (
            document_id,
            committee_id,
            delegate_id,
            title,
            status,
            submission_date
        ) VALUES (?, ?, ?, ?, 'draft', NOW())
    ");
    $stmt->execute([
        $document_id,
        $committee_id,
        $_SESSION['user_id'],
        $title
    ]);

    // Valider la transaction
    $pdo->commit();

    // Rediriger avec un message de succès
    $_SESSION['success_message'] = 'Resolution uploaded successfully!';
    header('Location: ../documents.php');
    exit();

} catch(Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Supprimer le fichier si déjà uploadé
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }

    error_log("Error in upload_resolution: " . $e->getMessage());
    http_response_code(500);
    die('An error occurred while uploading the resolution');
}
