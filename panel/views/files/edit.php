<?php $this->layout('panel') ?>

<?php $this->modals()->addMultiple(['changes', 'renameFile', 'deleteFile']) ?>

<form method="post" enctype="multipart/form-data" data-form="file-form">
    <div class="header">
        <div class="min-w-0 flex-grow-1">
            <div class="flex">
                <div class="header-icon"><?= $this->icon(is_null($file->type()) ? 'file' : 'file-' . $file->type()) ?></div>
                <div class="header-title truncate"><?= $this->escape($file->name()) ?></div>
            </div>
            <div class="truncate">
                <?php if ($model->getModelIdentifier() === 'page'): ?>
                    <a class="link-secondary text-size-sm" href="<?= $panel->uri('/pages/' . trim($model->route(), '/') . '/edit/') ?>"><span class="mr-2"><?= $this->icon($model->icon()) ?></span><?= $this->escape($model->title()) ?></a>
                <?php endif ?>
                <?php if ($model->getModelIdentifier() === 'site') : ?>
                    <a class="link-secondary text-size-sm" href="<?= $panel->uri('/files/') ?>"><span class="mr-2"><?= $this->icon('globe') ?></span><?= $this->translate('panel.options.site') ?></a>
                <?php endif ?>
            </div>
        </div>
        <div>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$previousFile]) ?>" role="button" <?php if ($previousFile) : ?>href="<?= $this->uri($app->router()->generate('panel.files.edit', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $previousFile->name()])) ?>" <?php endif ?> title="<?= $this->translate('panel.files.previous') ?>" aria-label="<?= $this->translate('panel.files.previous') ?>"><?= $this->icon('chevron-left') ?></a>
            <a class="<?= $this->classes(['button', 'button-link', 'show-from-md', 'disabled' => !$nextFile]) ?>" role="button" <?php if ($nextFile) : ?>href="<?= $this->uri($app->router()->generate('panel.files.edit', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $nextFile->name()])) ?>" <?php endif ?> title="<?= $this->translate('panel.files.next') ?>" aria-label="<?= $this->translate('panel.files.next') ?>"><?= $this->icon('chevron-right') ?></a>
            <?php if ($panel->user()->permissions()->has('panel.pages.renameFiles')) : ?>
                <button type="button" class="button button-link" data-modal="renameFileModal" data-modal-action="<?= $this->uri($app->router()->generate('panel.files.rename', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>" data-filename="<?= $file->name() ?>" title="<?= $this->translate('panel.pages.renameFile')  ?>" aria-label="<?= $this->translate('panel.pages.renameFile')  ?>"><?= $this->icon('pencil') ?></button>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('panel.pages.replaceFiles')) : ?>
                <button type="button" class="button button-link" data-command="replaceFile" data-action="<?= $this->uri($app->router()->generate('panel.files.replace', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>" data-extension=".<?= $file->extension() ?>" title="<?= $this->translate('panel.pages.replaceFile')  ?>" aria-label="<?= $this->translate('panel.pages.replaceFile')  ?>"><?= $this->icon('cloud-upload') ?></button>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('panel.pages.deleteFiles')) : ?>
                <button type="button" class="button button-link" data-modal="deleteFileModal" data-modal-action="<?= $this->uri($app->router()->generate('panel.files.delete', ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()])) ?>" title="<?= $this->translate('panel.pages.deleteFile')  ?>" aria-label="<?= $this->translate('panel.pages.deleteFile')  ?>"><?= $this->icon('trash') ?></button>
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
                    <span class="caption"><?= $this->translate('panel.files.preview') ?></span>
                </div>
                <div class="section-content file-preview-container">
                    <a class="file-preview-link" href="<?= $file->uri() ?>"><img class="<?= $this->classes(['file-preview-image', 'has-no-width' => $file->mimeType() === 'image/svg+xml' && $file->info()->width() === 0]) ?>" src="<?= $file->uri() ?>"></a>
                </div>
            </section>
        </div>
    <?php endif ?>
    <?php if ($file->type() === 'video') : ?>
        <section class="section">
            <div class="section-header">
                <span class="caption"><?= $this->translate('panel.files.preview') ?></span>
            </div>
            <div class="section-content file-preview-container">
                <video class="file-preview-video" controls playsinline>
                    <source src="<?= $file->uri() ?>" type="<?= $file->mimeType() ?>" />
                </video>
            </div>
        </section>
    <?php endif ?>
    <?php if ($file->type() === 'audio') : ?>
        <section class="section">
            <div class="section-header">
                <span class="caption"><?= $this->translate('panel.files.preview') ?></span>
            </div>
            <div class="section-content file-preview-container">
                <audio class="file-preview-audio" controls>
                    <source src="<?= $file->uri() ?>" type="<?= $file->mimeType() ?>" />
                </audio>
            </div>
        </section>
    <?php endif ?>
    <?php if ($file->type() === 'pdf') : ?>
        <section class="section">
            <div class="section-header">
                <span class="caption"><?= $this->translate('panel.files.preview') ?></span>
            </div>
            <div class="section-content file-preview-container">
                <embed class="file-preview-pdf" src="<?= $file->uri() ?>" type="<?= $file->mimeType() ?>" />
            </div>
        </section>
    <?php endif ?>
    <section class="section">
        <div class="section-header">
            <span class="caption"><?= $this->translate('panel.files.info') ?></span>
        </div>
        <div class="section-content">
            <div class="row">
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.mimeType') ?>:</div>
                    <?= $file->mimeType() ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.size') ?>:</div>
                    <?= $file->size() ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.lastModifiedTime') ?>:</div>
                    <?= $this->datetime($file->lastModifiedTime()) ?>
                </div>
                <div class="col-sm-1-2 col-md-1-4 mb-4">
                    <div class="file-info-entry-title"><?= $this->translate('panel.files.info.uri') ?>:</div>
                    <a class="file-info-entry-uri" href="<?= $file->uri() ?>"><?= $this->escape($file->uri()) ?></a>
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
                    <span class="caption"><?= $this->translate('panel.files.position') ?></span>
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
                    <span class="caption"><?= $this->translate('panel.files.exif') ?></span>
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
