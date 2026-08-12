<?php $this->layout('@panel.panel') ?>

<div class="header">
    <div class="header-icon"><?= $this->icon('chart-line') ?></div>
    <div class="header-title"><?= $this->translate('panel.statistics.statistics') ?></div>
</div>

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
    <div class="statistics-chart ct-chart is-loading" data-chart-data="<?= $this->escapeAttr($statistics) ?>"></div>
</section>
<section class="section">
    <div class="row text-align-center">
        <div class="col-xs-1-2 col-md-1-4">
            <div class="text-size-xxl text-bold text-color-blue"><?= $monthVisits ?></div>
            <span class="text-size-sm"><?= $this->translate('panel.statistics.monthlyVisits') ?></span>
        </div>
        <div class="col-xs-1-2 col-md-1-4">
            <div class="text-size-xxl text-bold text-color-amber"><?= $monthUniqueVisits ?></div>
            <span class="text-size-sm"><?= $this->translate('panel.statistics.monthlyUniqueVisitors') ?></span>
        </div>
        <div class="col-xs-1-2 col-md-1-4">
            <div class="text-size-xxl text-bold text-color-blue"><?= $weekVisits ?></div>
            <span class="text-size-sm"><?= $this->translate('panel.statistics.weeklyVisits') ?></span>
        </div>
        <div class="col-xs-1-2 col-md-1-4">
            <div class="text-size-xxl text-bold text-color-amber"><?= $weekUniqueVisits ?></div>
            <span class="text-size-sm"><?= $this->translate('panel.statistics.weeklyUniqueVisitors') ?></span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <div class="caption"><?= $this->translate('panel.statistics.totalVisits') ?></div>
    </div>
    <table class="table table-bordered table-striped table-hoverable text-size-sm">
        <thead>
            <tr>
                <th class="table-header"><?= $this->translate('panel.statistics.totalVisits.uri') ?></th>
                <th class="table-header truncate text-align-right" style="width: 4rem"><?= $this->translate('panel.statistics.visits') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pageViews as $page => $views) : ?>
                <tr>
                    <td class="table-cell statistics-histogram-cell" style="--percentage: <?= round($views / $totalViews * 100, 2) ?>%">
                        <div class="truncate"><?= $this->icon($page === '/' ? 'page-home' : 'page') ?> <?= $page ?></div>
                    </td>
                    <td class="table-cell text-align-right"><?= $views ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</section>

<div class="row">
    <div class="col-lg-1-2">
        <section class="section">
            <div class="section-header">
                <div class="caption"><?= $this->translate('panel.statistics.sources') ?></div>
            </div>
            <table class="table table-bordered table-striped table-hoverable text-size-sm">
                <thead>
                    <tr>
                        <th class="table-header"><?= $this->translate('panel.statistics.sources.site') ?></th>
                        <th class="table-header truncate text-align-right" style="width: 4rem"><?= $this->translate('panel.statistics.visits') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source => $views) : ?>
                        <tr>
                            <td class="table-cell statistics-histogram-cell" style="--percentage: <?= round($views / $totalSources * 100, 2) ?>%">
                                <div class="truncate"><?= $this->icon('globe') ?> <?= $this->escape($source) ?: $this->translate('panel.statistics.sources.direct') ?></div>
                            </td>
                            <td class="table-cell text-align-right"><?= $views ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </section>
    </div>
    <div class="col-lg-1-2">
        <section class="section">
            <div class="section-header">
                <div class="caption"><?= $this->translate('panel.statistics.devices') ?></div>
            </div>
            <table class="table table-bordered table-striped table-hoverable text-size-sm">
                <thead>
                    <tr>
                        <th class="table-header"><?= $this->translate('panel.statistics.devices.type') ?></th>
                        <th class="table-header truncate text-align-right" style="width: 4rem"><?= $this->translate('panel.statistics.visits') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($devices as $device => $views) : ?>
                        <tr>
                            <td class="table-cell statistics-histogram-cell" style="--percentage: <?= round($views / $totalDevices * 100, 2) ?>%">
                                <div class="truncate"><?= $this->icon($device) ?> <?= $this->translate("panel.statistics.devices.type.{$device}") ?></div>
                            </td>
                            <td class="table-cell text-align-right"><?= $views ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </section>
    </div>
</div>