<?php $this->layout('fields.field') ?>
<select class="form-select" id="page-parent" name="parent">
    <option <?= $this->attr([
                'value'    => '.',
                'selected' => $page->parent()->isSite(),
            ]) ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
    <?php foreach ($parents as $parent) : ?>
        <?php $scheme = $app->schemes()->get('pages.' . $parent->template()->name()) ?>
        <?php if ($parent === $page || !$scheme->options()->get('pages', true)) : ?>
            <?php continue ?>
        <?php endif ?>
        <option <?= $this->attr([
                    'value'    => $parent->route(),
                    'selected' => $page->parent() === $parent,
                ]) ?>><?= str_repeat('— ', $parent->level() - 1) . $this->escape($parent->title()) ?></option>
    <?php endforeach ?>
</select>