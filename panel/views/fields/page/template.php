<?php $this->layout('fields.field') ?>
<select class="form-select" id="page-template" name="template">
    <?php foreach ($templates as $template): ?>
        <?php $scheme = $app->schemes()->get('pages.' . $template) ?>
        <option value="<?= $template ?>" <?php if ($page->template()->name() === $template): ?> selected<?php endif ?>><?= $scheme->title() ?></option>
    <?php endforeach ?>
</select>
