<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez remplir tous les champs'
        ]);
        exit;
    }

    try {
        // Vérifier les identifiants
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Stocker les informations en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_firstname'] = $user['firstname'];
            $_SESSION['user_lastname'] = $user['lastname'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_country'] = $user['country_code'];
            $_SESSION['user_committee'] = $user['committee_id'];

            // Déterminer la redirection
            $redirect = '../delegate/dashboard.php'; // Par défaut
            switch($user['role']) {
                case 'admin':
                    $redirect = '../admin/dashboard.php';
                    break;
                case 'chair':
                    $redirect = '../chair/dashboard.php';
                    break;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'redirect' => $redirect
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Email ou mot de passe incorrect'
            ]);
        }
    } catch (PDOException $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la connexion'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
