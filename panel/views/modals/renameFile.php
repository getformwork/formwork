<div id="renameFileModal" class="modal">
    <div class="modal-content">
        <form action="" method="post">
            <h3 class="caption"><?= $this->translate('panel.pages.renameFile') ?></h3>
            <label class="label-required" for="filename"><?= $this->translate('panel.pages.renameFile.name')?>:</label>
            <input id="file-name" type="text" required name="filename" autofocus>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            <button type="button" data-dismiss="renameFileModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="submit" class="button-accent button-right" data-command="delete"><?= $this->icon('pencil') ?> <?= $this->translate('panel.modal.action.rename') ?></button>
        </form>
    </div>
</div>
