<div id="changesModal" class="modal">
    <div class="modal-content">
        <h3 class="caption"><?= $this->label('pages.changes.detected') ?></h3>
        <p class="modal-text"><?= $this->label('pages.changes.detected.prompt') ?></p>
        <button data-dismiss="changesModal"><?= $this->label('modal.action.cancel') ?></button>
        <button class="button-accent button-right" data-command="continue" data-href="#"><?= $this->label('pages.changes.continue') ?></button>
    </div>
</div>
