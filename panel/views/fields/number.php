<?php $this->layout('fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('fields.partials.icon', ['icon' => $field->get('icon')]) ?>
    <input <?= $this->attr([
                'class'       => 'form-input',
                'type'        => 'number',
                'id'          => $field->name(),
                'name'        => $field->formName(),
                'min'         => $field->get('min'),
                'max'         => $field->get('max'),
                'step'        => $field->get('step'),
                'value'       => $field->value(),
                'placeholder' => $field->placeholder(),
                'required'    => $field->isRequired(),
                'disabled'    => $field->isDisabled(),
                'hidden'      => $field->isHidden(),
            ]) ?>>
</div>