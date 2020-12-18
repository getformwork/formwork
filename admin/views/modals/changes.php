<div id="changesModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->translate('pages.changes.detected') ?></h3>
        <p class="modal-text"><?= $this->translate('pages.changes.detected.prompt') ?></p>
        <button type="button" data-dismiss="changesModal"><?= $this->translate('modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right" data-command="continue" data-href="#"><?= $this->translate('pages.changes.continue') ?></button>
    </div>
</div>
