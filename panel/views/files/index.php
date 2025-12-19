<?php $this->layout('@panel.panel') ?>
<?php $this->modals()->add('uploadFile') ?>

<div data-view="files">
    <header class="panel-header">
        <div class="header-icon"><?= $this->icon('files') ?></div>
        <div class="header-title"><?= $this->translate('panel.files.files') ?></div>
        <div>
            <?php if ($panel->user()->permissions()->has('panel.files.upload')) : ?>
                <button type="button" class="button button-accent" data-modal="uploadFileModal"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.files.upload') ?></button>
            <?php endif ?>
        </div>
    </header>

    <div class="section">
        <div class="section-content">
            <?php $this->insert('@panel._files.file.list', ['name' => 'view-files', 'files' => $files, 'columns' => ['parent', 'date', 'size']]) ?>
        </div>
    </div>
</div>
