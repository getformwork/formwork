<?php $this->layout('@panel.panel') ?>

<div data-view="dashboard">
    <div class="header">
        <div class="header-icon"><?= $this->icon('home') ?></div>
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
                <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
                    <button type="button" class="button button-accent mb-4" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('panel.cache.clear')) : ?>
                    <div class="dropdown mb-4">
                        <div class="button-group">
                            <button type="button" class="button button-secondary" data-command="clear-cache"><?= $this->icon('cache-clear') ?> <?= $this->translate('panel.cache.clear') ?></button>
                            <button type="button" class="button button-secondary dropdown-button caret" data-dropdown="dropdown-cache-options"></button>
                        </div>
                        <div class="dropdown-menu" id="dropdown-cache-options">
                            <button type="button" class="dropdown-item" data-command="clear-pages-cache"><?= $this->translate('panel.cache.clear.pages') ?></button>
                            <button type="button" class="dropdown-item" data-command="clear-images-cache"><?= $this->translate('panel.cache.clear.images') ?></button>
                            <button type="button" class="dropdown-item" data-command="clear-config-cache"><?= $this->translate('panel.cache.clear.config') ?></button>
                            <hr class="dropdown-separator">
                            <button type="button" class="dropdown-item" data-command="clear-all-cache"><?= $this->translate('panel.cache.clear.all') ?></button>
                        </div>
                    </div>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('panel.backup')) : ?>
                    <a class="button button-secondary mb-4" role="button" href="<?= $panel->uri('/tools/backups/') ?>"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.tools.backups') ?></a>
                <?php endif ?>
                <?php if ($panel->user()->permissions()->has('panel.options.updates')) : ?>
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
                <div class="dashboard-chart ct-chart is-loading" data-chart-data="<?= $this->escapeAttr($statistics) ?>"></div>
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
