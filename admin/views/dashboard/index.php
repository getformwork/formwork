<div class="container-no-margin">
    <div class="row">
        <div class="col-m-5-12">
            <div class="component">
                <span class="h4"><?= $this->label('dashboard.welcome') ?></span>
            </div>
            <div class="component">
                <h3 class="caption"><?= $this->label('dashboard.quick-actions') ?></h3>
<?php
                if ($admin->user()->permissions()->has('pages.create')):
?>
                <button type="button" data-modal="newPageModal"><i class="i-plus-circle"></i> <?= $this->label('pages.new-page') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('cache.clear')):
?>
                <button type="button" data-command="clear-cache"><i class="i-trash"></i> <?= $this->label('cache.clear') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('backup')):
?>
                <button type="button" data-command="make-backup"><i class="i-history"></i> <?= $this->label('backup.backup') ?></button>
<?php
                endif;

                if ($admin->user()->permissions()->has('options.updates')):
?>
                <a class="button" role="button" href="<?= $this->uri('/options/updates/'); ?>"><i class="i-sync"></i> <?= $this->label('updates.check') ?></a>
<?php
                endif;
?>
            </div>
        </div>
        <div class="col-m-7-12">
            <div class="component">
                <div class="row">
                    <div class="col-xs-1-2"><h3 class="caption"><?= $this->label('dashboard.statistics') ?></h3></div>
                    <div class="col-xs-1-2">
                        <div class="ct-legend ct-legend-right">
                            <span class="ct-legend-label ct-series-a"><?= $this->label('dashboard.statistics.visits') ?></span>
                            <span class="ct-legend-label ct-series-b"><?= $this->label('dashboard.statistics.unique-visitors') ?></span>
                        </div>
                    </div>
                </div>
                <div class="ct-chart" data-chart-data="<?= $this->escapeAttr($statistics); ?>"></div>
            </div>
        </div>
    </div>
</div>
<div class="component">
<h3 class="caption"><?= $this->label('dashboard.last-modified-pages') ?></h3>
    <?= $lastModifiedPages ?>
</div>
