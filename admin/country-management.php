<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch all countries
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT * FROM countries ORDER BY name");
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database error in country-management: " . $e->getMessage());
    die("Database error occurred: " . $e->getMessage());
}

$page_title = 'Country Management';
include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-globe me-2"></i>Country Management</h2>
        </div>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="form-control" id="countrySearch" placeholder="Search countries...">
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-auto">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCountryModal">
                <i class="fas fa-plus me-2"></i>Add Country
            </button>
        </div>
    </div>

    <div class="row" id="countriesList">
        <?php foreach ($countries as $country): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($country['name']); ?></h5>
                    <p class="card-text">
                        <strong>Code:</strong> <?php echo htmlspecialchars($country['code']); ?><br>
                        <?php if (!empty($country['full_name'])): ?>
                        <strong>Full Name:</strong> <?php echo htmlspecialchars($country['full_name']); ?><br>
                        <?php endif; ?>
                        <strong>UN Member:</strong> <?php echo isset($country['is_member']) && $country['is_member'] ? 'Yes' : 'No'; ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-sm btn-outline-primary edit-country" 
                                data-code="<?php echo htmlspecialchars($country['code']); ?>"
                                data-name="<?php echo htmlspecialchars($country['name']); ?>"
                                data-fullname="<?php echo htmlspecialchars($country['full_name'] ?? ''); ?>"
                                data-member="<?php echo $country['is_member']; ?>">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-country" 
                                data-code="<?php echo htmlspecialchars($country['code']); ?>">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Country</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCountryForm">
                    <div class="mb-3">
                        <label for="countryCode" class="form-label">Country Code (2 letters)</label>
                        <input type="text" class="form-control" id="countryCode" maxlength="2" required>
                        <div class="form-text">Example: FR for France, US for United States</div>
                    </div>
                    <div class="mb-3">
                        <label for="countryName" class="form-label">Country Name</label>
                        <input type="text" class="form-control" id="countryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="countryFullName" class="form-label">Full Name (Optional)</label>
                        <input type="text" class="form-control" id="countryFullName">
                        <div class="form-text">Example: French Republic, United States of America</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="isMember" checked>
                        <label class="form-check-label" for="isMember">UN Member State</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addCountryForm" class="btn btn-primary">Add Country</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Country Modal -->
<div class="modal fade" id="editCountryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Country</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCountryForm">
                    <input type="hidden" id="editCountryCode">
                    <div class="mb-3">
                        <label for="editCountryName" class="form-label">Country Name</label>
                        <input type="text" class="form-control" id="editCountryName" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCountryFullName" class="form-label">Full Name (Optional)</label>
                        <input type="text" class="form-control" id="editCountryFullName">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="editIsMember">
                        <label class="form-check-label" for="editIsMember">UN Member State</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editCountryForm" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Search functionality
        $('#countrySearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#countriesList .col-md-4').each(function() {
                const cardText = $(this).text().toLowerCase();
                $(this).toggle(cardText.includes(searchTerm));
            });
        });

        // Add Country
        $('#addCountryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_country.php',
                method: 'POST',
                data: {
                    code: $('#countryCode').val().toUpperCase(),
                    name: $('#countryName').val(),
                    full_name: $('#countryFullName').val(),
                    is_member: $('#isMember').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error adding country: ' + xhr.responseJSON?.error || 'Unknown error');
                }
            });
        });

        // Edit Country
        $('.edit-country').click(function() {
            const btn = $(this);
            $('#editCountryCode').val(btn.data('code'));
            $('#editCountryName').val(btn.data('name'));
            $('#editCountryFullName').val(btn.data('fullname'));
            $('#editIsMember').prop('checked', btn.data('member') === 1);
            $('#editCountryModal').modal('show');
        });

        $('#editCountryForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'update_country.php',
                method: 'POST',
                data: {
                    code: $('#editCountryCode').val(),
                    name: $('#editCountryName').val(),
                    full_name: $('#editCountryFullName').val(),
                    is_member: $('#editIsMember').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error updating country: ' + xhr.responseJSON?.error || 'Unknown error');
                }
            });
        });

        // Delete Country
        $('.delete-country').click(function() {
            if (confirm('Are you sure you want to delete this country?')) {
                const countryCode = $(this).data('code');
                $.ajax({
                    url: 'delete_country.php',
                    method: 'POST',
                    data: { code: countryCode },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        alert('Error deleting country: ' + xhr.responseJSON?.error || 'Unknown error');
                    }
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
