<?php if ($page->status() === 'published') : ?>
    <span class="page-status-published mr-2"><?= $this->icon('circle-small-fill') ?></span>
<?php elseif ($page->status() === 'notPublished') : ?>
    <span class="page-status-not-published mr-2"><?= $this->icon('circle-small-fill') ?></span>
<?php elseif ($page->status() === 'notRoutable') : ?>
    <span class="page-status-not-routable mr-2"><?= $this->icon('circle-small-fill') ?></span>
<?php endif ?>