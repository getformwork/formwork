<div class="files-list">
    <div>
        <fieldset class="form-togglegroup files-list-view-as" data-field-name=" <?= $field->name() ?>">
            <label class="form-label"><input class="form-input" type="radio" name="<?= $field->name() ?>-list-view-as" value="list" checked data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsList') ?>"><?= $this->icon('file-list') ?></span></label>
            <label class="form-label"><input class="form-input" type="radio" name="<?= $field->name() ?>-list-view-as" value="thumbnails" data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsThumbnails') ?>"><?= $this->icon('file-icons') ?></span></label>
        </fieldset>
    </div>

    <div class="files-items">
        <?php foreach ($files as $file) : ?>
            <div class="files-item" data-href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/') ?>">
                <?php if ($file->type() === 'image') : ?>
                    <div class="file-thumbnail" style="background-image:url('<?= $file->square(300, 'contain')->uri() ?>');"></div>
                <?php endif ?>
                <?php if ($file->type() === 'video') : ?>
                    <video class="file-thumbnail">
                        <source src="<?= $file->uri() ?>" type="<?= $file->mimeType() ?>" />
                    </video>
                <?php endif ?>
                <div class="file-icon"><?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?></div>
                <div class="file-name truncate"><?= $file->name() ?> <span class="file-size">(<?= $file->size() ?>)</span></div>
                <div class="dropdown">
                    <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.files.actions') ?>" data-dropdown="dropdown-<?= $file->hash() ?>"><?= $this->icon('ellipsis-v') ?></button>
                    <div class="dropdown-menu" id="dropdown-<?= $file->hash() ?>">
                        <?php if ($model?->getModelIdentifier() === 'page'): ?>
                            <a class="dropdown-item" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/') ?>"><?= $this->icon('info-circle') ?> <?= $this->translate('panel.pages.file.info') ?></a>
                        <?php endif ?>
                        <a class="dropdown-item" href="<?= $file->uri() ?>" target="formwork-preview-file-<?= $file->hash() ?>"><?= $this->icon('eye') ?> <?= $this->translate('panel.pages.previewFile') ?></a>
                        <?php if ($model?->getModelIdentifier() === 'page'): ?>
                            <?php if ($panel->user()->permissions()->has('pages.renameFiles')) : ?>
                                <a class="dropdown-item" data-modal="renameFileModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/rename/') ?>" data-filename="<?= $file->name() ?>"><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.renameFile') ?></a>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('pages.replaceFiles')) : ?>
                                <a class="dropdown-item" data-command="replaceFile" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/replace/') ?>" data-extension=".<?= $file->extension() ?>"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.pages.replaceFile') ?></a>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('pages.deleteFiles')) : ?>
                                <a class="dropdown-item" data-modal="deleteFileModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/delete/') ?>"><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deleteFile') ?></a>
                            <?php endif ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>