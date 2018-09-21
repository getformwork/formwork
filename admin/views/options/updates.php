<div id="updater-component" class="component">
    <h3 class="caption"><?= $this->label('options.options') ?></h3>
    <?= $tabs ?>
    <div class="row">
        <div class="col-m-1-1">
            <div class="checker"><span class="spinner"></span><span class="update-status" data-checking-text="<?= $this->label('updates.status.checking') ?>" data-installing-text="<?= $this->label('updates.status.installing') ?>"><?= $this->label('updates.status.checking') ?></span></div>
        </div>
    </div>
    <div class="separator-l"></div>
    <div class="row new-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="new-version-name">Formwork x.x.x</strong> <?= $this->label('updates.available-for-install') ?></div>
            <div><?= $this->label('updates.install-prompt') ?></div>
            <div class="separator"></div>
            <button data-command="install-updates"><?= $this->label('updates.install') ?></button>
        </div>
    </div>
    <div class="row current-version" style="display: none;">
        <div class="col-m-1-1">
            <div class="h5"><strong class="current-version-name">Formwork <?= $currentVersion ?></strong> <?= $this->label('updates.latest-version-available') ?></div>
        </div>
    </div>
</div>
