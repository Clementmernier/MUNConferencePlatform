<!-- Add Speaker Modal -->
<div class="modal fade" id="addSpeakerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add to Speakers List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Delegate</label>
                    <div class="delegates-list">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="delegateSearch" placeholder="Search delegate...">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <div class="list-group" id="delegatesList">
                            <!-- Delegates will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Speaking Time (minutes)</label>
                    <input type="number" class="form-control" id="speakingTime" value="1" min="1" max="10">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addSpeakerBtn" disabled>Add Speaker</button>
            </div>
        </div>
    </div>
</div>

<style>
.delegates-list {
    max-height: 300px;
    overflow-y: auto;
}

.list-group-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.list-group-item:hover {
    background-color: var(--light-gray);
}

.list-group-item.selected {
    background-color: var(--primary-color);
    color: white;
}
</style>

<script>
let selectedDelegateId = null;

// Charger les délégués quand le modal s'ouvre
$('#addSpeakerModal').on('show.bs.modal', function () {
    loadDelegates();
    selectedDelegateId = null;
    $('#addSpeakerBtn').prop('disabled', true);
    $('#delegateSearch').val('');
});

// Fonction pour charger les délégués
function loadDelegates() {
    $.ajax({
        url: 'get_delegates.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                displayDelegates(response.delegates);
            }
        },
        error: function(xhr) {
            console.error('Error loading delegates:', xhr.responseText);
        }
    });
}

// Fonction pour afficher les délégués
function displayDelegates(delegates) {
    const delegatesList = $('#delegatesList');
    delegatesList.empty();

    delegates.forEach(delegate => {
        const item = $('<a>')
            .addClass('list-group-item list-group-item-action')
            .html(`<strong>${delegate.country_name}</strong> - ${delegate.first_name} ${delegate.last_name}`)
            .data('delegate-id', delegate.id)
            .click(function() {
                $('.list-group-item').removeClass('selected');
                $(this).addClass('selected');
                selectedDelegateId = delegate.id;
                $('#addSpeakerBtn').prop('disabled', false);
            });
        delegatesList.append(item);
    });
}

// Recherche de délégués
$('#delegateSearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    $('.list-group-item').each(function() {
        const text = $(this).text().toLowerCase();
        $(this).toggle(text.includes(searchTerm));
    });
});

// Ajout du speaker sélectionné
$('#addSpeakerBtn').click(function() {
    if (!selectedDelegateId) return;

    const speakingTime = $('#speakingTime').val();
    
    // Appel à votre fonction existante addSpeaker avec les nouveaux paramètres
    addSpeaker(selectedDelegateId, speakingTime);
    
    // Fermer le modal
    $('#addSpeakerModal').modal('hide');
});
</script>
