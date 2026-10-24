<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?php $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'tags')]) ?>
    <input <?= $this->attr([
                'class'            => $this->classes(['form-input', 'form-input-tags', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'type'             => 'text',
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'value'            => implode(', ', (array) $field->value()),
                'placeholder'      => $field->placeholder(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
                'data-limit'       => $field->limit(),
                'data-options'     => $field->options() ? Formwork\Parsers\Json::encode($field->options()) : null,
                'data-accept'      => $field->accept(),
                'data-orderable'   => $field->isOrderable(),
            ]) ?>>
</div>
