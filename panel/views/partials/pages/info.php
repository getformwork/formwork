<div class="page-info">
    <div class="page-info-row"><strong><?= $this->translate('page.template') ?>:</strong> <?= $page->template()->title() ?></div>
    <div class="page-info-row"><strong><?= $this->translate('page.slug') ?>:</strong> <?= $page->slug() ?></div>
    <div class="page-info-badges">
        <?php if ($page->routable()): ?>
            <span class="badge badge-amber"><?= $this->icon('circle-small-fill') ?> <?= $this->translate('page.routable') ?></span>
        <?php else: ?>
            <span class="badge badge-amber"><?= $this->icon('circle-small') ?> <?= $this->translate('page.notRoutable') ?></span>
        <?php endif ?>
        <?php if ($page->listed()): ?>
            <span class="badge badge-purple"><?= $this->icon('eye') ?> <?= $this->translate('page.listed') ?></span>
        <?php else: ?>
            <span class="badge badge-purple"><?= $this->icon('eye-slash') ?> <?= $this->translate('page.notListed') ?></span>
        <?php endif ?>
    </div>
</div>