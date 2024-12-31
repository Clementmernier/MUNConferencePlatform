<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug des sessions
error_log('Session data: ' . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté et est un président
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'chair') {
    header('Location: ../login.php');
    exit;
}

// Vérifier la connexion avec la base de données
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'chair' AND status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userData) {
    // Détruire la session si l'utilisateur n'existe plus ou n'est plus actif
    session_destroy();
    header('Location: ../login.php?error=invalid_session');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chair Dashboard - Model United Nations</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/common_head.php'; ?>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/chair/css/dashboard.css" rel="stylesheet">
    <link href="/chair/css/amendments.css" rel="stylesheet">
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</head>
<body class="bg-light">
    <div class="wrapper"><!-- Début du wrapper -->
