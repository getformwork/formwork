<div id="deleteFileModal" class="modal" aria-labelledby="deleteFileModalLabel">
    <div class="modal-container">
        <form action="" method="post">
        <div class="modal-header">
            <h3 class="caption" id="deleteFileModalLabel"><?= $this->translate('panel.pages.deleteFile') ?></h3>
        </div>
        <div class="modal-content">
            <p class="modal-text"><?= $this->translate('panel.pages.deleteFile.prompt') ?></p>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
        </div>
        <div class="modal-footer">
            <button type="button" data-dismiss="deleteFileModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="submit" class="button-error button-right" data-command="delete"><?= $this->icon('trash') ?> <?= $this->translate('panel.modal.action.delete') ?></button>
        </div>
        </form>
    </div>
</div>
