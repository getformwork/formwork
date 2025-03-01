<?php $modals->addMultiple(['deleteFileItem', 'renameFileItem']) ?>

<div class="files-list" data-for="<?= $field->name() ?>">
    <div>
        <fieldset class="form-togglegroup files-list-view-as" data-for="<?= $field->name() ?>">
            <label class="form-label"><input class="form-input" type="radio" name="<?= $field->name() ?>-list-view-as" value="list" checked aria-label="<?= $this->translate('panel.files.viewAsList') ?>" data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsList') ?>"><?= $this->icon('file-list') ?></span></label>
            <label class="form-label"><input class="form-input" type="radio" name="<?= $field->name() ?>-list-view-as" value="thumbnails" aria-label="<?= $this->translate('panel.files.viewAsThumbnails') ?>" data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsThumbnails') ?>"><?= $this->icon('file-icons') ?></span></label>
        </fieldset>
    </div>

    <div class="files-items">
        <?php foreach ($files as $file) : ?>
            <div class="files-item" data-filename="<?= $file->name() ?>" data-href="<?= $panel->uri('/files/pages/' . trim($page->route(), '/') . '/' . $file->name()) ?>">
                <?php if ($file->type() === 'image') : ?>
                    <div class="file-thumbnail" style="background-image:url('<?= $file->square(300, 'contain')->uri() ?>');"></div>
                <?php endif ?>
                <?php if ($file->type() === 'video') : ?>
                    <video class="file-thumbnail" src="<?= $file->uri() ?>" preload="metadata"></video>
                <?php endif ?>
                <div class="file-icon"><?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?></div>
                <div class="file-item-label truncate"><span class="file-name"><?= $file->name() ?></span> <span class="file-size">(<?= $file->size() ?>)</span></div>
                <div class="dropdown">
                    <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.files.actions') ?>" aria-label="<?= $this->translate('panel.files.actions') ?>" data-dropdown="dropdown-<?= $file->hash() ?>"><?= $this->icon('ellipsis-v') ?></button>
                    <div class="dropdown-menu" id="dropdown-<?= $file->hash() ?>">
                        <?php if ($model?->getModelIdentifier() === 'page'): ?>
                            <a class="dropdown-item" href="<?= $panel->uri('/files/pages/' . trim($page->route(), '/') . '/' . $file->name()) ?>"><?= $this->icon('info-circle') ?> <?= $this->translate('panel.pages.file.info') ?></a>
                        <?php endif ?>
                        <a class="dropdown-item" href="<?= $file->uri() ?>" target="formwork-preview-file-<?= $file->hash() ?>"><?= $this->icon('eye') ?> <?= $this->translate('panel.pages.previewFile') ?></a>
                        <?php if ($model?->getModelIdentifier() === 'page'): ?>
                            <?php if ($panel->user()->permissions()->has('pages.renameFiles')) : ?>
                                <a class="dropdown-item" data-modal="renameFileItemModal" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/rename/') ?>"><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.renameFile') ?></a>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('pages.replaceFiles')) : ?>
                                <a class="dropdown-item" data-command="replaceFile" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/replace/') ?>" data-mimetype="<?= $file->mimeType() ?>"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.pages.replaceFile') ?></a>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('pages.deleteFiles')) : ?>
                                <a class="dropdown-item" data-modal="deleteFileItemModal" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/delete/') ?>"><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deleteFile') ?></a>
                            <?php endif ?>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>

<template id="files-item">
    <div class="files-item" data-filename="" data-href="<?= $panel->uri('/files/pages/' . trim($page->route(), '/') . '/') ?>">
        <div class="file-thumbnail"></div>
        <div class="file-icon"></div>
        <div class="file-item-label truncate"><span class="file-name"></span> <span class="file-size"></span></div>
        <div class="dropdown">
            <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.files.actions') ?>" aria-label="<?= $this->translate('panel.files.actions') ?>" data-dropdown=""><?= $this->icon('ellipsis-v') ?></button>
            <div class="dropdown-menu" id="">
                <a class="dropdown-item" data-command="infoFile" href="<?= $panel->uri('/files/pages/' . trim($page->route(), '/') . '/') ?>"><?= $this->icon('info-circle') ?> <?= $this->translate('panel.pages.file.info') ?></a>
                <a class="dropdown-item" data-command="previewFile" href="" target=""><?= $this->icon('eye') ?> <?= $this->translate('panel.pages.previewFile') ?></a>
                <?php if ($panel->user()->permissions()->has('pages.renameFiles')) : ?>
                    <a class="dropdown-item" data-modal="renameFileItemModal" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/rename/') ?>"><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.renameFile') ?></a>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('pages.replaceFiles')) : ?>
                    <a class="dropdown-item" data-command="replaceFile" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/replace/') ?>" data-mimetype=""><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.pages.replaceFile') ?></a>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('pages.deleteFiles')) : ?>
                    <a class="dropdown-item" data-modal="deleteFileItemModal" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/delete/') ?>"><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deleteFile') ?></a>
                <?php endif ?>
            </div>
        </div>
    </div>
</template>
