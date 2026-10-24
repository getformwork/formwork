<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon')]) ?>
    <input <?= $this->attr([
                'class'            => $this->classes(['form-input', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'type'             => 'number',
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'min'              => $field->get('min'),
                'max'              => $field->get('max'),
                'step'             => $field->get('step'),
                'value'            => $field->value(),
                'placeholder'      => $field->placeholder(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
</div>
