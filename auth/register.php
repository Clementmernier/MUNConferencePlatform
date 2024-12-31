<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer la liste des pays
$stmt = $db->prepare("SELECT code, name FROM countries ORDER BY name");
$stmt->execute();
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = new User($db);
$errors = [];
$success = false;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des champs
    if (empty($_POST['firstname'])) {
        $errors[] = "Le prénom est requis";
    }
    if (empty($_POST['lastname'])) {
        $errors[] = "Le nom est requis";
    }
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
    }
    if (empty($_POST['committee'])) {
        $errors[] = "Le comité est requis";
    }
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($errors)) {
        // Vérifier si c'est le premier utilisateur (ID 1)
        $stmt = $db->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $userCount = $stmt->fetchColumn();
        
        $role = ($userCount == 0) ? 'admin' : 'delegate';

        // Insérer le nouvel utilisateur
        $stmt = $db->prepare("INSERT INTO users (firstname, lastname, email, password_hash, role, country_code, committee, status) 
                             VALUES (:firstname, :lastname, :email, :password_hash, :role, :country_code, :committee, :status)");
        
        $stmt->bindParam(':firstname', $_POST['firstname']);
        $stmt->bindParam(':lastname', $_POST['lastname']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':password_hash', password_hash($_POST['password'], PASSWORD_DEFAULT));
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':country_code', $_POST['country']);
        $stmt->bindParam(':committee', $_POST['committee']);
        $stmt->bindParam(':status', 'active');

        if ($stmt->execute()) {
            $success = true;
            $message = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        } else {
            $message = "Une erreur est survenue lors de la création du compte.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - MUN Simulator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Inscription MUN</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <br>
                    <a href="login.php" class="alert-link">Se connecter</a>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstname" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="firstname" name="firstname" required
                                value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>">
                            <div class="invalid-feedback">
                                Veuillez entrer votre prénom
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="lastname" name="lastname" required
                                value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>">
                            <div class="invalid-feedback">
                                Veuillez entrer votre nom
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <div class="invalid-feedback">
                            Veuillez entrer une adresse email valide
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="committee" class="form-label">Comité</label>
                        <select class="form-select" id="committee" name="committee" required>
                            <option value="">Sélectionner un comité</option>
                            <option value="UNSC">Conseil de sécurité</option>
                            <option value="UNHRC">Conseil des droits de l'homme</option>
                            <option value="UNGA">Assemblée générale</option>
                            <option value="ECOSOC">Conseil économique et social</option>
                        </select>
                        <div class="invalid-feedback">
                            Veuillez sélectionner un comité
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="country" class="form-label">Pays</label>
                        <select class="form-select select2" id="country" name="country" style="width: 100%">
                            <option value="">Sélectionner un pays</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country['code']); ?>">
                                    <?php echo htmlspecialchars($country['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Veuillez entrer un mot de passe
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">
                            Veuillez confirmer votre mot de passe
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                </form>

                <div class="text-center mt-3">
                    <a href="login.php">Déjà inscrit ? Se connecter</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Sélectionner un pays'
            });
        });

        // Validation Bootstrap
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
