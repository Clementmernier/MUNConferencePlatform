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

    // Récupérer la résolution actuelle en débat
    $resolutionStmt = $pdo->prepare("
        SELECT * FROM resolutions 
        WHERE committee_id = ? AND status = 'in_debate'
        ORDER BY created_at DESC LIMIT 1
    ");
    $resolutionStmt->execute([$committee['id']]);
    $currentResolution = $resolutionStmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer la liste des speakers
    $speakersStmt = $pdo->prepare("
        SELECT s.*, u.firstname as first_name, u.lastname as last_name, c.name as country_name 
        FROM speakers_list s
        JOIN users u ON s.user_id = u.id
        JOIN countries c ON u.country_code = c.code
        WHERE s.committee_id = ? AND s.status = 'pending'
        ORDER BY s.created_at ASC
    ");
    $speakersStmt->execute([$committee['id']]);
    $speakers = $speakersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer la liste des délégués disponibles
    $delegatesStmt = $pdo->prepare("
        SELECT u.*, c.name as country_name 
        FROM users u
        JOIN countries c ON u.country_code = c.code
        WHERE u.committee_id = ? AND u.role = 'delegate'
        ORDER BY c.name ASC
    ");
    $delegatesStmt->execute([$committee['id']]);
    $delegates = $delegatesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Traitement des actions POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_speaker':
                if (!empty($_POST['delegate_id']) && !empty($_POST['speaking_time'])) {
                    // Calculer la prochaine position pour ce comité
                    $posStmt = $pdo->prepare("
                        SELECT COALESCE(MAX(position), 0) + 1 as next_pos 
                        FROM speakers_list 
                        WHERE committee_id = ?
                    ");
                    $posStmt->execute([$committee['id']]);
                    $nextPos = $posStmt->fetch(PDO::FETCH_ASSOC)['next_pos'];

                    $insertStmt = $pdo->prepare("
                        INSERT INTO speakers_list (committee_id, user_id, speaking_time, position, status) 
                        VALUES (?, ?, ?, ?, 'pending')
                    ");
                    $insertStmt->execute([$committee['id'], $_POST['delegate_id'], $_POST['speaking_time'], $nextPos]);
                    header('Location: dashboard.php?success=1');
                    exit;
                }
                break;

            case 'remove_speaker':
                if (!empty($_POST['speaker_id'])) {
                    $deleteStmt = $pdo->prepare("DELETE FROM speakers_list WHERE id = ?");
                    $deleteStmt->execute([$_POST['speaker_id']]);
                    header('Location: dashboard.php?success=2');
                    exit;
                }
                break;

            case 'set_resolution':
                if (!empty($_POST['resolution_link'])) {
                    // Mettre toutes les résolutions précédentes en draft
                    $updateStmt = $pdo->prepare("
                        UPDATE resolutions 
                        SET status = 'draft' 
                        WHERE committee_id = ? AND status = 'in_debate'
                    ");
                    $updateStmt->execute([$committee['id']]);

                    // Ajouter la nouvelle résolution
                    $insertStmt = $pdo->prepare("
                        INSERT INTO resolutions (link, committee_id, status) 
                        VALUES (?, ?, 'in_debate')
                    ");
                    $insertStmt->execute([$_POST['resolution_link'], $committee['id']]);
                    header('Location: dashboard.php?success=3');
                    exit;
                }
                break;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<style>
.right-panel {
    position: fixed;
    right: 0;
    top: 56px; /* Height of the navbar */
    width: 33.33%;
    height: calc(100vh - 56px); /* Subtract navbar height from total height */
    background-color: #f8f9fa;
    border-left: 1px solid #dee2e6;
    overflow-y: auto;
    padding: 20px;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
    z-index: 100;
}

.left-panel {
    margin-right: 33.33%;
    padding: 20px;
}

.resolution-section {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.current-resolution {
    margin-top: 10px;
    padding: 10px;
    background-color: #e9ecef;
    border-radius: 4px;
}
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Section des résolutions -->
        <div class="col-md-6">
            <div class="resolution-section">
                <h3>Resolution Management</h3>
                <?php if ($currentResolution): ?>
                    <div class="current-resolution">
                        <h5>Current Resolution in Debate:</h5>
                        <a href="<?php echo htmlspecialchars($currentResolution['link']); ?>" target="_blank" class="btn btn-primary">
                            View Current Resolution
                        </a>
                    </div>
                <?php endif; ?>
                
                <form action="dashboard.php" method="POST" class="mt-4">
                    <input type="hidden" name="action" value="set_resolution">
                    <div class="input-group">
                        <input type="url" name="resolution_link" class="form-control" placeholder="Enter resolution link" required>
                        <button type="submit" class="btn btn-success">Set as Current Resolution</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Section de la liste des orateurs -->
        <div class="col-md-6">
            <div class="speakers-list">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Speakers List</h5>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSpeakerModal">
                                    Add Speaker
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (empty($speakers)): ?>
                                    <p class="text-center text-muted">No speakers in the list</p>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($speakers as $speaker): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($speaker['country_name']); ?></h6>
                                                    <small>
                                                        <?php echo htmlspecialchars($speaker['first_name'] . ' ' . $speaker['last_name']); ?>
                                                        (<?php echo $speaker['speaking_time']; ?> min)
                                                    </small>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="remove_speaker">
                                                        <input type="hidden" name="speaker_id" value="<?php echo $speaker['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section de l'amendement en cours -->
    <div class="row mt-4">
        <div class="col-12">
            <?php include 'includes/current_amendment.php'; ?>
        </div>
    </div>
</div>
<!-- Section de la résolution en cours -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Resolution Under Discussion</h5>
            </div>
            <div class="card-body">
                <?php if ($currentResolution): ?>
                    <iframe src="<?php echo htmlspecialchars($currentResolution['link']); ?>" 
                            width="100%" 
                            height="800px" 
                            frameborder="0"
                            allowfullscreen="true"
                            mozallowfullscreen="true"
                            webkitallowfullscreen="true">
                    </iframe>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No resolution is currently under discussion.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Modal d'ajout de speaker -->
<div class="modal fade" id="addSpeakerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Speaker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="action" value="add_speaker">
                    <div class="mb-3">
                        <label class="form-label">Delegate</label>
                        <select name="delegate_id" class="form-control" required>
                            <option value="">Select a delegate</option>
                            <?php foreach ($delegates as $delegate): ?>
                                <option value="<?php echo $delegate['id']; ?>">
                                    <?php echo htmlspecialchars($delegate['country_name']); ?> - 
                                    <?php echo htmlspecialchars($delegate['firstname'] . ' ' . $delegate['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Speaking Time (minutes)</label>
                        <input type="number" name="speaking_time" class="form-control" value="1" min="1" max="10" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// No timer functionality needed anymore
</script>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {
    // Gérer l'approbation/rejet des amendements
    $('.btn-approve, .btn-reject').click(function(e) {
        e.preventDefault();
        const amendmentId = $(this).data('amendment-id');
        const action = $(this).hasClass('btn-approve') ? 'approve' : 'reject';
        const button = $(this);
        const buttonRow = button.closest('.list-group-item');

        $.ajax({
            url: 'handle_amendment.php',
            method: 'POST',
            data: {
                amendment_id: amendmentId,
                action: action
            },
            success: function(response) {
                if (response.success) {
                    // Supprimer l'amendement de la liste
                    buttonRow.fadeOut(400, function() {
                        $(this).remove();
                        // Si c'était le dernier amendement, afficher le message "No amendments"
                        if ($('.list-group-item').length === 0) {
                            $('.list-group').html('<p class="text-center text-muted p-3">No amendments pending</p>');
                        }
                    });
                    
                    // Afficher un message de succès
                    const alertClass = action === 'approve' ? 'success' : 'danger';
                    const alertHtml = `
                        <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                            Amendment ${action === 'approve' ? 'approved' : 'rejected'} successfully
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                    $('.container-fluid').prepend(alertHtml);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred';
                const alertHtml = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('.container-fluid').prepend(alertHtml);
            }
        });
    });
});
</script>
</body>
</html>
