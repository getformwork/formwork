<?php $this->modals()->addMultiple(['deleteFileItem', 'renameFileItem']) ?>

<div <?= $this->attr(['class' => 'files-list', 'data-for' => $name, 'hidden' => count($files) === 0]) ?>>
    <div class="flex flex-wrap">
        <div class="flex-grow-1 mr-4">
            <div class="form-input-wrap">
                <span class="form-input-icon"><?= $this->icon('search') ?></span>
                <input class="form-input files-search" id="files.search" type="search" placeholder="<?= $this->translate('panel.files.search') ?>">
            </div>
        </div>
        <fieldset class="form-togglegroup files-list-view-as" data-for="<?= $name ?>">
            <label class="form-label"><input class="form-input" type="radio" name="<?= $name ?>-list-view-as" value="list" checked aria-label="<?= $this->translate('panel.files.viewAsList') ?>" data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsList') ?>"><?= $this->icon('file-list') ?></span></label>
            <label class="form-label"><input class="form-input" type="radio" name="<?= $name ?>-list-view-as" value="thumbnails" aria-label="<?= $this->translate('panel.files.viewAsThumbnails') ?>" data-form-ignore="true" autocomplete="off"><span title="<?= $this->translate('panel.files.viewAsThumbnails') ?>"><?= $this->icon('file-icons') ?></span></label>
        </fieldset>
    </div>

    <div class="files-list-headers">
        <div class="files-headers-cell file-name truncate"><?= $this->translate('panel.files.info.name') ?></div>
        <?php if (in_array('parent', $columns, true)) : ?>
            <div class="files-headers-cell file-parent truncate show-from-lg"><?= $this->translate('panel.files.parent') ?></div>
        <?php endif ?>
        <?php if (in_array('date', $columns, true)) : ?>
            <div class="files-headers-cell file-date truncate show-from-lg"><?= $this->translate('panel.files.info.lastModifiedTime') ?></div>
        <?php endif ?>
        <?php if (in_array('size', $columns, true)) : ?>
            <div class="files-headers-cell file-size truncate show-from-lg"><?= $this->translate('panel.files.info.size') ?></div>
        <?php endif ?>
        <div class="files-headers-cell file-actions"><span class="show-from-xs mr-6"><?= $this->translate('panel.files.actions') ?></span></div>
    </div>

    <div class="files-items">
        <?php foreach ($files as [$file, $model]) : ?>
            <?php $this->insert('partials.files.file.item', ['file' => $file, 'model' => $model, 'columns' => $columns]) ?>
        <?php endforeach ?>
    </div>
</div>

<template id="files-item">
    <div class="files-item" data-filename="">
        <div class="file-thumbnail"></div>
        <div class="files-item-cell file-icon"></div>
        <div class="files-item-cell file-name truncate"><a></a></div>
        <?php if (in_array('parent', $columns, true)) : ?>
            <div class="files-item-cell file-parent truncate show-from-lg"></div>
        <?php endif ?>
        <?php if (in_array('date', $columns, true)) : ?>
            <div class="files-item-cell file-date truncate show-from-lg"></div>
        <?php endif ?>
        <?php if (in_array('size', $columns, true)) : ?>
            <div class="files-item-cell file-size truncate show-from-lg"></div>
        <?php endif ?>
        <div class="files-item-cell file-actions">
            <div class="dropdown">
                <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.files.actions') ?>" aria-label="<?= $this->translate('panel.files.actions') ?>" data-dropdown=""><?= $this->icon('ellipsis-v') ?></button>
                <div class="dropdown-menu" id="">
                    <a class="dropdown-item" data-command="infoFile" href=""><?= $this->icon('info-circle') ?> <?= $this->translate('panel.files.info') ?></a>
                    <a class="dropdown-item" data-command="previewFile" href="" target=""><?= $this->icon('eye') ?> <?= $this->translate('panel.pages.previewFile') ?></a>
                    <?php if ($panel->user()->permissions()->has('panel.pages.renameFiles')) : ?>
                        <button type="button" class="dropdown-item" data-command="renameFile" data-modal="renameFileItemModal" data-action=""><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.renameFile') ?></button>
                    <?php endif ?>
                    <?php if ($panel->user()->permissions()->has('panel.pages.replaceFiles')) : ?>
                        <button type="button" class="dropdown-item" data-command="replaceFile" data-action="" data-mimetype=""><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.pages.replaceFile') ?></button>
                    <?php endif ?>
                    <?php if ($panel->user()->permissions()->has('panel.pages.deleteFiles')) : ?>
                        <button type="button" class="dropdown-item" data-command="deleteFile" data-modal="deleteFileItemModal" data-action=""><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deleteFile') ?></button>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</template>
