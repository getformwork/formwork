<div id="deleteFileModal" class="modal">
    <div class="modal-content">
        <form action="" method="post">
            <h3 class="caption"><?= $this->label('pages.delete-file') ?></h3>
            <p class="modal-text"><?= $this->label('pages.delete-file.prompt') ?></p>
            <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
            <button type="button" data-dismiss="deleteFileModal"><?= $this->label('modal.action.cancel') ?></button>
            <button type="submit" class="button-error button-right"><i class="i-trash"></i> <?= $this->label('modal.action.delete') ?></button>
        </form>
    </div>
</div>
