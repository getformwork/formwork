<div id="imagesModal" class="modal">
    <div class="modal-content modal-size-large">
        <h3 class="caption"><?= $this->translate('panel.modal.images.title') ?></h3>
        <div class="image-picker-empty-state">
            <span class="image-picker-empty-state-icon"><?= $this->icon('image') ?></span>
            <h4 class="h5"><?= $this->translate('panel.modal.images.noImages') ?></h4>
<?php
            if ($panel->user()->permissions()->has('pages.uploadFiles')):
                ?>
            <p><?= $this->translate('panel.modal.images.noImages.upload') ?></p>
            <button type="button" data-command="upload" data-upload-target="file-uploader"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.modal.action.uploadFile') ?></button>
<?php
            endif
        ?>
        </div>
        <select class="image-picker">
<?php
                foreach ($page->images() as $image):
                    ?>
            <option value="<?= $page->uri($image, includeLanguage: false) ?>"><?= $image ?></option>
<?php
                endforeach
        ?>
        </select>
        <button type="button" data-dismiss="imagesModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
        <button type="button" class="button-accent button-right image-picker-confirm" data-dismiss="imagesModal"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
    </div>
</div>
