<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <div class="flex">
        <input <?= $this->attr([
                    'class'            => $this->classes(['form-input-color', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                    'type'             => 'color',
                    'id'               => $field->name(),
                    'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                    'name'             => $field->formName(),
                    'value'            => $field->value(),
                    'placeholder'      => $field->placeholder(),
                    'required'         => $field->isRequired(),
                    'disabled'         => $field->isDisabled(),
                    'hidden'           => $field->isHidden(),
                ]) ?>>
        <output class="form-input-color-value" for="<?= $field->name() ?>"><?= $this->escape((string) $field->value()) ?></output>
    </div>
</div>
