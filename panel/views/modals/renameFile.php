<div id="renameFileModal" class="modal">
    <div class="modal-container">
        <form action="" method="post">
            <div class="modal-header">
                <h3 class="caption"><?= $this->translate('panel.pages.renameFile') ?></h3>
            </div>
            <div class="modal-content">
                <label class="label-required" for="filename"><?= $this->translate('panel.pages.renameFile.name')?>:</label>
                <input id="file-name" type="text" required name="filename" autofocus>
                <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="renameFileModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
                <button type="submit" class="button-accent button-right" data-command="delete"><?= $this->icon('pencil') ?> <?= $this->translate('panel.modal.action.rename') ?></button>
            </div>
        </form>
    </div>
</div>
