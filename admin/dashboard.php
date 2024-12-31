<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les statistiques
    $stats = [];

    // Nombre total d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre de délégués
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'delegate'");
    $stats['total_delegates'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre de pays représentés
    $stmt = $pdo->query("SELECT COUNT(DISTINCT country_code) as total FROM users WHERE country_code IS NOT NULL");
    $stats['total_countries'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre de comités
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM committees");
    $stats['total_committees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Derniers utilisateurs inscrits
    $stmt = $pdo->query("
        SELECT u.*, c.name as country_name 
        FROM users u 
        LEFT JOIN countries c ON u.country_code = c.code 
        ORDER BY u.created_at DESC 
        LIMIT 5
    ");
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $page_title = "Dashboard";
    include 'includes/header.php';
    include 'includes/nav.php';
    ?>

    <div class="container-fluid px-4">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="display-4 mb-0"><?php echo $stats['total_users']; ?></h3>
                                <div class="mt-2">Utilisateurs</div>
                            </div>
                            <div>
                                <i class="fas fa-users fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="user-management.php">Voir les détails</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="display-4 mb-0"><?php echo $stats['total_delegates']; ?></h3>
                                <div class="mt-2">Délégués</div>
                            </div>
                            <div>
                                <i class="fas fa-user-tie fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="user-management.php">Voir les détails</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="display-4 mb-0"><?php echo $stats['total_countries']; ?></h3>
                                <div class="mt-2">Pays</div>
                            </div>
                            <div>
                                <i class="fas fa-globe fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="country-management.php">Voir les détails</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="display-4 mb-0"><?php echo $stats['total_committees']; ?></h3>
                                <div class="mt-2">Comités</div>
                            </div>
                            <div>
                                <i class="fas fa-building fa-3x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="committee-management.php">Voir les détails</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derniers utilisateurs -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Derniers utilisateurs inscrits</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Pays</th>
                                        <th>Date d'inscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if (!empty($user['country_name'])): ?>
                                                <?php echo htmlspecialchars($user['country_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Non assigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include 'includes/footer.php';
} catch(PDOException $e) {
    error_log("Database error in dashboard: " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données : " . $e->getMessage());
}
?>
