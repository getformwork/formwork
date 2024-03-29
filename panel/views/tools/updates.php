<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.tools.tools') ?></div>
</div>

<?= $tabs ?>

<section id="updater-component" class="section">
    <div class="row">
        <div class="col-md-1-1">
            <div class="checker"><span class="spinner"></span><span class="update-status" data-checking-text="<?= $this->translate('panel.updates.status.checking') ?>" data-installing-text="<?= $this->translate('panel.updates.status.installing') ?>"><?= $this->translate('panel.updates.status.checking') ?></span></div>
        </div>
    </div>
    <div class="row new-version mt-9" style="display: none;">
        <div class="col-md-1-1">
            <p><strong class="new-version-name">Formwork x.x.x</strong> <?= $this->translate('panel.updates.availableForInstall') ?></p>
            <div class="mb-8"><?= $this->translate('panel.updates.installPrompt') ?></div>
            <button type="button" class="button" data-command="install-updates"><?= $this->translate('panel.updates.install') ?></button>
        </div>
    </div>
    <div class="row current-version mt-9" style="display: none;">
        <div class="col-md-1-1">
            <p><strong class="current-version-name">Formwork <?= $currentVersion ?></strong> <?= $this->translate('panel.updates.latestVersionAvailable') ?></p>
        </div>
    </div>
</section>