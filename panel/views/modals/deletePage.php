<div id="deletePageModal" class="modal" aria-labelledby="deletePageModalLabel">
    <div class="modal-container">
        <form action="" method="post">
            <div class="modal-header">
                <h3 class="caption" id="deletePageModalLabel"><?= $this->translate('panel.pages.deletePage') ?></h3>
            </div>
            <div class="modal-content">
                <p class="modal-text"><?= $this->translate('panel.pages.deletePage.prompt') ?></p>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" class="button" data-dismiss="deletePageModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button button-error button-right" data-command="delete"><?= $this->icon('trash') ?> <?= $this->translate('panel.modal.action.delete') ?></button>
            </div>
        </form>
    </div>
</div>
