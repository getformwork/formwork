<?php foreach ($fields as $field): ?>
    <?php if ($field->isVisible()): ?>
        <?php $this->insert('fields.' . $field->type(), ['field' => $field]) ?>
    <?php endif ?>
<?php endforeach ?>
