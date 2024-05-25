<?php $this->layout('fields.field') ?>
<select class="form-select" id="page-template" name="template">
    <?php foreach ($templates as $template) : ?>
        <?php $scheme = $app->schemes()->get('pages.' . $template) ?>
        <option <?= $this->attr([
                    'value'    => $template,
                    'selected' => $page->template()->name() === $template,
                ]) ?>><?= $this->escape($scheme->title()) ?></option>
    <?php endforeach ?>
</select>