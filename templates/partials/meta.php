<?php foreach ($page->metadata() as $meta): ?>
<?php if ($meta->isCharset()): ?>
    <meta charset="<?= $this->escapeAttr($meta->content()) ?>">
<?php elseif ($meta->isHTTPEquiv()) : ?>
    <meta http-equiv="<?= $this->escapeAttr($meta->name()) ?>" content="<?= $this->escapeAttr($meta->content()) ?>">
<?php else: ?>
    <meta <?= $meta->prefix() === 'og' ? 'property' : 'name' ?>="<?= $this->escapeAttr($meta->name()) ?>" content="<?= $this->escapeAttr($meta->content()) ?>">
<?php endif; ?>
<?php endforeach; ?>
