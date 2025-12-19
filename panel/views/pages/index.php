<?php $this->layout('@panel.panel') ?>

<header class="panel-header">
    <div class="panel-header-breadcrumb">
        <?= $this->icon('pages') ?>
        <span><?= $this->translate('panel.pages.pages') ?></span>
        <?php foreach ($parent->ancestors()->reverse()->with($parent) as $page) : ?>
            <span class="breadcrumb-separator">/</span>
            <?php if ($page->isSite()) : ?>
                <a href="<?= $panel->uri('/pages/') ?>" class="breadcrumb-link">
                    <?= $this->icon('globe') ?>
                    <span><?= $this->translate('panel.options.site') ?></span>
                </a>
            <?php else: ?>
                <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>" class="breadcrumb-link">
                    <?= $this->icon($page->icon()) ?>
                    <span><?= $this->escape($page->title()) ?></span>
                </a>
            <?php endif ?>
        <?php endforeach ?>
    </div>

    <div class="panel-header-search flex gap-2">
        <div class="form-input-wrap">
            <span class="form-input-icon"><?= $this->icon('search') ?></span>
            <input class="form-input page-search" id="pages.search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
        </div>
        <div class="pages-tree-controls">
            <button type="button" class="button button-secondary" data-command="expand-all-pages" disabled><?= $this->icon('chevron-down') ?></button>
            <button type="button" class="button button-secondary" data-command="collapse-all-pages" disabled><?= $this->icon('chevron-up') ?></button>
            <button type="button" class="button button-secondary" data-command="reorder-pages" disabled><?= $this->icon('reorder-v') ?></button>
        </div>
    </div>

    <div class="panel-header-actions flex gap-2">
        <?php if ($gridDisplayEnabled ?? false) : ?>
        <div class="pages-view-toggle">
            <button type="button" class="button button-secondary <?= ($viewMode ?? 'tree') === 'card' ? 'active' : '' ?>" data-command="view-mode-card" title="<?= $this->translate('panel.pages.pages.viewCard') ?>">
                    <?= $this->icon('file-icons') ?>
            </button>
            <button type="button" class="button button-secondary <?= ($viewMode ?? 'tree') === 'tree' ? 'active' : '' ?>" data-command="view-mode-tree" title="<?= $this->translate('panel.pages.pages.viewTree') ?>">
                    <?= $this->icon('list') ?>
            </button>
        </div>
        <?php endif ?>
        <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
            <button type="button" class="button button-accent" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
        <?php endif ?>
    </div>
</header>

<section class="mt-4">
    <?= $pagesTree ?>
</section>
