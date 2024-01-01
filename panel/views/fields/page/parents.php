<?php $this->layout('fields.field') ?>
<select id="page-parent" name="parent">
    <option value="." <?php if ($page->parent()->isSite()): ?> selected<?php endif ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
    <?php foreach ($parents as $parent): ?>
        <?php $scheme = $app->schemes()->get('pages.' . $parent->template()->name()) ?>
        <?php if (!$scheme->options()->get('pages', true)): ?>
            <?php continue ?>
        <?php endif ?>
        <?php if ($parent === $page): ?>
            <?php continue ?>
        <?php endif ?>
        <option value="<?= $parent->route() ?>" <?php if ($page->parent() === $parent): ?> selected<?php endif ?>><?= str_repeat('— ', $parent->level() - 1) . $parent->title() ?></option>
    <?php endforeach ?>
</select>
