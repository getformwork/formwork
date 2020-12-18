<?php $this->layout('admin') ?>
<div id="updater-component" class="component">
    <h3 class="caption"><?= $this->translate('options.options') ?></h3>
    <?= $tabs ?>
    <div class="row">
        <div class="col-m-1-1">
            <div class="checker"><span class="spinner"></span><span class="update-status" data-checking-text="<?= $this->translate('updates.status.checking') ?>" data-installing-text="<?= $this->translate('updates.status.installing') ?>"><?= $this->translate('updates.status.checking') ?></span></div>
        </div>
    </div>
    <div class="separator-l"></div>
    <div class="row new-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="new-version-name">Formwork x.x.x</strong> <?= $this->translate('updates.available-for-install') ?></div>
            <div><?= $this->translate('updates.install-prompt') ?></div>
            <div class="separator"></div>
            <button type="button" data-command="install-updates"><?= $this->translate('updates.install') ?></button>
        </div>
    </div>
    <div class="row current-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="current-version-name">Formwork <?= $currentVersion ?></strong> <?= $this->translate('updates.latest-version-available') ?></div>
        </div>
    </div>
</div>
