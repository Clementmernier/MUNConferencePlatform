<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Check if the user is logged in and is a delegate
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'delegate') {
    header('Location: ../auth/login.php');
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve delegate and committee information, as well as the current resolution
    $stmt = $pdo->prepare("
        SELECT u.*, c.name as committee_name, c.id as committee_id, cd.google_docs_link, co.name as country_name,
               r.link as resolution_link
        FROM users u
        LEFT JOIN committees c ON u.committee_id = c.id
        LEFT JOIN committee_docs cd ON c.id = cd.committee_id
        LEFT JOIN countries co ON u.country_code = co.code
        LEFT JOIN resolutions r ON c.id = r.committee_id AND r.status = 'in_debate'
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $delegate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$delegate) {
        throw new Exception('Delegate not found');
    }

} catch(PDOException $e) {
    error_log("Database error in delegate dashboard: " . $e->getMessage());
    die("An error occurred while accessing the database: " . $e->getMessage());
} catch(Exception $e) {
    error_log("Error in delegate dashboard: " . $e->getMessage());
    die("An error occurred: " . $e->getMessage());
}

$page_title = "Delegate Dashboard";
include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="container-fluid px-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Dashboard - <?php echo htmlspecialchars($delegate['committee_name'] ?? 'No Committee'); ?></h2>
        </div>
    </div>

    <!-- Current Amendment Section -->
    <div class="row mb-4">
        <div class="col-12">
            <?php include 'includes/current_amendment.php'; ?>
        </div>
    </div>

    <!-- Delegate Information -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Your Information</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($delegate['firstname'] . ' ' . $delegate['lastname']); ?></p>
                    <p><strong>Country:</strong> <?php echo htmlspecialchars($delegate['country_name'] ?? 'Not assigned'); ?></p>
                    <p><strong>Committee:</strong> <?php echo htmlspecialchars($delegate['committee_name'] ?? 'Not assigned'); ?></p>
                </div>
            </div>
        </div>
        <?php if ($delegate['committee_id']): ?>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#amendmentModal">
                            <i class="fas fa-file-alt me-2"></i>Submit Amendment
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resolutionModal">
                            <i class="fas fa-file-signature me-2"></i>Submit Resolution
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>Please wait for an administrator to assign you to a committee to access features.
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Google Docs Frame -->
    <?php if ($delegate['committee_id']): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Under Debate</h5>
                </div>
                <div class="card-body">
                    <?php if ($delegate['resolution_link']): ?>
                        <iframe src="<?php echo htmlspecialchars($delegate['resolution_link']); ?>" 
                                width="100%" 
                                height="800px" 
                                frameborder="0"
                                allowfullscreen="true"
                                mozallowfullscreen="true"
                                webkitallowfullscreen="true">
                        </iframe>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No resolution is currently under debate.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Amendment Modal -->
<div class="modal fade" id="amendmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Amendment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (!$delegate['resolution_link']): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No resolution is currently under debate. You cannot submit an amendment.
                    </div>
                <?php else: ?>
                    <form id="amendmentForm">
                        <input type="hidden" name="delegate_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <input type="hidden" name="committee_id" value="<?php echo $delegate['committee_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Delegate</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($delegate['firstname'] . ' ' . $delegate['lastname']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($delegate['country_name']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amendmentType" class="form-label">Amendment Type</label>
                            <select class="form-select" id="amendmentType" name="type" required>
                                <option value="">Choose a type...</option>
                                <option value="add">Add</option>
                                <option value="modify">Modify</option>
                                <option value="delete">Delete</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amendmentContent" class="form-label">Amendment Content</label>
                            <textarea class="form-control" id="amendmentContent" name="content" rows="5" required></textarea>
                            <div class="form-text">Clearly describe the changes you want to make to the resolution.</div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <?php if ($delegate['resolution_link']): ?>
                    <button type="button" class="btn btn-primary" id="submitAmendment">Submit</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resolution Modal -->
<div class="modal fade" id="resolutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Google Docs Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resolutionForm" method="POST" action="submit_resolution.php">
                    <div class="mb-3">
                        <label for="resolutionLink" class="form-label">Google Docs Link</label>
                        <input type="url" class="form-control" id="resolutionLink" name="resolution_link" placeholder="https://docs.google.com/..." required>
                        <div class="form-text">Please provide a valid Google Docs link.</div>
                    </div>
                    <input type="hidden" name="committee_id" value="<?php echo htmlspecialchars($delegate['committee_id']); ?>">
                    <button type="submit" class="btn btn-success">Submit</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('submitAmendment')?.addEventListener('click', function() {
    const form = document.getElementById('amendmentForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const amendmentData = {
        type: formData.get('type'),
        content: formData.get('content')
    };

    formData.set('content', JSON.stringify(amendmentData));

    fetch('submit_amendment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const toastEl = document.createElement('div');
            toastEl.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastEl.innerHTML = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>
                            Amendment submitted successfully
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl.querySelector('.toast'));
            toast.show();
            
            $('#amendmentModal').modal('hide');
            form.reset();
        } else {
            alert('Error submitting amendment: ' + (data.message || 'An error occurred'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the amendment');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
