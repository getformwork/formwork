<?php $this->layout('@panel.fields.field') ?>
<div class="flex">
    <div class="form-input-range">
        <input <?= $this->attr([
                    'class'            => $this->classes(['form-input', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                    'type'             => 'range',
                    'id'               => $field->name(),
                    'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                    'name'             => $field->formName(),
                    'min'              => $field->min(),
                    'max'              => $field->max(),
                    'step'             => $field->step(),
                    'value'            => $field->value(),
                    'required'         => $field->isRequired(),
                    'disabled'         => $field->isDisabled(),
                    'hidden'           => $field->isHidden(),
                    'data-ticks'       => $field->ticks(),
                ]) ?>>
    </div>
    <output class="form-input-range-value" for="<?= $field->name() ?>"><?= $this->escape($field->value()) ?></output>
</div>
