<?php $this->layout('panel') ?>

<div class="header">
    <div class="header-title"><?= $this->translate('panel.pages.pages') ?> <span class="badge"><?= $formwork->site()->descendants()->count() ?></span></div>
    <div>
<?php
    if ($panel->user()->permissions()->has('pages.create')):
?>
        <button type="button" class="button-accent" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
<?php
    endif;
?>
    </div>
</div>

<div class="component">
    <div class="flex flex-wrap">
        <div class="flex-grow-1 mr-4">
            <input class="page-search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
        </div>
        <div class="whitespace-nowrap">
            <button type="button" data-command="expand-all-pages"><?= $this->icon('chevron-down') ?> <?= $this->translate('panel.pages.pages.expandAll') ?></button>
            <button type="button" data-command="collapse-all-pages"><?= $this->icon('chevron-up') ?> <?= $this->translate('panel.pages.pages.collapseAll') ?></button>
            <button type="button" data-command="reorder-pages"><?= $this->icon('reorder-v') ?> <?= $this->translate('panel.pages.pages.reorder') ?></button>
        </div>
    </div>
    <?= $pagesList ?>
</div>
