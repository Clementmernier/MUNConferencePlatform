<!-- New Motion Modal -->
<div class="modal fade" id="newMotionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Motion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newMotionForm">
                    <div class="mb-3">
                        <label class="form-label">Motion Type</label>
                        <select class="form-select" name="type" required>
                            <option value="">Select a motion...</option>
                            <option value="moderated">Moderated Caucus</option>
                            <option value="unmoderated">Unmoderated Caucus</option>
                            <option value="extension">Extension of Caucus</option>
                            <option value="voting">Enter Voting Procedure</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" name="duration" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose/Topic</label>
                        <input type="text" class="form-control" name="purpose" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="newMotionForm" class="btn btn-primary">Submit Motion</button>
            </div>
        </div>
    </div>
</div>
