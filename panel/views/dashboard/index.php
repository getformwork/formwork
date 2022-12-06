<?php $this->layout('panel') ?>
<div class="row">
    <div class="col-m-1-3">
        <div class="component">
            <span class="h4"><?= $this->translate('panel.dashboard.welcome') ?></span>
        </div>
        <div class="component">
            <h3 class="caption"><?= $this->translate('panel.dashboard.quickActions') ?></h3>
<?php
            if ($panel->user()->permissions()->has('pages.create')):
?>
            <button type="button" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
<?php
            endif;

            if ($panel->user()->permissions()->has('cache.clear')):
?>
            <button type="button" data-command="clear-cache"><?= $this->icon('cache-clear') ?> <?= $this->translate('panel.cache.clear') ?></button>
<?php
            endif;

            if ($panel->user()->permissions()->has('backup')):
?>
            <button type="button" data-command="make-backup"><?= $this->icon('clock-rotate-left') ?> <?= $this->translate('panel.backup.backup') ?></button>
<?php
            endif;

            if ($panel->user()->permissions()->has('options.updates')):
?>
            <a class="button" role="button" href="<?= $panel->uri('/options/updates/'); ?>"><?= $this->icon('arrows-rotate-clockwise') ?> <?= $this->translate('panel.updates.check') ?></a>
<?php
            endif;
?>
        </div>
    </div>
    <div class="col-m-2-3">
        <div class="component">
            <div class="row">
                <div class="col-xs-1-2"><h3 class="caption"><?= $this->translate('panel.dashboard.statistics') ?></h3></div>
                <div class="col-xs-1-2">
                    <div class="ct-legend ct-legend-right">
                        <span class="ct-legend-label ct-series-a"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.visits') ?></span>
                        <span class="ct-legend-label ct-series-b"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('panel.dashboard.statistics.uniqueVisitors') ?></span>
                    </div>
                </div>
            </div>
            <div class="ct-chart" data-chart-data="<?= $this->escapeAttr($statistics); ?>"></div>
        </div>
    </div>
</div>
<div class="component">
<h3 class="caption"><?= $this->translate('panel.dashboard.lastModifiedPages') ?></h3>
    <?= $lastModifiedPages ?>
</div>
