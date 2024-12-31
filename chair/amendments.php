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

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<style>
.amendment-card {
    margin-bottom: 1.5rem;
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.amendment-card .card-header {
    background: linear-gradient(45deg, #f8f9fa, #ffffff);
    border-bottom: 1px solid rgba(0,0,0,0.05);
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.25rem;
}

.amendment-card .card-body {
    padding: 1.5rem;
}

.status-badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
    border-radius: 50px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background-color: #ffd43b;
    color: #000;
}

.status-approved {
    background-color: #40c057;
    color: #fff;
}

.status-rejected {
    background-color: #fa5252;
    color: #fff;
}

.content-box {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.25rem;
    margin: 1rem 0;
    border: 1px solid rgba(0,0,0,0.05);
}

.amendment-type {
    display: inline-block;
    padding: 0.35rem 0.8rem;
    border-radius: 50px;
    background-color: #e9ecef;
    color: #495057;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.btn-action {
    border-radius: 50px;
    padding: 0.5rem 1.2rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
    margin: 0 0.3rem;
}

.country-flag {
    width: 24px;
    height: 16px;
    margin-right: 0.5rem;
    vertical-align: middle;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.resolution-link {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background-color: #e9ecef;
    border-radius: 8px;
    color: #495057;
    text-decoration: none;
}

.resolution-link:hover {
    background-color: #dee2e6;
    color: #212529;
}

.resolution-link i {
    margin-right: 0.5rem;
}

.timestamp {
    font-size: 0.85rem;
    color: #868e96;
}

.container-fluid {
    margin-top: 2rem;
    padding-bottom: 2rem;
}

.page-header {
    margin-bottom: 2rem;
}

.page-header h2 {
    font-weight: 600;
    color: #212529;
}

.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.no-amendments {
    text-align: center;
    padding: 3rem 0;
    background-color: #f8f9fa;
    border-radius: 12px;
    margin: 2rem 0;
}

.no-amendments i {
    font-size: 3rem;
    color: #adb5bd;
    margin-bottom: 1rem;
}

.no-amendments p {
    color: #495057;
    font-size: 1.1rem;
    margin-bottom: 0;
}
</style>

<div class="container-fluid px-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-gavel me-2"></i>Amendments Management</h2>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2">Committee: <?php echo htmlspecialchars($committee['name']); ?></span>
                <span class="badge bg-secondary" id="amendments-count">Loading...</span>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div id="success-alert" class="alert alert-success" role="alert" style="display: none;">
        <i class="fas fa-check-circle me-2"></i><span id="success-message"></span>
    </div>

    <div class="row">
        <div class="col-12">
            <div id="amendments-container">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<template id="amendment-template">
    <div class="card amendment-card" data-amendment-id="">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="" alt="" class="country-flag" style="display: none;">
                <i class="fas fa-flag text-muted me-2" style="display: none;"></i>
                <div>
                    <strong class="d-block country-name"></strong>
                    <small class="text-muted delegate-name"></small>
                </div>
            </div>
            <span class="badge status-badge"></span>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <a href="" target="_blank" class="resolution-link">
                    <i class="fas fa-external-link-alt"></i>
                    View Original Resolution
                </a>
            </div>
            
            <div class="content-box amendment-content">
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4">
                <span class="timestamp">
                    <i class="far fa-clock me-1"></i>
                    <span class="date"></span>
                </span>
                <div class="d-flex action-buttons">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
function createActionButton(type, amendmentId) {
    const form = document.createElement('form');
    form.method = 'post';
    form.className = 'd-inline';

    let buttonClass, buttonIcon, buttonText, actionUrl;
    switch(type) {
        case 'approve':
            buttonClass = 'btn-success';
            buttonIcon = 'fa-check';
            buttonText = 'Approve';
            actionUrl = 'handle_amendment.php';
            break;
        case 'reject':
            buttonClass = 'btn-danger';
            buttonIcon = 'fa-times';
            buttonText = 'Reject';
            actionUrl = 'handle_amendment.php';
            break;
        case 'discuss':
            buttonClass = 'btn-info';
            buttonIcon = 'fa-gavel';
            buttonText = 'Set as Current';
            actionUrl = 'actions/set_current_amendment.php';
            break;
    }

    // Définir l'action du formulaire
    form.setAttribute('action', actionUrl);

    if (type === 'approve' || type === 'reject') {
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = type;
        form.appendChild(actionInput);
    }

    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'amendment_id';
    idInput.value = amendmentId;
    form.appendChild(idInput);

    const button = document.createElement('button');
    button.type = 'submit';
    button.className = `btn ${buttonClass} btn-action`;
    button.innerHTML = `<i class="fas ${buttonIcon} me-1"></i>${buttonText}`;
    form.appendChild(button);

    return form;
}

function updateAmendment(template, amendment) {
    template.dataset.amendmentId = amendment.id;
    
    // Mise à jour du drapeau
    const flagImg = template.querySelector('.country-flag');
    const flagIcon = template.querySelector('.fas.fa-flag');
    if (amendment.country_code) {
        flagImg.src = `https://flagcdn.com/w40/${amendment.country_code.toLowerCase().substring(0, 2)}.png`;
        flagImg.alt = `${amendment.country_name} flag`;
        flagImg.style.display = '';
        flagIcon.style.display = 'none';
    } else {
        flagImg.style.display = 'none';
        flagIcon.style.display = '';
    }

    // Mise à jour des informations
    template.querySelector('.country-name').textContent = amendment.country_name;
    template.querySelector('.delegate-name').textContent = 
        `${amendment.firstname} ${amendment.lastname}`;
    
    const statusBadge = template.querySelector('.status-badge');
    statusBadge.textContent = amendment.status.charAt(0).toUpperCase() + amendment.status.slice(1);
    statusBadge.className = `badge status-badge status-${amendment.status}`;

    // Lien de résolution
    template.querySelector('.resolution-link').href = amendment.resolution_link;

    // Contenu de l'amendement
    const contentBox = template.querySelector('.amendment-content');
    try {
        const content = JSON.parse(amendment.content);
        if (content && content.type && content.content) {
            contentBox.innerHTML = `
                <span class="amendment-type">
                    <i class="fas fa-edit me-2"></i>${content.type.charAt(0).toUpperCase() + content.type.slice(1)}
                </span>
                <div class="amendment-content">${content.content.replace(/\n/g, '<br>')}</div>
            `;
        } else {
            contentBox.innerHTML = amendment.content.replace(/\n/g, '<br>');
        }
    } catch {
        contentBox.innerHTML = amendment.content.replace(/\n/g, '<br>');
    }

    // Date
    template.querySelector('.date').textContent = amendment.formatted_date;

    // Boutons d'action
    const actionButtons = template.querySelector('.action-buttons');
    actionButtons.innerHTML = '';
    
    if (amendment.status === 'pending') {
        actionButtons.appendChild(createActionButton('approve', amendment.id));
        actionButtons.appendChild(createActionButton('reject', amendment.id));
    }
    
    const discussButton = createActionButton('discuss', amendment.id);
    if (amendment.in_discussion) {
        discussButton.querySelector('button').className = 'btn btn-info btn-action';
        discussButton.querySelector('button').innerHTML = '<i class="fas fa-gavel me-1"></i>Currently Discussing';
    }
    actionButtons.appendChild(discussButton);
}

function showSuccessMessage(message) {
    const alert = document.getElementById('success-alert');
    const messageSpan = document.getElementById('success-message');
    messageSpan.textContent = message;
    alert.style.display = 'block';
    setTimeout(() => {
        alert.style.display = 'none';
    }, 3000);
}

async function fetchAmendments() {
    try {
        const response = await fetch('get_amendments.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch amendments');
        }

        const container = document.getElementById('amendments-container');
        const template = document.getElementById('amendment-template');
        
        // Mettre à jour le compteur
        document.getElementById('amendments-count').textContent = `Total: ${data.amendments.length}`;

        if (data.amendments.length === 0) {
            container.innerHTML = `
                <div class="no-amendments">
                    <i class="fas fa-file-alt mb-3 d-block"></i>
                    <p>No amendments have been submitted yet.</p>
                </div>
            `;
            return;
        }

        // Créer le nouveau contenu
        const newContent = document.createDocumentFragment();
        data.amendments.forEach(amendment => {
            const clone = template.content.cloneNode(true);
            const amendmentElement = clone.querySelector('.card');
            updateAmendment(amendmentElement, amendment);
            newContent.appendChild(amendmentElement);
        });

        // Remplacer tout le contenu d'un coup
        container.innerHTML = '';
        container.appendChild(newContent);

    } catch (error) {
        console.error('Error fetching amendments:', error);
    }
}

// Intercepter les soumissions de formulaire pour les gérer en AJAX
document.addEventListener('submit', async function(e) {
    const form = e.target;
    if (form.matches('.amendment-card form')) {
        e.preventDefault();
        
        try {
            const formData = new FormData(form);
            console.log('Sending request to:', form.action);
            console.log('Form data:', Object.fromEntries(formData));
            
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            // Debug: afficher la réponse brute
            const rawResponse = await response.text();
            console.log('Raw response:', rawResponse);
            
            let result;
            try {
                result = JSON.parse(rawResponse);
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                throw new Error('Invalid response from server: ' + rawResponse.substring(0, 100));
            }
            
            if (result.success) {
                // Afficher le message de succès
                const successAlert = document.getElementById('success-alert');
                const successMessage = document.getElementById('success-message');
                successMessage.textContent = result.message;
                successAlert.style.display = 'block';
                
                // Supprimer l'amendement de la liste
                const amendmentCard = form.closest('.amendment-card');
                amendmentCard.remove();
                
                // Mettre à jour le compteur d'amendements
                updateAmendmentCount();
                
                // Masquer le message après 3 secondes
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 3000);
            } else {
                throw new Error(result.message || 'An error occurred');
            }
        } catch (error) {
            console.error('Error details:', error);
            // Afficher l'erreur
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger alert-dismissible fade show';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.page-header').insertAdjacentElement('afterend', errorAlert);
        }
    }
});

function updateAmendmentCount() {
    const count = document.querySelectorAll('.amendment-card').length;
    const countBadge = document.getElementById('amendments-count');
    countBadge.textContent = `${count} Amendment${count !== 1 ? 's' : ''}`;
}

// Charger les amendements initialement
fetchAmendments();

// Actualiser les amendements toutes les minutes
const REFRESH_INTERVAL = 60000; // 60 secondes * 1000 = 1 minute
setInterval(fetchAmendments, REFRESH_INTERVAL);
</script>

<?php require_once 'includes/footer.php'; ?>
