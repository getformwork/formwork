<?php
        if ($headers):
?>
            <div class="pages-list-headers" aria-hidden="true">
                <div class="pages-headers-cell page-details"><?= $this->translate('admin.pages.page.title') ?></div>
                <div class="pages-headers-cell page-date"><?= $this->translate('admin.pages.page.last-modified') ?></div>
                <div class="pages-headers-cell page-status"><?= $this->translate('admin.pages.page.status') ?></div>
                <div class="pages-headers-cell page-actions"><?= $this->translate('admin.pages.page.actions') ?></div>
            </div>
<?php
        endif;
?>
            <ul class="pages-list <?= $class ?>" data-sortable-children="<?= $sortable ? 'true' : 'false' ?>"<?php if ($parent): ?> data-parent="<?= $parent ?>"<?php endif; ?>>
<?php
            foreach ($pages as $page):
                $routable = $page->published() && $page->routable();
                $date = $this->datetime($page->lastModifiedTime());
?>
                <li class="<?php if ($subpages): ?>pages-level-<?= $page->level() ?><?php endif; ?>" <?php if (!$page->sortable()): ?>data-sortable="false"<?php endif; ?>>
                    <div class="pages-item">
                        <div class="pages-item-cell page-details">
                            <div class="page-title">
<?php
                    if ($sortable && $page->sortable()):
?>
                            <span class="sort-handle" title="<?= $this->translate('admin.drag-to-reorder') ?>"><?= $this->icon('grabber') ?></span>
<?php
                    endif;
?>
<?php
                    if ($subpages && $page->hasChildren()):
?>
                            <button type="button" class="page-children-toggle toggle-collapsed" title="<?= $this->translate('admin.pages.toggle-children') ?>"><?= $this->icon('chevron-down') ?></button>
<?php
                    endif;
?>
                                <?= $this->icon($page->get('icon', 'page')) ?>
                                <a href="<?= $admin->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>" title="<?= $this->escapeAttr($page->title()) ?>"><?= $this->escape($page->title()) ?></a>
<?php
                                foreach ($page->availableLanguages() as $code):
?>
                                <span class="page-language"><?= $code ?></span>
<?php
                                endforeach;
?>
                            </div>
                            <div class="page-route" aria-hidden="true">
                                <span><?= $page->route() ?></span>
                            </div>
                        </div>
                        <div class="pages-item-cell page-date">
                            <div class="page-date-inner" data-overflow-tooltip="true"><?= $date ?></div>
                        </div>
                        <div class="pages-item-cell page-status page-status-<?= $page->status() ?>">
                            <?= $this->icon('circle-small-fill'); ?>
                            <span class="page-status-label" data-overflow-tooltip="true"><?= $this->translate('admin.pages.status.' . $page->status()) ?></span>
                        </div>
                        <div class="pages-item-cell page-actions">
                            <a class="button button-link<?php if (!$page->published() || !$page->routable()): ?> disabled<?php endif; ?>" role="button" <?php if ($page->published() && $page->routable()): ?>href="<?= $admin->pageUri($page) ?>"<?php endif; ?> target="formwork-preview-<?= $page->uid() ?>" title="<?= $this->translate('admin.pages.preview') ?>" aria-label="<?= $this->translate('admin.pages.preview') ?>"><?= $this->icon('eye') ?></a>
<?php
                        if ($admin->user()->permissions()->has('pages.delete')):
?>
                            <button type="button" class="button-link" data-modal="deletePageModal" data-modal-action="<?= $admin->uri('/pages/' . trim($page->route(), '/') . '/delete/') ?>" title="<?= $this->translate('admin.pages.delete-page') ?>" aria-label="<?= $this->translate('admin.pages.delete-page') ?>"<?php if (!$page->isDeletable()): ?> disabled<?php endif; ?>><?= $this->icon('trash') ?></button>
<?php
                        endif;
?>
                        </div>
                    </div>
<?php
                    if ($subpages && $page->hasChildren()):
                        $scheme = $page->scheme();
                        $reverseChildren = $scheme->get('children.reverse', false);
                        $sortableChildren = $scheme->get('children.sortable', true);

                        $this->insert('pages.list', [
                            'pages'    => $reverseChildren ? $page->children()->reverse() : $page->children(),
                            'subpages' => true,
                            'class'    => 'pages-children',
                            'parent'   => $sortableChildren ? $page->route() : null,
                            'sortable' => $sortable && $sortableChildren,
                            'headers'  => false
                        ]);

                    endif;
?>
                </li>
<?php
            endforeach;
?>
            </ul>
