<?php if ($field->has('description')) : ?>
    <div class="form-input-description" id="<?= $this->escapeAttr($field->name()) ?>-description"><?= $this->markdown($field->get('description')) ?></div>
<?php endif ?>