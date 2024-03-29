<div id="changesModal" class="modal" aria-labelledby="changesModalLabel">
    <div class="modal-container">
        <div class="modal-header">
            <div class="caption" id="changesModalLabel"><?= $this->translate('panel.pages.changes.detected') ?></div>
        </div>
        <div class="modal-content">
            <p class="modal-text"><?= $this->translate('panel.pages.changes.detected.prompt') ?></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary" data-dismiss="changesModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="button" class="button button-accent button-right" data-command="continue" data-href="#"><?= $this->icon('exclamation-circle') ?> <?= $this->translate('panel.pages.changes.continue') ?></button>
        </div>
    </div>
</div>
