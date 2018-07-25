            <ul class="pages-list <?= $class ?>" data-sortable-children="<?= $sortable ?>"<?php if ($parent): ?> data-parent="<?= $parent ?>"<?php endif; ?>>
<?php
            foreach ($pages as $page):
                $routable = $page->published() && $page->routable();
                $date = date($this->option('date.format') . ' ' . $this->option('date.hour_format'), $page->lastModifiedTime());
?>
                <li class="<?php if ($subpages): ?>pages-level-<?= $page->level() ?><?php endif; ?>" <?php if (!$page->sortable()): ?>data-sortable="false"<?php endif; ?>>
                    <div class="pages-item">
                        <div class="pages-item-cell page-details">
                            <div class="page-title">
<?php
                    if ($subpages && $page->hasChildren()):
?>
                            <span class="page-children-toggle toggle-collapsed"></span>
<?php
                    endif;
?>
                                <a href="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/edit/') ?>" title="<?= htmlspecialchars($page->title()) ?>"><?= $page->title() ?></a>
                            </div>
                            <div class="page-uri">
                                <a <?php if ($routable): ?>href="<?= $this->pageUri($page) ?>"<?php endif; ?> target="_blank"><?= $page->slug() ?></a>
                            </div>
                        </div>
                        <div class="pages-item-cell page-date">
                            <div class="page-date-inner" data-overflow-tooltip="true"><?= $date ?></div>
                        </div>
                        <div class="pages-item-cell page-status page-status-<?= $page->status() ?>">
                            <div class="page-status-label" data-overflow-tooltip="true"><?= $this->label('pages.status.' . $page->status()) ?></div>
                        </div>
                        <div class="pages-item-cell page-actions">
<?php
                            if ($page->isDeletable()):
?>
                            <button class="button-link" data-modal="deletePageModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/delete/') ?>" title="<?= $this->label('pages.delete-page') ?>"><i class="i-trash"></i></button>
<?php
                            endif;
?>
                        </div>
                    </div>
<?php
                    if ($subpages && $page->hasChildren()):
                        $scheme = $page->template()->scheme();
                        $reverseChildren = $page->get('reverse-children', $scheme->get('reverse-children', false));
                        $sortableChildren = $page->get('sortable-children', $scheme->get('sortable-children', true));

                        $this->view('pages.list', array(
                            'pages' =>  $reverseChildren ? $page->children()->reverse() : $page->children(),
                            'subpages' => true,
                            'class' => 'pages-children',
                            'parent' => $sortableChildren ? $page->slug() : null,
                            'sortable' => $sortableChildren ? 'true' : 'false'
                        ));

                    endif;
?>
                </li>
<?php
            endforeach;
?>
            </ul>
