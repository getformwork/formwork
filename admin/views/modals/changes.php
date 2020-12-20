<div id="changesModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->translate('admin.pages.changes.detected') ?></h3>
        <p class="modal-text"><?= $this->translate('admin.pages.changes.detected.prompt') ?></p>
        <button type="button" data-dismiss="changesModal"><?= $this->translate('admin.modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right" data-command="continue" data-href="#"><?= $this->translate('admin.pages.changes.continue') ?></button>
    </div>
</div>
