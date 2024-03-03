<?php $this->layout('panel') ?>

<div data-view="dashboard">
    <div class="header">
        <div class="header-title"><?= $this->translate('panel.dashboard.dashboard') ?></div>
    </div>

    <div class="row">
        <div class="col-md-1-3">
            <section class="section">
                <span class="h4"><?= $this->translate('panel.dashboard.welcome') ?></span>
            </section>
            <section class="section">
                <div class="section-header">
                    <div class="caption"><?= $this->translate('panel.dashboard.quickActions') ?></div>
                </div>
                <?php if ($panel->user()->permissions()->has('pages.create')) : ?>
                    <button type="button" class="button button-secondary mb-4" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('cache.clear')) : ?>
                    <button type="button" class="button button-secondary mb-4" data-command="clear-cache"><?= $this->icon('cache-clear') ?> <?= $this->translate('panel.cache.clear') ?></button>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('backup')) : ?>
                    <button type="button" class="button button-secondary mb-4" data-command="make-backup"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.backup.backup') ?></button>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('options.updates')) : ?>
                    <a class="button button-secondary mb-4" role="button" href="<?= $panel->uri('/tools/updates/') ?>"><?= $this->icon('arrows-rotate-clockwise') ?> <?= $this->translate('panel.updates.check') ?></a>
                <?php endif ?>
            </section>
        </div>
        <div class="col-md-2-3">
            <section class="section">
                <div class="row">
                    <div class="col-xs-1-2">
                        <div class="section-header">
                            <div class="caption"><?= $this->translate('panel.dashboard.statistics') ?></div>
                        </div>
                    </div>
                    <div class="col-xs-1-2">
                        <div class="ct-legend ct-legend-right">
                            <span class="ct-legend-label ct-series-a mr-8"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.visits') ?></span>
                            <span class="ct-legend-label ct-series-b"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.uniqueVisitors') ?></span>
                        </div>
                    </div>
                </div>
                <div class="dashboard-chart ct-chart" data-chart-data="<?= $this->escapeAttr($statistics) ?>"></div>
            </section>
        </div>
    </div>
    <section class="section">
        <div class="section-header">
            <div class="caption"><?= $this->translate('panel.dashboard.lastModifiedPages') ?></div>
        </div>
        <?= $lastModifiedPages ?>
    </section>
</div>