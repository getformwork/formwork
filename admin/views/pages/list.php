            <ul class="pages-list <?= $class ?>" data-sortable="<?= $sortable ?>" <?php if ($parent): ?>data-parent="<?= $parent ?>"<?php endif; ?>>
<?php
            foreach ($pages as $page):
                if ($page->published()) $status = 'published';
                if (!$page->routable()) $status = 'not-routable';
                if (!$page->published()) $status = 'not-published';

                $date = date($this->option('date.format') . ' ' . $this->option('date.hour_format'), $page->lastModifiedTime());
                $reorder = is_null($page->num()) || $page->template()->scheme()->get('num') == 'date';
                $routable = $page->published() && $page->routable();
?>
                <li class="<?php if ($subpages): ?>pages-level-<?= $page->level() ?><?php endif; ?><?php if ($reorder): ?> no-reorder<?php endif; ?>">
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
                            <div class="page-date-inner overflow-title"><?= $date ?></div>
                        </div>
                        <div class="pages-item-cell page-status page-status-<?= $status ?>">
                            <div class="page-status-label overflow-title"><?= $this->label('pages.status.' . $status) ?></div>
                        </div>
                        <div class="pages-item-cell page-actions">
<?php
                            if ($page->isDeletable()):
?>
                            <button data-modal="deletePageModal" data-modal-action="<?= $this->uri('/pages/' . trim($page->slug(), '/') . '/delete/') ?>" title="<?= $this->label('pages.delete-page') ?>"><i class="i-trash"></i></button>
<?php
                            endif;
?>
                        </div>
                    </div>
<?php
                    if ($subpages && $page->hasChildren()):
                        $children = $page->children();

                        if ($page->template()->scheme()->get('reverse')) $children = $children->reverse();
                        $sortable = $page->template()->scheme()->get('sortable-children', true);

                        $this->view('pages.list', array(
                            'pages' => $children,
                            'subpages' => true,
                            'class' => 'pages-children',
                            'parent' => $sortable ? $page->slug() : null,
                            'sortable' => $sortable ? 'true' : 'false'
                        ));

                    endif;
?>
                </li>
<?php
            endforeach;
?>
            </ul>
