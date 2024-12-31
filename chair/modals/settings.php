<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Committee Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="committeeSettingsForm">
                    <div class="mb-3">
                        <label class="form-label">Default Speaking Time (minutes)</label>
                        <input type="number" class="form-control" name="speakingTime" value="1" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Voting Majority Required</label>
                        <select class="form-select" name="votingMajority">
                            <option value="simple">Simple Majority</option>
                            <option value="two-thirds">Two-thirds Majority</option>
                            <option value="consensus">Consensus</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="autoTimer" name="autoTimer">
                        <label class="form-check-label" for="autoTimer">Auto-start timer for speeches</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roll Call Frequency</label>
                        <select class="form-select" name="rollCallFrequency">
                            <option value="start">Start of Session Only</option>
                            <option value="voting">Before Each Vote</option>
                            <option value="caucus">After Each Caucus</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yielding Rules</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="yieldToQuestions" name="yieldToQuestions" checked>
                            <label class="form-check-label" for="yieldToQuestions">Allow yield to questions</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="yieldToComments" name="yieldToComments" checked>
                            <label class="form-check-label" for="yieldToComments">Allow yield to comments</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="yieldToOther" name="yieldToOther" checked>
                            <label class="form-check-label" for="yieldToOther">Allow yield to another delegate</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Submission Rules</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="requireSignatures" name="requireSignatures" checked>
                            <label class="form-check-label" for="requireSignatures">Require signatories for working papers</label>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Minimum signatories required</label>
                            <input type="number" class="form-control" name="minSignatories" value="3" min="1">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="committeeSettingsForm" class="btn btn-primary">Save Settings</button>
            </div>
        </div>
    </div>
</div>
