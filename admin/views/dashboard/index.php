<div class="container-no-margin">
    <div class="row">
        <div class="col-m-5-12">
            <div class="component">
                <span class="h4"><?= $this->label('dashboard.welcome') ?></span>
            </div>
            <div class="component">
                <h3 class="caption"><?= $this->label('dashboard.quick-actions') ?></h3>
                <button data-modal="newPageModal"><i class="i-plus-circle"></i> <?= $this->label('pages.new-page') ?></button>
                <button id="clear-cache"><i class="i-trash"></i> <?= $this->label('cache.clear') ?></button>
            </div>
        </div>
        <div class="col-m-7-12">
            <div class="component">
                <h3 class="caption"><?= $this->label('dashboard.statistics') ?></h3>
                <div class="ct-chart" data-chart-data="<?= $this->escape(json_encode($statistics)); ?>"></div>
            </div>
        </div>
    </div>
</div>
<div class="component">
<h3 class="caption"><?= $this->label('dashboard.last-modified-pages') ?></h3>
    <?= $lastModifiedPages ?>
</div>
