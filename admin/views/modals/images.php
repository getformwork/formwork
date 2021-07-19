<div id="imagesModal" class="modal">
    <div class="modal-content modal-size-large">
        <h3 class="caption"><?= $this->translate('admin.modal.images.title') ?></h3>
        <div class="image-picker-empty-state">
            <span class="image-picker-empty-state-icon"><?= $this->icon('image') ?></span>
            <h4 class="h5"><?= $this->translate('admin.modal.images.no-images') ?></h4>
<?php
            if ($admin->user()->permissions()->has('pages.upload_files')):
?>
            <p><?= $this->translate('admin.modal.images.no-images.upload') ?></p>
            <button type="button" data-command="upload" data-upload-target="file-uploader"><?= $this->icon('cloud-upload') ?> <?= $this->translate('admin.modal.action.upload-file') ?></button>
<?php
            endif;
?>
        </div>
        <select class="image-picker">
<?php
        foreach ($page->images() as $image):
?>
            <option value="<?= $admin->pageUri($page) . $image ?>"><?= $image ?></option>
<?php
        endforeach;
?>
        </select>
        <button type="button" data-dismiss="imagesModal"><?= $this->icon('times-circle') ?> <?= $this->translate('admin.modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right image-picker-confirm" data-dismiss="imagesModal"><?= $this->icon('check-circle') ?> <?= $this->translate('admin.modal.action.continue') ?></button>
    </div>
</div>
