<?php $this->layout('panel') ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon('toolbox') ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->translate('panel.tools.tools') ?></div>
        </div>
    </div>
</header>


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
            <button type="button" class="button button-accent" data-command="install-updates"><?= $this->icon('cloud-download') ?> <?= $this->translate('panel.updates.install') ?></button>
        </div>
    </div>
    <div class="row current-version mt-9" style="display: none;">
        <div class="col-md-1-1">
            <p><strong class="current-version-name">Formwork <?= $currentVersion ?></strong> <?= $this->translate('panel.updates.latestVersionAvailable') ?></p>
        </div>
    </div>
</section>