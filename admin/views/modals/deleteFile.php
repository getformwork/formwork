<div id="deleteFileModal" class="modal">
    <div class="modal-content">
        <form action="" method="post">
            <h3 class="caption"><?= $this->translate('admin.pages.delete-file') ?></h3>
            <p class="modal-text"><?= $this->translate('admin.pages.delete-file.prompt') ?></p>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            <button type="button" data-dismiss="deleteFileModal"><?= $this->icon('times-circle') ?> <?= $this->translate('admin.modal.action.cancel') ?></button>
            <button type="submit" class="button-error button-right" data-command="delete"><?= $this->icon('trash') ?> <?= $this->translate('admin.modal.action.delete') ?></button>
        </form>
    </div>
</div>
