<?php $this->layout('admin') ?>
<div class="container-no-margin">
    <div class="row">
        <div class="col-m-5-12">
            <div class="component">
                <span class="h4"><?= $this->translate('dashboard.welcome') ?></span>
            </div>
            <div class="component">
                <h3 class="caption"><?= $this->translate('dashboard.quick-actions') ?></h3>
<?php
                if ($admin->user()->permissions()->has('pages.create')):
?>
                <button type="button" data-modal="newPageModal"><i class="i-plus-circle"></i> <?= $this->translate('pages.new-page') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('cache.clear')):
?>
                <button type="button" data-command="clear-cache"><i class="i-trash"></i> <?= $this->translate('cache.clear') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('backup')):
?>
                <button type="button" data-command="make-backup"><i class="i-history"></i> <?= $this->translate('backup.backup') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('options.updates')):
?>
                <a class="button" role="button" href="<?= $admin->uri('/options/updates/'); ?>"><i class="i-sync"></i> <?= $this->translate('updates.check') ?></a>
<?php
                endif;
?>
            </div>
        </div>
        <div class="col-m-7-12">
            <div class="component">
                <div class="row">
                    <div class="col-xs-1-2"><h3 class="caption"><?= $this->translate('dashboard.statistics') ?></h3></div>
                    <div class="col-xs-1-2">
                        <div class="ct-legend ct-legend-right">
                            <span class="ct-legend-label ct-series-a"><?= $this->translate('dashboard.statistics.visits') ?></span>
                            <span class="ct-legend-label ct-series-b"><?= $this->translate('dashboard.statistics.unique-visitors') ?></span>
                        </div>
                    </div>
                </div>
                <div class="ct-chart" data-chart-data="<?= $this->escapeAttr($statistics); ?>"></div>
            </div>
        </div>
    </div>
</div>
<div class="component">
<h3 class="caption"><?= $this->translate('dashboard.last-modified-pages') ?></h3>
    <?= $lastModifiedPages ?>
</div>
