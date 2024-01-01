<?php $this->layout('fields.field') ?>
<input <?= $this->attr([
    'type'        => 'email',
    'id'          => $field->name(),
    'name'        => $field->formName(),
    'value'       => $field->value(),
    'placeholder' => $field->placeholder(),
    'minlength'   => $field->get('min'),
    'maxlength'   => $field->get('max'),
    'pattern'     => $field->get('pattern'),
    'required'    => $field->isRequired(),
    'disabled'    => $field->isDisabled(),
    'hidden'      => $field->isHidden(),
]) ?>>

