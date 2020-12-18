<?php $this->layout('admin') ?>
<div class="component">
    <h3 class="caption"><?= $this->translate('pages.pages') ?></h3>
<?php
    if ($admin->user()->permissions()->has('pages.create')):
?>
    <button type="button" data-modal="newPageModal"><i class="i-plus-circle"></i> <?= $this->translate('pages.new-page') ?></button>
<?php
    endif;
?>
    <button type="button" data-command="expand-all-pages"><i class="i-chevron-down"></i> <?= $this->translate('pages.pages.expand-all') ?></button>
    <button type="button" data-command="collapse-all-pages"><i class="i-chevron-up"></i> <?= $this->translate('pages.pages.collapse-all') ?></button>
    <button type="button" data-command="reorder-pages"><i class="i-move"></i> <?= $this->translate('pages.pages.reorder') ?></button>
    <div class="separator"></div>
    <input class="page-search" type="search" placeholder="<?= $this->translate('pages.pages.search') ?>">
    <div class="separator"></div>
    <?= $pagesList ?>
</div>
