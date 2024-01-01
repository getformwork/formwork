<?php $this->layout('fields.field') ?>
<textarea <?= $this->attr([
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'placeholder' => $field->placeholder(),
    'rows'        => $field->get('rows', 5),
    'required'    => $field->isRequired(),
    'disabled'    => $field->isDisabled(),
    'hidden'      => $field->isHidden(),
]) ?>><?= $field->value() ?></textarea>
