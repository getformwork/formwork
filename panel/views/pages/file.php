<?php $this->layout('panel') ?>
<form method="post" enctype="multipart/form-data" data-form="page-file-form">
    <div class="header">
        <div class="min-w-0 flex-grow-1">
            <div class="header-title"><?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?> <?= $file->name() ?></div>
            <div><a class="link-secondary text-size-sm" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>"><span class="mr-2"><?= $this->icon('arrow-left-circle') ?></span><?= $this->translate('panel.pages.file.backToPage') ?></a></div>
        </div>
        <div>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousFile]) ?>" role="button" <?php if ($previousFile) : ?>href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . ($previousFile->name()) . '/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.previousFile') ?>" aria-label="<?= $this->translate('panel.pages.previousFile') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextFile]) ?>" role="button" <?php if ($nextFile) : ?>href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . ($nextFile->name()) . '/') ?>" <?php endif ?> title="<?= $this->translate('panel.pages.nextFile') ?>" aria-label="<?= $this->translate('panel.pages.nextFile') ?>"><?= $this->icon('chevron-right') ?></a>
            <?php if ($panel->user()->permissions()->has('pages.renameFiles')) : ?>
                <button type="button" class="button button-link" data-modal="renameFileModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/rename/') ?>" data-filename="<?= $file->name() ?>" title="<?= $this->translate('panel.pages.renameFile')  ?>" aria-label="<?= $this->translate('panel.pages.renameFile')  ?>"><?= $this->icon('pencil') ?></button>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('pages.replaceFiles')) : ?>
                <button type="button" class="button button-link" data-command="replaceFile" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/replace/') ?>" data-extension=".<?= $file->extension() ?>" title="<?= $this->translate('panel.pages.replaceFile')  ?>" aria-label="<?= $this->translate('panel.pages.replaceFile')  ?>"><?= $this->icon('cloud-upload') ?></button>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('pages.deleteFiles')) : ?>
                <button type="button" class="button button-link" data-modal="deleteFileModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/file/' . $file->name() . '/delete/') ?>" title="<?= $this->translate('panel.pages.deleteFile')  ?>" aria-label="<?= $this->translate('panel.pages.deleteFile')  ?>"><?= $this->icon('trash') ?></button>
            <?php endif ?>
            <?php if (!$file->fields()->isEmpty()): ?>
                <button type="submit" class="button button-accent" data-command="save"><?= $this->icon('check-circle') ?> <?= $this->translate('panel.modal.action.save') ?></button>
            <?php endif ?>
        </div>
    </div>
    <?php if ($file->type() === 'image') : ?>
        <div class="sections">
            <section class="section">
                <div class="section-header">
                    <span class="caption"><?= $this->translate('panel.pages.file.preview') ?></span>
                </div>
                <div class="section-content page-file-preview-container">
                    <a class="page-file-preview-link" href="<?= $file->uri() ?>"><img class="<?= $this->classes(['page-file-preview-image', 'has-no-width' => $file->mimeType() === 'image/svg+xml' && $file->info()->width() === 0]) ?>" src="<?= $file->uri() ?>"></a>
                </div>
            </section>
        </div>
    <?php endif ?>
    <?php if ($file->type() === 'video') : ?>
        <section class="section">
            <div class="section-header">
                <span class="caption"><?= $this->translate('panel.pages.file.preview') ?></span>
            </div>
            <div class="section-content page-file-preview-container">
                <video class="page-file-preview-video" controls playsinline>
                    <source src="<?= $file->uri() ?>" type="<?= $file->mimeType() ?>" />
                </video>
            </div>
        </section>
    <?php endif ?>
    <section class="section">
        <div class="section-header">
            <span class="caption"><?= $this->translate('panel.pages.file.info') ?></span>
        </div>
        <div class="section-content">
            <div class="row">
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.mimeType') ?>:</div>
                    <?= $file->mimeType() ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.size') ?>:</div>
                    <?= $file->size() ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.lastModifiedTime') ?>:</div>
                    <?= $this->datetime($file->lastModifiedTime()) ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="page-file-info-entry-title"><?= $this->translate('panel.pages.file.info.uri') ?>:</div>
                    <a class="page-file-info-entry-uri" href="<?= $file->uri() ?>"><?= $file->uri() ?></a>
                </div>
                <?php if ($file->type() === 'image') : ?>
                    <?php $this->insert('_files/images/info/info', ['file' => $file]) ?>
                <?php endif ?>
            </div>
        </div>
    </section>
    <?php if ($file->type() === 'image') : ?>
        <?php if ($file->hasExifData() && $file->getExifData()->hasPositionData()) : ?>
            <section class="section collapsible">
                <div class="section-header">
                    <button type="button" class="button section-toggle mr-2" title="<?= $this->translate('panel.sections.toggle') ?>" aria-label="<?= $this->translate('panel.sections.toggle') ?>"><?= $this->icon('chevron-up') ?></button>
                    <span class="caption"><?= $this->translate('panel.pages.file.position') ?></span>
                </div>
                <div class="section-content">
                    <?php $this->insert('_files/images/position/map', ['exif' => $file->getExifData()]) ?>
                </div>
            </section>
        <?php endif ?>
        <?php if ($file->hasExifData()) : ?>
            <section class="section collapsible collapsed">
                <div class="section-header">
                    <button type="button" class="button section-toggle mr-2" title="<?= $this->translate('panel.sections.toggle') ?>" aria-label="<?= $this->translate('panel.sections.toggle') ?>"><?= $this->icon('chevron-up') ?></button>
                    <span class="caption"><?= $this->translate('panel.pages.file.exif') ?></span>
                </div>
                <div class="section-content">
                    <?php $this->insert('_files/images/exif/data', ['exif' => $file->getExifData()]) ?>
                </div>
            </section>
        <?php endif ?>
    <?php endif ?>
    <input type="hidden" name="csrf-token" value="<?= $csrfToken ?>">
    <?php if (!$file->fields()->isEmpty()): ?>
        <?php $this->insert('fields', ['fields' => $file->fields()]) ?>
    <?php endif ?>
</form>