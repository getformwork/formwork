<?php $this->layout('panel') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('panel.pages.pages') ?></h3>
<?php
    if ($panel->user()->permissions()->has('pages.create')):
?>
    <button type="button" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.new-page') ?></button>
<?php
    endif;
?>
    <button type="button" data-command="expand-all-pages"><?= $this->icon('chevron-down') ?> <?= $this->translate('panel.pages.pages.expand-all') ?></button>
    <button type="button" data-command="collapse-all-pages"><?= $this->icon('chevron-up') ?> <?= $this->translate('panel.pages.pages.collapse-all') ?></button>
    <button type="button" data-command="reorder-pages"><?= $this->icon('reorder-v') ?> <?= $this->translate('panel.pages.pages.reorder') ?></button>
    <div class="separator"></div>
    <input class="page-search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
    <div class="separator"></div>
    <?= $pagesList ?>
</div>
