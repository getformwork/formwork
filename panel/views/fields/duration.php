<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'hourglass')]) ?>
    <input <?= $this->attr([
                'class'            => $this->classes(['form-input', 'form-input-duration', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'type'             => 'number',
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'min'              => $field->get('min'),
                'max'              => $field->get('max'),
                'step'             => $field->get('step'),
                'value'            => $field->value(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
                'data-unit'        => $field->get('unit', 'seconds'),
                'data-intervals'   => $field->has('intervals') ? implode(', ', $field->get('intervals')) : null,
            ]) ?>>
</div>
