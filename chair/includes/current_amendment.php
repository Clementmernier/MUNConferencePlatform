<?php
// Récupérer l'amendement en cours de discussion
$currentAmendmentStmt = $pdo->prepare("
    SELECT a.*, u.firstname, u.lastname, c.name as country_name, r.link as resolution_link
    FROM amendments a
    JOIN users u ON a.delegate_id = u.id
    JOIN countries c ON u.country_code = c.code
    JOIN resolutions r ON a.resolution_id = r.id
    WHERE a.committee_id = ? AND a.in_discussion = TRUE
    LIMIT 1
");
$currentAmendmentStmt->execute([$committee['id']]);
$currentAmendment = $currentAmendmentStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="card current-amendment-card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Current Amendment Under Discussion</h5>
    </div>
    <div class="card-body">
        <?php if ($currentAmendment): ?>
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h6 class="mb-1">Proposed by: <strong><?php echo htmlspecialchars($currentAmendment['country_name']); ?></strong></h6>
                    <small class="text-muted">
                        <?php echo htmlspecialchars($currentAmendment['firstname'] . ' ' . $currentAmendment['lastname']); ?>
                    </small>
                </div>
                <span class="badge bg-<?php echo $currentAmendment['status'] === 'approved' ? 'success' : ($currentAmendment['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                    <?php echo ucfirst($currentAmendment['status']); ?>
                </span>
            </div>
            <div class="amendment-content bg-light p-3 rounded">
                <?php 
                $content = json_decode($currentAmendment['content'], true);
                if ($content && isset($content['type'], $content['content'])) {
                    echo '<div class="mb-2">';
                    echo '<strong>Type: </strong>';
                    echo '<span class="badge bg-info">' . htmlspecialchars(ucfirst($content['type'])) . '</span>';
                    echo '</div>';
                    echo '<div>';
                    echo '<strong>Content: </strong>';
                    echo '<div class="mt-2">' . nl2br(htmlspecialchars($content['content'])) . '</div>';
                    echo '</div>';
                } else {
                    echo nl2br(htmlspecialchars($currentAmendment['content']));
                }
                ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-3">
                <i class="fas fa-info-circle mb-2 fs-4"></i>
                <p class="mb-0">No amendment currently under discussion.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
