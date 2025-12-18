<?php $this->layout('panel') ?>
<?php $this->modals()->add('uploadFile') ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon('files') ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->translate('panel.files.files') ?></div>
        </div>
    </div>

    <div class="panel-header-actions">
        <?php if ($panel->user()->permissions()->has('panel.files.upload')) : ?>
            <button type="button" class="button button-accent" data-modal="uploadFileModal"><?= $this->icon('cloud-upload') ?> <?= $this->translate('panel.files.upload') ?></button>
        <?php endif ?>
    </div>
</header>

<div data-view="files">

    <div class="section">
        <div class="section-content">
            <?php $this->insert('partials.files.file.list', ['name' => 'view-files', 'files' => $files, 'columns' => ['parent', 'date', 'size']]) ?>
        </div>
    </div>
</div>