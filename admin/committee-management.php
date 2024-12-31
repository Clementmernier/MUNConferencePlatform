<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all committees
    $stmt = $pdo->query("SELECT * FROM committees ORDER BY name");
    $committees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error in committee-management: " . $e->getMessage());
    die("Database error occurred: " . $e->getMessage());
}

$page_title = 'Committee Management';
include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-building me-2"></i>Committee Management</h2>
        </div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control" id="searchCommittee" placeholder="Search committees...">
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommitteeModal">
                <i class="fas fa-plus me-2"></i>Add Committee
            </button>
        </div>
    </div>

    <div class="row" id="committeesList">
        <?php foreach ($committees as $committee): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($committee['name']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($committee['description']); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-sm btn-outline-primary edit-committee" 
                                data-id="<?php echo $committee['id']; ?>">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-committee" 
                                data-id="<?php echo $committee['id']; ?>">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Committee Modal -->
<div class="modal fade" id="addCommitteeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Committee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCommitteeForm">
                    <div class="mb-3">
                        <label for="committeeName" class="form-label">Committee Name</label>
                        <input type="text" class="form-control" id="committeeName" required>
                    </div>
                    <div class="mb-3">
                        <label for="committeeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="committeeDescription" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addCommitteeForm" class="btn btn-primary">Add Committee</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Search functionality
        $('#searchCommittee').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#committeesList .col-md-4').each(function() {
                const cardText = $(this).text().toLowerCase();
                $(this).toggle(cardText.includes(searchTerm));
            });
        });

        // Add Committee
        $('#addCommitteeForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_committee.php',
                method: 'POST',
                data: {
                    name: $('#committeeName').val(),
                    description: $('#committeeDescription').val()
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    showToast('Error adding committee', 'error');
                }
            });
        });

        // Delete Committee
        $('.delete-committee').click(function() {
            if (confirm('Are you sure you want to delete this committee?')) {
                const committeeId = $(this).data('id');
                $.ajax({
                    url: 'delete_committee.php',
                    method: 'POST',
                    data: { id: committeeId },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        showToast('Error deleting committee', 'error');
                    }
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
