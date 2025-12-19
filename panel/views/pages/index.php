<?php $this->layout('@panel.panel') ?>

<header class="panel-header">
    <div class="flex mr-auto overflow-hidden">
        <div class="min-w-0 flex">
            <div class="header-icon"><?= $this->icon('pages') ?></div>
            <div class="header-title mr-4"><?= $this->translate('panel.pages.pages') ?></div>
            <?php foreach ($parent->ancestors()->reverse()->with($parent) as $page) : ?>
                <div class="text-color-gray-medium mr-4">/</div>
                <?php if ($page->isSite()) : ?>
                    <div class="header-icon"><?= $this->icon('globe') ?></div>
                    <div class="header-title truncate mr-4"><a href="<?= $panel->uri('/pages/') ?>"><?= $this->translate('panel.options.site') ?></a></div>
                <?php else: ?>
                    <div class="header-icon"><?= $this->icon($page->icon()) ?></div>
                    <div class="header-title truncate mr-4"><a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>"><?= $this->escape($page->title()) ?></a></div>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </div>
    <div class="flex gap-2">
        <?php if ($gridDisplayEnabled ?? false) : ?>
            <div class="pages-view-toggle">
                <button type="button" class="button button-secondary <?= ($viewMode ?? 'tree') === 'tree' ? 'active' : '' ?>" data-command="view-mode-tree" title="<?= $this->translate('panel.pages.pages.viewTree') ?>">
                    <?= $this->icon('list') ?>
                </button>
                <button type="button" class="button button-secondary <?= ($viewMode ?? 'tree') === 'card' ? 'active' : '' ?>" data-command="view-mode-card" title="<?= $this->translate('panel.pages.pages.viewCard') ?>">
                    <?= $this->icon('file-icons') ?>
                </button>
            </div>

        <?php endif ?>
        <?php if ($panel->user()->permissions()->has('panel.pages.create')) : ?>
            <button type="button" class="button button-accent" data-modal="newPageModal"><?= $this->icon('plus-circle') ?> <?= $this->translate('panel.pages.newPage') ?></button>
        <?php endif ?>
    </div>
</header>

<section class="section">
    <div class="flex flex-wrap">
        <div class="flex-grow-1 mr-4">
            <div class="form-input-wrap">
                <span class="form-input-icon"><?= $this->icon('search') ?></span>
                <input class="form-input page-search" id="pages.search" type="search" placeholder="<?= $this->translate('panel.pages.pages.search') ?>">
            </div>
        </div>
        <div class="whitespace-nowrap">
            <button type="button" class="button button-secondary mb-4" data-command="expand-all-pages" disabled><?= $this->icon('chevron-down') ?> <?= $this->translate('panel.pages.pages.expandAll') ?></button>
            <button type="button" class="button button-secondary mb-4" data-command="collapse-all-pages" disabled><?= $this->icon('chevron-up') ?> <?= $this->translate('panel.pages.pages.collapseAll') ?></button>
            <button type="button" class="button button-secondary mb-4" data-command="reorder-pages" disabled><?= $this->icon('reorder-v') ?> <?= $this->translate('panel.pages.pages.reorder') ?></button>
        </div>
    </div>
    <?= $pagesTree ?>
</section>
