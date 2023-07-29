<?= $this->layout('fields.field') ?>
<select id="page-parent" name="parent">
    <option value="." <?php if ($page->parent()->isSite()): ?> selected<?php endif ?>><?= $this->translate('panel.pages.newPage.site') ?> (/)</option>
<?php
    foreach ($parents as $parent):
        $scheme = $app->schemes()->get('pages.' . $parent->template()->name());
        if (!$scheme->options()->get('pages', true)) {
            continue;
        }
        if ($parent === $page) {
            continue;
        }
        ?>
    <option value="<?= $parent->route() ?>"<?php if ($page->parent() === $parent): ?> selected<?php endif ?>><?= str_repeat('— ', $parent->level() - 1) . $parent->title() ?></option>
<?php
    endforeach
?>
</select>
