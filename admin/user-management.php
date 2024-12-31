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

    // First, get the users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Then, try to get delegate information if the table exists
    $delegateInfo = [];
    try {
        $stmtDelegate = $pdo->query("
            SELECT d.user_id, d.first_name, d.last_name, c.name as country_name 
            FROM delegates d 
            LEFT JOIN countries c ON d.country_id = c.id
        ");
        while ($row = $stmtDelegate->fetch()) {
            $delegateInfo[$row['user_id']] = $row;
        }
    } catch(PDOException $e) {
        // Silently handle if delegates table doesn't exist or has different structure
        error_log("Delegate info fetch error: " . $e->getMessage());
    }

    $page_title = 'User Management';
    include 'includes/header.php';
    include 'includes/nav.php';
    ?>

    <div class="container">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-users me-2"></i>User Management</h2>
            </div>
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="form-control" id="userSearch" placeholder="Search users...">
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Délégué</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersList">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if (isset($delegateInfo[$user['id']])): ?>
                                        <?php 
                                            $delegate = $delegateInfo[$user['id']];
                                            if (!empty($delegate['country_name'])) {
                                                echo '<small class="text-muted">' . htmlspecialchars($delegate['country_name']) . '</small>';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <span class="text-muted">Non délégué</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'chair' ? 'success' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary edit-user" 
                                            data-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-user" 
                                            data-id="<?php echo $user['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="mb-3">
                            <label for="editFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editFirstName" name="firstname" required>
                        </div>
                        <div class="mb-3">
                            <label for="editLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editLastName" name="lastname" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="delegate">Delegate</option>
                                <option value="chair">Chair</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3" id="countryField">
                            <label for="editCountry" class="form-label">Country</label>
                            <select class="form-select" id="editCountry" name="country_code">
                                <option value="">Select a country</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT code, name FROM countries ORDER BY name");
                                    while ($country = $stmt->fetch()) {
                                        echo '<option value="' . htmlspecialchars($country['code']) . '">' . 
                                             htmlspecialchars($country['name']) . '</option>';
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error fetching countries: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCommittee" class="form-label">Committee</label>
                            <select class="form-select" id="editCommittee" name="committee_id">
                                <option value="">Select a committee</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, name FROM committees ORDER BY name");
                                    while ($committee = $stmt->fetch()) {
                                        echo '<option value="' . htmlspecialchars($committee['id']) . '">' . 
                                             htmlspecialchars($committee['name']) . '</option>';
                                    }
                                } catch(PDOException $e) {
                                    error_log("Error fetching committees: " . $e->getMessage());
                                }
                                ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="editUserForm" class="btn btn-primary">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion de l'affichage du champ pays en fonction du rôle
        const roleSelect = document.getElementById('editRole');
        const countryField = document.getElementById('countryField');

        function toggleCountryField() {
            const selectedRole = roleSelect.value;
            if (selectedRole === 'chair' || selectedRole === 'admin') {
                countryField.style.display = 'none';
                document.getElementById('editCountry').value = '';  // Réinitialiser la valeur
            } else {
                countryField.style.display = 'block';
            }
        }

        roleSelect.addEventListener('change', toggleCountryField);

        // Appliquer au chargement de la modal
        const editModal = document.getElementById('editUserModal');
        editModal.addEventListener('show.bs.modal', function() {
            toggleCountryField();
        });

        // Search functionality
        const searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#usersList tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }

        // Edit user buttons
        const editButtons = document.querySelectorAll('.edit-user');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                fetch('get_user.php?id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const user = data.data;
                            document.getElementById('editUserId').value = user.id;
                            document.getElementById('editFirstName').value = user.firstname;
                            document.getElementById('editLastName').value = user.lastname;
                            document.getElementById('editEmail').value = user.email;
                            document.getElementById('editRole').value = user.role;
                            document.getElementById('editCountry').value = user.country_code || '';
                            document.getElementById('editCommittee').value = user.committee_id || '';
                            
                            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
                            editModal.show();
                        } else {
                            alert('Error fetching user data: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error fetching user data');
                    });
            });
        });

        // Handle form submission
        const editForm = document.getElementById('editUserForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('User updated successfully');
                        location.reload();
                    } else {
                        alert('Error updating user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating user');
                });
            });
        }
    });
    </script>

    <?php include 'includes/footer.php';
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Une erreur est survenue lors de la connexion à la base de données.");
}
?>