<?php foreach ($page->metadata() as $meta): ?>
<?php if ($meta->isCharset()): ?>
    <meta charset="<?= $meta->content() ?>">
<?php elseif ($meta->isHTTPEquiv()): ?>
    <meta http-equiv="<?= $meta->name() ?>" content="<?= $meta->content() ?>">
<?php else: ?>
    <meta <?= $meta->namespace() === 'og' ? 'property' : 'name' ?>="<?= $meta->name() ?>" content="<?= $meta->content() ?>">
<?php endif; ?>
<?php endforeach; ?>
