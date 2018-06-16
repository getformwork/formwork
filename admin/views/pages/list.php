			<ul class="pages-list <?= implode(' ', $class) ?>" data-parent="<?= isset($parent) ? $parent : '.' ?>"<?php if (isset($sortable) && $sortable === false): ?> data-sortable="false"<?php endif; ?>>
<?php
			foreach ($pages as $page):
				if ($page->published()) $status = 'published';
				if (!$page->routable()) $status = 'not-routable';
				if (!$page->published()) $status = 'not-published';
				$date = date($this->option('date.format') . ' ' . $this->option('date.hour_format'), $page->lastModifiedTime());
?>
				<li class="<?= $subpages ? 'pages-level-' . $page->level() : '' ?><?= (is_null($page->num()) || $page->template()->scheme()->get('num') == 'date') ? ' no-reorder' : '' ?>">
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
								<a <?= $page->published() && $page->routable() ? 'href="' . $this->pageUri($page) . '"' : '' ?>target="_blank"><?= $this->pageUri($page) ?></a>
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
							'templates' => $templates,
							'class' => array('pages-children'),
							'parent' => $page->slug(),
							'sortable' => $sortable
						));
					endif;
?>
				</li>
<?php
			endforeach;
?>
			</ul>
