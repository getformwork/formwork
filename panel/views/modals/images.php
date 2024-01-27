<div id="imagesModal" class="modal" aria-labelledby="imagesModalLabel">
    <div class="modal-container modal-size-large">
        <div class="modal-header">
            <h3 class="caption" id="imagesModalLabel"><?= $this->translate('panel.modal.images.title') ?></h3>
        </div>
        <div class="modal-content">
            <div class="image-picker-empty-state">
                <span class="image-picker-empty-state-icon"><?= $this->icon('image') ?></span>
                <h4 class="h5"><?= $this->translate('panel.modal.images.noImages') ?></h4>
                <?php if ($panel->user()->permissions()->has('pages.uploadFiles')): ?>
                    <p><?= $this->translate('panel.modal.images.noImages.upload') ?></p>
                    <button type="button" class="button" data-command="upload" data-upload-target="file-uploader"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.modal.action.uploadFile') ?></button>
                <?php endif ?>
            </div>
            <select class="form-input image-picker">
                <?php foreach ($page->images() as $image): ?>
                    <option value="<?= $page->uri($image, includeLanguage: false) ?>"><?= $image ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-secondary" data-dismiss="imagesModal"><?= $this->icon('times-circle') ?> <?= $this->translate('panel.modal.action.cancel') ?></button>
            <button type="button" class="button button-accent button-right image-picker-confirm"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.continue') ?></button>
        </div>
    </div>
</div>
