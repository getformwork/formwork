<?php $this->layout('panel') ?>

<header class="panel-header">
    <div class="panel-header-page-info">
        <div class="panel-header-page-icon">
            <?= $this->icon('home') ?>
        </div>
        <div class="panel-header-page-title">
            <div class="panel-header-page-title-text"><?= $this->translate('panel.dashboard.dashboard') ?></div>
        </div>
    </div>
</header>

<div data-view="dashboard">

    <!-- Welcome Section -->
    <section class="section">
        <div class="row">
            <div class="col-md-1-1">
                <span class="h4"><?= $this->translate('panel.dashboard.welcome') ?></span>
            </div>
        </div>
    </section>

    <!-- Quick Actions Grid -->
    <section class="section">
        <div class="section-header">
            <div class="caption"><?= $this->translate('panel.dashboard.quickActions') ?></div>
        </div>
        <div class="row">
            <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
                <div class="col-xs-1-2 col-md-1-4">
                    <button type="button" class="button button-accent" style="width: 100%; height: 100%; min-height: 3rem;" data-modal="newPageModal">
                        <?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?>
                    </button>
                </div>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('panel.cache.clear')) : ?>
                <div class="col-xs-1-2 col-md-1-4">
                    <div class="dropdown" style="width: 100%; height: 100%; min-height: 3rem;">
                        <div class="button-group" style="width: 100%; height: 100%;">
                            <button type="button" class="button button-secondary" style="flex: 1; height: 100%;" data-command="clear-cache">
                                <?= $this->icon('cache-clear') ?> <?= $this->translate('panel.cache.clear') ?>
                            </button>
                            <button type="button" class="button button-secondary dropdown-button caret" style="height: 100%;" data-dropdown="dropdown-cache-options"></button>
                        </div>
                        <div class="dropdown-menu" id="dropdown-cache-options">
                            <button type="button" class="dropdown-item" data-command="clear-pages-cache"><?= $this->translate('panel.cache.clear.pages') ?></button>
                            <button type="button" class="dropdown-item" data-command="clear-images-cache"><?= $this->translate('panel.cache.clear.images') ?></button>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('panel.backup')) : ?>
                <div class="col-xs-1-2 col-md-1-4">
                    <a class="button button-secondary" role="button" href="<?= $panel->uri('/tools/backups/') ?>" style="width: 100%; height: 100%; min-height: 3rem; display: flex; align-items: center; justify-content: center;">
                        <?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.tools.backups') ?>
                    </a>
                </div>
            <?php endif ?>
            <?php if ($panel->user()->permissions()->has('panel.options.updates')) : ?>
                <div class="col-xs-1-2 col-md-1-4">
                    <a class="button button-secondary" role="button" href="<?= $panel->uri('/tools/updates/') ?>" style="width: 100%; height: 100%; min-height: 3rem; display: flex; align-items: center; justify-content: center;">
                        <?= $this->icon('arrows-rotate-clockwise') ?> <?= $this->translate('panel.updates.check') ?>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </section>

    <!-- Statistics Chart -->
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

    <!-- Last Modified Pages -->
    <section class="section">
        <div class="section-header">
            <div class="caption"><?= $this->translate('panel.dashboard.lastModifiedPages') ?></div>
        </div>
        <?= $lastModifiedPages ?>
    </section>
</div>