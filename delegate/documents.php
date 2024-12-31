<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a delegate
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'delegate') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First, get the delegate's committee id
    $stmt = $pdo->prepare("
        SELECT committee_id, 
               (SELECT name FROM committees WHERE id = users.committee_id) as committee_name
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $delegate = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get documents and resolutions
    $stmt = $pdo->prepare("
        SELECT d.*, 
               CASE 
                   WHEN d.committee_id IS NULL THEN 'Global'
                   ELSE c.name 
               END as document_scope,
               u.firstname, u.lastname,
               COALESCE(d.document_type, 'document') as doc_type,
               pr.title as resolution_title,
               pr.status as resolution_status
        FROM documents d
        LEFT JOIN committees c ON d.committee_id = c.id
        LEFT JOIN users u ON d.uploaded_by = u.id
        LEFT JOIN purposed_resolutions pr ON d.id = pr.document_id
        WHERE (d.committee_id IS NULL 
           OR d.committee_id = ?)
        ORDER BY d.upload_date DESC
    ");
    $stmt->execute([$delegate['committee_id']]);
    $all_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate documents by type
    $documents = array_filter($all_documents, function($doc) {
        return $doc['doc_type'] === 'document';
    });
    
    $resolutions = array_filter($all_documents, function($doc) {
        return $doc['doc_type'] === 'resolution';
    });

} catch(PDOException $e) {
    error_log("Database error in delegate documents: " . $e->getMessage());
    die("An error occurred while accessing the database: " . $e->getMessage());
} catch(Exception $e) {
    error_log("Error in delegate documents: " . $e->getMessage());
    die("An error occurred: " . $e->getMessage());
}

$page_title = "Documents";
include 'includes/header.php';
include 'includes/nav.php';

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
?>

<style>
.nav-tabs .nav-link {
    color: #000000 !important;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #000000 !important;
    font-weight: 600;
}
</style>

<div class="container-fluid px-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Documents - <?php echo htmlspecialchars($delegate['committee_name']); ?></h2>
            <div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                    <i class="fas fa-upload me-2"></i>Upload Document
                </button>
                <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#uploadResolutionModal">
                    <i class="fas fa-scroll me-2"></i>Propose Resolution
                </button>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars($_SESSION['success_message']); 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs" id="documentsTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="helpful-tab" data-bs-toggle="tab" data-bs-target="#helpful" type="button" role="tab" aria-controls="helpful" aria-selected="true">
                        <i class="fas fa-file me-2"></i>Documents
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resolutions-tab" data-bs-toggle="tab" data-bs-target="#resolutions" type="button" role="tab" aria-controls="resolutions" aria-selected="false">
                        <i class="fas fa-scroll me-2"></i>Resolutions
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="documentsTabContent">
                <!-- Documents Tab -->
                <div class="tab-pane fade show active" id="helpful" role="tabpanel" aria-labelledby="helpful-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Committee Documents</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($documents)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Name</th>
                                                <th>Uploaded By</th>
                                                <th>Committee</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo getFileTypeColor($doc['file_type']); ?>">
                                                            <?php echo strtoupper($doc['file_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($doc['original_name']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($doc['firstname'] . ' ' . $doc['lastname']); ?>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($doc['document_scope']); ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($doc['upload_date'])); ?></td>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                                           class="btn btn-sm btn-outline-primary"
                                                           download="<?php echo htmlspecialchars($doc['original_name']); ?>">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-file mb-2 fs-4"></i>
                                    <p class="mb-0">No documents available.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Resolutions Tab -->
                <div class="tab-pane fade" id="resolutions" role="tabpanel" aria-labelledby="resolutions-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Proposed Resolutions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($resolutions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>File</th>
                                                <th>Proposed By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resolutions as $res): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($res['resolution_title']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($res['resolution_status']); ?>">
                                                            <?php echo ucfirst($res['resolution_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getFileTypeColor($res['file_type']); ?>">
                                                            <?php echo strtoupper($res['file_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($res['firstname'] . ' ' . $res['lastname']); ?>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($res['upload_date'])); ?></td>
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
        </div>
    </div>
</div>

<!-- Modal pour upload de document -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/upload_document.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="document" class="form-label">Select Document</label>
                        <input type="file" class="form-control" id="document" name="document" required>
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, ODT</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour proposer une résolution -->
<div class="modal fade" id="uploadResolutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Propose Resolution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/upload_resolution.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resolution_title" class="form-label">Resolution Title</label>
                        <input type="text" class="form-control" id="resolution_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="resolution_file" class="form-label">Resolution Document</label>
                        <input type="file" class="form-control" id="resolution_file" name="resolution_file" required>
                        <div class="form-text">Accepted formats: PDF, DOC, DOCX, ODT</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Propose Resolution</button>
                </div>
            </form>
        </div>
    </div>
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
});
</script>

<?php include 'includes/footer.php'; ?>
