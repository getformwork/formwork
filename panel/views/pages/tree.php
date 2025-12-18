<?php $this->modals()->add('deletePageItem') ?>
<?php $this->modals()->add('duplicatePage') ?>

<?php if ($headers) : ?>
    <div class="pages-tree-headers" aria-hidden="true">
        <div class="pages-tree-headers-cell page-details truncate"><?= $this->translate('page.title') ?></div>
        <div class="pages-tree-headers-cell page-date truncate show-from-lg"><?= $this->translate('panel.pages.page.lastModified') ?></div>
        <div class="pages-tree-headers-cell page-status truncate show-from-xs"><?= $this->translate('page.status') ?></div>
        <div class="pages-tree-headers-cell page-actions"><span class="show-from-xs mr-6"><?= $this->translate('panel.pages.page.actions') ?></span></div>
    </div>
<?php endif ?>

<ul <?= $this->attr(['class' => ['pages-tree', $class ?? null], 'data-orderable-children' => $orderable ? 'true' : 'false', 'data-parent' => isset($parent) ? ($parent->isSite() ? '.' : $parent->route()) : null]) ?>>
    <?php foreach ($pages as $page) : ?>
        <?php $routable = $page->published() && $page->routable() ?>
        <?php $date = $this->datetime($page->contentFile()->lastModifiedTime()) ?>
        <?php $subtree = $page->scheme()->options()->get('children.subtree', false) ?>
        <li class="<?= $this->classes([
                        'pages-tree-item',
                        'pages-tree-level-' . ($page->level() - ($root ?? $site)->level()) => $includeChildren,
                        'has-children'     => $page->hasChildren() && !$subtree,
                        'is-orderable'     => $page->orderable(),
                        'is-not-orderable' => !$page->orderable(),
                    ])
                    ?>" data-route="<?= $page->route() ?>">
            <div class="pages-tree-row">
                <div class="pages-tree-item-cell page-details flex">
                    <div class="pages-tree-icon sortable-handle mr-2">
                        <?php if ($orderable && $page->orderable()) : ?>
                            <span title="<?= $this->translate('panel.dragToReorder') ?>"><?= $this->icon('grabber') ?></span>
                        <?php endif ?>
                    </div>
                    <?php if ($includeChildren) : ?>
                        <div class="pages-tree-icon pages-tree-children-toggle mr-2">
                            <?php if ($page->hasChildren() && !$subtree) : ?>
                                <button type="button" class="button" title="<?= $this->translate('panel.pages.toggleChildren') ?>" aria-label="<?= $this->translate('panel.pages.toggleChildren') ?>"><?= $this->icon('chevron-down') ?></button>
                            <?php endif ?>
                        </div>
                    <?php endif ?>
                    <div class="page-icon mr-3">
                        <?php $imagePreviewField = $page->scheme()->options()->get('imagePreviewField') ?>
                        <?php if ($imagePreviewField !== null && $page->fields()->get($imagePreviewField)?->type() === 'image' && $page->get($imagePreviewField) != '') : ?>
                            <img class="page-thumbnail" src="<?= $page->get($imagePreviewField)->square(80, 'cover')->uri() ?>" alt="" />
                        <?php endif ?>
                        <?= $this->icon($page->icon()) ?>
                        <?= $this->insert('_pages/info', ['page' => $page]) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="flex">
                            <div class="page-title truncate mr-2">
                                <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>"><?= $this->escape($page->title()) ?></a>
                            </div>
                            <div class="page-languages show-from-xs">
                                <?php foreach ($page->languages()->available() as $language) : ?>
                                    <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/language/' . $language->code() . '/') ?>" title="<?= $this->translate('panel.pages.languages.editLanguage', $language->nativeName() . ' (' . $language->code() . ')') ?>"><span class="badge badge-blue"><span><?= $this->icon('translate') ?></span> <?= $language->code() ?></a></span>
                                <?php endforeach ?>
                            </div>
                        </div>
                        <div class="page-route truncate mr-2" aria-hidden="true">
                            <span><?= $this->escape($page->canonicalRoute() ?? $page->route()) ?></span>
                        </div>
                    </div>
                </div>
                <div class="pages-tree-item-cell page-date truncate show-from-lg"><?= $date ?></div>
                <div class="pages-tree-item-cell page-status truncate show-from-xs">
                    <?= $this->insert('_pages/status', ['page' => $page]) ?>
                    <span class="page-status-label"><?= $this->translate('page.status.' . $page->status()) ?></span>
                </div>
                <div class="pages-tree-item-cell page-actions">
                    <?php if ($includeChildren && $page->hasChildren() && $subtree) : ?>
                        <a class="button button-link show-from-lg" role="button" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>" title="<?= $this->translate('panel.pages.viewChildren') ?>" aria-label="<?= $this->translate('panel.pages.viewChildren') ?>"><?= $this->icon('pages-level-down') ?></a>
                    <?php endif ?>
                    <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->published() || !$page->routable()]) ?>" role="button" <?php if ($page->published() && $page->routable()) : ?>href="<?= $page->uri(includeLanguage: false) ?>" <?php endif ?> target="formwork-view-page-<?= $page->uid() ?>" title="<?= $this->translate('panel.pages.viewPage') ?>" aria-label="<?= $this->translate('panel.pages.viewPage') ?>"><?= $this->icon('arrow-right-up-box') ?></a>
                    <div class="dropdown mb-0">
                        <button type="button" class="button button-link dropdown-button" title="<?= $this->translate('panel.pages.page.actions') ?>" aria-label="<?= $this->translate('panel.pages.page.actions') ?>" data-dropdown="dropdown-<?= $page->uid() ?>"><?= $this->icon('ellipsis-v') ?></button>
                        <div class="dropdown-menu" id="dropdown-<?= $page->uid() ?>">
                            <a class="dropdown-item" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>"><?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.edit') ?></a>
                            <?php if ($includeChildren && $page->hasChildren() && $subtree) : ?>
                                <a class="dropdown-item hide-from-lg" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>"><?= $this->icon('pages-level-down') ?> <?= $this->translate('panel.pages.viewChildren') ?></a>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('panel.pages.duplicate')) : ?>
                                <button type="button" class="dropdown-item" data-modal="duplicatePageModal" data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/duplicate/') ?>" data-duplicate-title="<?= $this->escapeAttr($this->translate('panel.pages.duplicatePage.title', $page->title())) ?>" <?php if (!$page->isDuplicable()) : ?> disabled<?php endif ?>><?= $this->icon('duplicate') ?> <?= $this->translate('panel.pages.duplicatePage') ?></button>
                            <?php endif ?>
                            <?php if ($panel->user()->permissions()->has('panel.pages.delete')) : ?>
                                <button type="button" class="dropdown-item" data-modal="deletePageItemModal" data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/') ?>" <?php if (!$page->isDeletable()) : ?> disabled<?php endif ?>><?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deletePage') ?></button>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($includeChildren && $page->hasChildren() && !$subtree) : ?>
                <?php $this->insert('pages.tree', [
                    'pages'           => $page->scheme()->options()->get('children.reverse', false) ? $page->children()->reverse() : $page->children(),
                    'includeChildren' => true,
                    'class'           => 'pages-tree-children',
                    'parent'          => $page,
                    'orderable'       => $orderable && $page->scheme()->options()->get('children.orderable', true),
                    'headers'         => false,
                ]) ?>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ul>