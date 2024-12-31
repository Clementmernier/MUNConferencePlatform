<?php
session_start();
require_once '../config/database.php';

// Vérifier si l'utilisateur est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$message = '';
$messageType = '';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la liste des comités pour le formulaire
    $stmt = $pdo->query("SELECT id, name FROM committees ORDER BY name");
    $committees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gérer l'upload de fichier
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
        $file = $_FILES['document'];
        $committee_id = !empty($_POST['committee_id']) ? $_POST['committee_id'] : null;
        
        // Vérifier le type de fichier
        $allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'ods', 'odp'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            $message = "Type de fichier non autorisé. Types acceptés : " . implode(', ', $allowedTypes);
            $messageType = 'danger';
        } elseif ($file['size'] > 20 * 1024 * 1024) { // 20MB max
            $message = "Le fichier est trop volumineux. Taille maximum : 20MB";
            $messageType = 'danger';
        } else {
            // Générer un nom de fichier unique
            $newFileName = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file['name']);
            $targetPath = "../uploads/documents/" . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Insérer les informations dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO documents (name, original_name, file_path, file_type, file_size, uploaded_by, committee_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $newFileName,
                    $file['name'],
                    $targetPath,
                    $fileExtension,
                    $file['size'],
                    $_SESSION['user_id'],
                    $committee_id
                ]);
                
                $message = "Document uploadé avec succès !";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de l'upload du fichier.";
                $messageType = 'danger';
            }
        }
    }

    // Récupérer la liste des documents
    $stmt = $pdo->query("
        SELECT d.*, u.firstname, u.lastname, c.name as committee_name 
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        LEFT JOIN committees c ON d.committee_id = c.id 
        ORDER BY d.upload_date DESC
    ");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $message = "Erreur de base de données : " . $e->getMessage();
    $messageType = 'danger';
}

$page_title = "Gestion des Documents";
include 'includes/header.php';
include 'includes/nav.php';
?>

<div class="container mt-4">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-upload me-2"></i>Upload un document</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="document" class="form-label">Sélectionner un fichier</label>
                            <input type="file" class="form-control" id="document" name="document" required>
                            <div class="form-text">Types acceptés : pdf, doc, docx, ppt, pptx, xls, xlsx, odt, ods, odp</div>
                        </div>
                        <div class="mb-3">
                            <label for="committee_id" class="form-label">Comité (optionnel)</label>
                            <select class="form-select" id="committee_id" name="committee_id">
                                <option value="">Sélectionner un comité</option>
                                <?php foreach ($committees as $committee): ?>
                                    <option value="<?php echo $committee['id']; ?>">
                                        <?php echo htmlspecialchars($committee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Documents uploadés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Taille</th>
                                    <th>Comité</th>
                                    <th>Uploadé par</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['original_name']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo strtoupper($doc['file_type']); ?></span></td>
                                        <td><?php echo number_format($doc['file_size'] / 1024, 2) . ' KB'; ?></td>
                                        <td><?php echo $doc['committee_name'] ? htmlspecialchars($doc['committee_name']) : '<em>Aucun</em>'; ?></td>
                                        <td><?php echo htmlspecialchars($doc['firstname'] . ' ' . $doc['lastname']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($doc['upload_date'])); ?></td>
                                        <td>
                                            <a href="<?php echo $doc['file_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun document uploadé</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prévisualisation du nom du fichier
    document.getElementById('document').addEventListener('change', function(e) {
        const fileName = e.target.files[0].name;
        const fileSize = e.target.files[0].size;
        
        // Vérifier la taille du fichier
        if (fileSize > 20 * 1024 * 1024) { // 20MB
            alert('Le fichier est trop volumineux. Taille maximum : 20MB');
            this.value = ''; // Réinitialiser l'input
            return;
        }
        
        // Vérifier l'extension
        const extension = fileName.split('.').pop().toLowerCase();
        const allowedTypes = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'odt', 'ods', 'odp'];
        
        if (!allowedTypes.includes(extension)) {
            alert('Type de fichier non autorisé. Types acceptés : ' + allowedTypes.join(', '));
            this.value = ''; // Réinitialiser l'input
            return;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
