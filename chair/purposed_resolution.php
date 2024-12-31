<?php
require_once 'includes/header.php';
require_once 'includes/nav.php';

try {
    // Récupérer les informations du comité du président
    $stmt = $pdo->prepare("SELECT c.* FROM committees c 
                          JOIN users u ON c.id = u.committee_id 
                          WHERE u.id = ? AND u.role = 'chair'");
    $stmt->execute([$_SESSION['user_id']]);
    $committee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$committee) {
        throw new Exception('No committee assigned.');
    }

    // Récupérer les résolutions pour ce comité
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               d.file_type, d.original_name, d.file_path,
               u.firstname, u.lastname,
               c.name as committee_name
        FROM purposed_resolutions pr
        JOIN documents d ON pr.document_id = d.id
        JOIN users u ON pr.delegate_id = u.id
        JOIN committees c ON pr.committee_id = c.id
        WHERE pr.committee_id = ?
        ORDER BY pr.submission_date DESC
    ");
    $stmt->execute([$committee['id']]);
    $resolutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    error_log("Error in purposed_resolution: " . $e->getMessage());
    $error = $e->getMessage();
}

// Function to get status badge color
function getStatusColor($status) {
    switch($status) {
        case 'draft':
            return 'secondary';
        case 'discussing':
            return 'info';
        case 'adopted':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'primary';
    }
}

// Function to get a Bootstrap color class based on file type
function getFileTypeColor($fileType) {
    switch(strtolower($fileType)) {
        case 'pdf':
            return 'danger';
        case 'doc':
        case 'docx':
            return 'primary';
        case 'odt':
            return 'success';
        default:
            return 'info';
    }
}
?>

<div class="container-fluid px-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-scroll me-2"></i>Proposed Resolutions</h2>
            <span class="badge bg-primary" id="resolutions-count">
                Total: <?php echo count($resolutions); ?>
            </span>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if (!empty($resolutions)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Resolution Number</th>
                                            <th>Title</th>
                                            <th>Status</th>
                                            <th>Proposed By</th>
                                            <th>Committee</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resolutions as $res): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($res['resolution_number'] ?: 'Draft'); ?></td>
                                                <td><?php echo htmlspecialchars($res['title']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo getStatusColor($res['status']); ?>">
                                                        <?php echo ucfirst($res['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($res['firstname'] . ' ' . $res['lastname']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($res['committee_name']); ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($res['submission_date'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="<?php echo htmlspecialchars($res['file_path']); ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           download="<?php echo htmlspecialchars($res['original_name']); ?>">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-info view-resolution" 
                                                                data-file-path="<?php echo htmlspecialchars($res['file_path']); ?>">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($res['status'] === 'draft'): ?>
                                                            <button class="btn btn-sm btn-outline-warning set-discussing" 
                                                                    data-id="<?php echo $res['id']; ?>">
                                                                <i class="fas fa-gavel"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($res['status'] === 'discussing'): ?>
                                                            <button class="btn btn-sm btn-outline-success set-adopted" 
                                                                    data-id="<?php echo $res['id']; ?>">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger set-rejected" 
                                                                    data-id="<?php echo $res['id']; ?>">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-scroll mb-2 fs-4"></i>
                                <p class="mb-0">No resolutions have been proposed yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pour visualiser la résolution -->
<div class="modal fade" id="viewResolutionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Resolution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <iframe id="resolutionViewer" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour le bouton de visualisation
    document.querySelectorAll('.view-resolution').forEach(button => {
        button.addEventListener('click', function() {
            const filePath = this.dataset.filePath;
            const viewer = document.getElementById('resolutionViewer');
            viewer.src = filePath;
            
            const modal = new bootstrap.Modal(document.getElementById('viewResolutionModal'));
            modal.show();
        });
    });

    // Gestionnaire pour les boutons de changement de statut
    ['discussing', 'adopted', 'rejected'].forEach(action => {
        document.querySelectorAll(`.set-${action}`).forEach(button => {
            button.addEventListener('click', async function() {
                const id = this.dataset.id;
                try {
                    const response = await fetch('actions/update_resolution_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `resolution_id=${id}&status=${action}`
                    });
                    
                    if (!response.ok) throw new Error('Failed to update status');
                    
                    // Recharger la page pour montrer le nouveau statut
                    window.location.reload();
                } catch (error) {
                    console.error('Error:', error);
                    alert('Failed to update resolution status');
                }
            });
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
