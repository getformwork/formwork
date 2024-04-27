<?php $this->layout('fields.field') ?>
<input <?= $this->attr([
            'class'        => 'form-input',
            'type'         => 'text',
            'id'           => $field->name(),
            'name'         => $field->formName(),
            'value'        => implode(', ', (array) $field->value()),
            'placeholder'  => $field->placeholder(),
            'required'     => $field->isRequired(),
            'disabled'     => $field->isDisabled(),
            'hidden'       => $field->isHidden(),
            'data-field'   => 'tags',
            'data-options' => $field->has('options') ? Formwork\Parsers\Json::encode((array) $field->get('options')) : null,
        ]) ?>>