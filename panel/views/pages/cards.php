<?php
/**
 * Kirby-inspired card grid view for pages
 */
?>

<?php if (empty($pages->toArray())) : ?>
    <div class="pages-empty">
        <?= $this->icon('pages') ?>
        <p><?= $this->translate('panel.pages.pages.empty') ?></p>
    </div>
<?php else : ?>
    <div class="pages-grid">
        <?php foreach ($pages as $page) : ?>
            <?php $date = $this->datetime($page->contentFile()->lastModifiedTime()) ?>
            <?php $imagePreviewField = $page->scheme()->options()->get('imagePreviewField') ?>
            <?php $hasImage = $imagePreviewField !== null && $page->fields()->get($imagePreviewField)?->type() === 'image' && $page->get($imagePreviewField) != '' ?>
            <?php $hasChildren = $page->hasChildren() ?>
            <?php $subtree = $page->scheme()->options()->get('children.subtree', false) ?>
            
            <div class="page-card <?= $hasChildren ? 'has-children' : '' ?>" data-route="<?= $page->route() ?>">
                <div class="page-card-thumbnail-wrapper">
                    <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>" class="page-card-thumbnail">
                        <?php if ($hasImage) : ?>
                            <img src="<?= $page->get($imagePreviewField)->square(400, 'cover')->uri() ?>" alt="" loading="lazy" />
                        <?php else : ?>
                            <div class="page-card-thumbnail-placeholder">
                                <?= $this->icon($page->icon()) ?>
                            </div>
                        <?php endif ?>
                    </a>
                    
                    <?php if ($hasChildren) : ?>
                        <?php if ($subtree) : ?>
                            <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>" 
                               class="page-card-badge" 
                               title="<?= $this->translate('panel.pages.viewChildren') ?>">
                                <?= $this->icon('pages') ?>
                                <span><?= count($page->children()) ?></span>
                            </a>
                        <?php else : ?>
                            <span class="page-card-badge page-card-badge-static" 
                                  title="<?= $this->translate('panel.pages.viewChildren') ?>">
                                <?= $this->icon('pages') ?>
                                <span><?= count($page->children()) ?></span>
                            </span>
                        <?php endif ?>
                    <?php endif ?>
                </div>
                
                <div class="page-card-content">
                    <div class="page-card-title">
                        <a href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>"><?= $this->escape($page->title()) ?></a>
                    </div>
                    <div class="page-card-meta">
                        <span class="page-card-date"><?= $date ?></span>
                    </div>
                </div>
                
                <div class="page-card-footer">
                    <div class="page-card-status">
                        <?php if ($page->published()) : ?>
                            <span class="status-dot status-dot-published"></span>
                        <?php elseif (!$page->routable()) : ?>
                            <span class="status-dot status-dot-not-routable"></span>
                        <?php else : ?>
                            <span class="status-dot status-dot-draft"></span>
                        <?php endif ?>
                    </div>
                    
                    <div class="page-card-actions">
                        <?php if ($hasChildren && $subtree) : ?>
                            <a class="button button-link" 
                               href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/tree/') ?>" 
                               title="<?= $this->translate('panel.pages.viewChildren') ?>">
                                <?= $this->icon('pages-level-down') ?>
                            </a>
                        <?php endif ?>
                        
                        <a class="<?= $this->classes(['button', 'button-link', 'disabled' => !$page->published() || !$page->routable()]) ?>" 
                           role="button" 
                           <?php if ($page->published() && $page->routable()) : ?>href="<?= $page->uri(includeLanguage: false) ?>"<?php endif ?> 
                           target="_blank" 
                           title="<?= $this->translate('panel.pages.viewPage') ?>">
                            <?= $this->icon('arrow-right-up-box') ?>
                        </a>
                        
                        <div class="dropdown mb-0">
                            <button type="button" class="button button-link dropdown-button" 
                                    title="<?= $this->translate('panel.pages.page.actions') ?>" 
                                    data-dropdown="dropdown-card-<?= $page->uid() ?>">
                                <?= $this->icon('ellipsis-h') ?>
                            </button>
                            <div class="dropdown-menu" id="dropdown-card-<?= $page->uid() ?>">
                                <a class="dropdown-item" href="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/edit/') ?>">
                                    <?= $this->icon('pencil') ?> <?= $this->translate('panel.pages.edit') ?>
                                </a>
                                <?php if ($panel->user()->permissions()->has('panel.pages.duplicate')) : ?>
                                    <button type="button" class="dropdown-item" 
                                            data-modal="duplicatePageModal" 
                                            data-modal-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/duplicate/') ?>"
                                            <?php if (!$page->isDuplicable()) : ?> disabled<?php endif ?>>
                                        <?= $this->icon('duplicate') ?> <?= $this->translate('panel.pages.duplicatePage') ?>
                                    </button>
                                <?php endif ?>
                                <?php if ($panel->user()->permissions()->has('panel.pages.delete')) : ?>
                                    <button type="button" class="dropdown-item" 
                                            data-modal="deletePageItemModal" 
                                            data-action="<?= $panel->uri('/pages/' . trim($page->route(), '/') . '/delete/') ?>"
                                            <?php if (!$page->isDeletable()) : ?> disabled<?php endif ?>>
                                        <?= $this->icon('trash') ?> <?= $this->translate('panel.pages.deletePage') ?>
                                    </button>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>
