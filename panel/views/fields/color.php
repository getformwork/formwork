<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <div class="flex">
        <input <?= $this->attr([
                    'class'       => ['form-input-color', $field->get('class')],
                    'type'        => 'color',
                    'id'          => $field->name(),
                    'name'        => $field->formName(),
                    'value'       => $field->value(),
                    'placeholder' => $field->placeholder(),
                    'required'    => $field->isRequired(),
                    'disabled'    => $field->isDisabled(),
                    'hidden'      => $field->isHidden(),
                ]) ?>>
        <output class="form-input-color-value" for="<?= $field->name() ?>"><?= $this->escape((string) $field->value()) ?></output>
    </div>
</div>