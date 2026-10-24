<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'envelope')]) ?>
    <input <?= $this->attr([
                'class'            => $this->classes(['form-input', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'type'             => 'email',
                'id'               => $field->name(),
                'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                'name'             => $field->formName(),
                'value'            => $field->value(),
                'placeholder'      => $field->placeholder(),
                'minlength'        => $field->minLength(),
                'maxlength'        => $field->maxLength(),
                'pattern'          => $field->pattern(),
                'autocomplete'     => $field->autocomplete(),
                'required'         => $field->isRequired(),
                'disabled'         => $field->isDisabled(),
                'hidden'           => $field->isHidden(),
            ]) ?>>
</div>
