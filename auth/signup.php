<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Récupérer et valider les données du formulaire
    $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
    $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $birthdate = filter_input(INPUT_POST, 'birthdate', FILTER_SANITIZE_STRING);
    $termsAccepted = filter_input(INPUT_POST, 'terms_accepted', FILTER_VALIDATE_BOOLEAN);
    
    // Validation des données
    if (!$firstname || !$lastname || !$email || !$password || !$birthdate || !$termsAccepted) {
        throw new Exception('Tous les champs sont obligatoires, y compris l\'acceptation des conditions générales');
    }

    // Validation du format de la date
    $date = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$date || $date->format('Y-m-d') !== $birthdate) {
        throw new Exception('Format de date invalide');
    }

    // Validation du mot de passe
    if (strlen($password) < 8) {
        throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
    }

    // Déterminer le rôle basé sur l'email
    $role = 'delegate'; // Par défaut
    if (strpos($email, 'chair') !== false) {
        $role = 'chair';
    } elseif (strpos($email, 'admin') !== false) {
        $role = 'admin';
    }

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Cet email est déjà utilisé');
    }

    // Hasher le mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insérer le nouvel utilisateur avec des valeurs par défaut pour country_code et committee_id
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, password_hash, birthdate, role, country_code, committee_id) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)");
    $success = $stmt->execute([$firstname, $lastname, $email, $password_hash, $birthdate, $role]);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie',
            'redirect' => 'login.html'
        ]);
    } else {
        throw new Exception('Erreur lors de l\'inscription');
    }

} catch (Exception $e) {
    error_log("Erreur d'inscription : " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Erreur base de données : " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de l\'inscription'
    ]);
}
