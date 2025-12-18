<?php $this->layout('fields.field') ?>
<div class="flex">
    <div class="form-input-range">
        <input <?= $this->attr([
                    'class'       => 'form-input',
                    'type'       => 'range',
                    'id'         => $field->name(),
                    'name'       => $field->formName(),
                    'min'        => $field->min(),
                    'max'        => $field->max(),
                    'step'       => $field->step(),
                    'value'      => $field->value(),
                    'required'   => $field->isRequired(),
                    'disabled'   => $field->isDisabled(),
                    'hidden'     => $field->isHidden(),
                    'data-ticks' => $field->ticks(),
                ]) ?>>
    </div>
    <output class="form-input-range-value" for="<?= $field->name() ?>"><?= $this->escape($field->value()) ?></output>
</div>