<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.statistics.statistics') ?></div>
</div>

<section class="section">
    <div class="row">
        <div class="col-xs-1-2">
            <div class="section-header">
                <h3 class="caption"><?= $this->translate('panel.dashboard.statistics') ?></h3>
            </div>
        </div>
        <div class="col-xs-1-2">
            <div class="ct-legend ct-legend-right">
                <span class="ct-legend-label ct-series-a mr-8"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.visits') ?></span>
                <span class="ct-legend-label ct-series-b"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.uniqueVisitors') ?></span>
            </div>
        </div>
    </div>
    <div class="statistics-chart ct-chart" data-chart-data="<?= $this->escapeAttr($statistics) ?>"></div>
</section>
<section class="section">
    <div class="row text-align-center">
        <div class="col-xs-1-2 col-m-1-4">
            <div class="text-size-xxl text-bold text-color-blue"><?= $monthVisits ?></div>
            <span class="text-size-s"><?= $this->translate('panel.statistics.monthlyVisits') ?></span>
        </div>
        <div class="col-xs-1-2 col-m-1-4">
            <div class="text-size-xxl text-bold text-color-amber"><?= $monthUniqueVisits ?></div>
            <span class="text-size-s"><?= $this->translate('panel.statistics.monthlyUniqueVisitors') ?></span>
        </div>
        <div class="col-xs-1-2 col-m-1-4">
            <div class="text-size-xxl text-bold text-color-blue"><?= $weekVisits ?></div>
            <span class="text-size-s"><?= $this->translate('panel.statistics.weeklyVisits') ?></span>
        </div>
        <div class="col-xs-1-2 col-m-1-4">
            <div class="text-size-xxl text-bold text-color-amber"><?= $weekUniqueVisits ?></div>
            <span class="text-size-s"><?= $this->translate('panel.statistics.weeklyUniqueVisitors') ?></span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h3 class="caption"><?= $this->translate('panel.statistics.totalVisits') ?></h3>
    </div>
    <table class="table table-bordered table-striped table-hoverable text-size-s">
        <thead>
            <tr>
                <th class="table-header" style="width: 100%"><?= $this->translate('panel.statistics.totalVisits.uri') ?></th>
                <th class="table-header truncate" style="width: 20%"><?= $this->translate('panel.statistics.totalVisits.visits') ?></th>
                <th class="table-header truncate" style="width: 20%"><?= $this->translate('panel.statistics.totalVisits.percentTotal') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pageViews as $page => $views): ?>
                <tr>
                    <td class="table-cell truncate"><a href="<?= $site->uri($page, includeLanguage: false) ?>" target="_blank"><?= $page ?></a></td>
                    <td class="table-cell"><?= $views ?></td>
                    <td class="table-cell"><?= round($views / $totalViews * 100, 2) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</section>
