<?php
require_once '../config/database.php';
session_start();

// Vérifier si l'utilisateur est connecté et est un président
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'chair') {
    header('Location: ../login.html');
    exit();
}

// Récupérer les informations du comité du président
$stmt = $pdo->prepare("SELECT c.* FROM committees c 
                      JOIN users u ON c.id = u.committee_id 
                      WHERE u.id = ? AND u.role = 'chair'");
$stmt->execute([$_SESSION['user_id']]);
$committee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$committee) {
    die('No committee assigned.');
}

// Récupérer les amendements discutés (approuvés ou rejetés)
$amendmentsStmt = $pdo->prepare("
    SELECT a.*, u.firstname, u.lastname, c.name as country_name, 
           CASE 
               WHEN a.status = 'approved' THEN 'Approved'
               WHEN a.status = 'rejected' THEN 'Rejected'
           END as status_display
    FROM amendments a
    JOIN users u ON a.delegate_id = u.id
    JOIN countries c ON u.country_code = c.code
    WHERE a.committee_id = ? 
    AND a.status IN ('approved', 'rejected')
    ORDER BY a.updated_at DESC
");
$amendmentsStmt->execute([$committee['id']]);
$amendments = $amendmentsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/nav.php';
?>

<div class="container-fluid px-4">
    <h2 class="mt-4 mb-4">Discussed Amendments</h2>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Amendment History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($amendments)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No amendments have been discussed yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Delegate</th>
                                <th>Country</th>
                                <th>Type</th>
                                <th>Content</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($amendments as $amendment): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($amendment['updated_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($amendment['firstname'] . ' ' . $amendment['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($amendment['country_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $amendment['type'] === 'add' ? 'success' : ($amendment['type'] === 'modify' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($amendment['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($amendment['content']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $amendment['status'] === 'approved' ? 'success' : 'danger'; ?>">
                                            <?php echo $amendment['status_display']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
