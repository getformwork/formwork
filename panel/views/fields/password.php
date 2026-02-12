<?php $this->layout('@panel.fields.field') ?>
<div class="form-input-wrap">
    <?= $this->insert('@panel.fields.partials.icon', ['icon' => $field->get('icon', 'key')]) ?>
    <input <?= $this->attr([
                'class'        => $this->classes(['form-input', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                'type'         => 'password',
                'id'           => $field->name(),
                'name'         => $field->formName(),
                'value'        => null, // Never expose the value of a password field
                'placeholder'  => $field->placeholder(),
                'minlength'    => $field->minLength(),
                'maxlength'    => $field->maxLength(),
                'pattern'      => $field->pattern(),
                'autocomplete' => $field->autocomplete(),
                'required'     => $field->isRequired(),
                'disabled'     => $field->isDisabled(),
                'hidden'       => $field->isHidden(),
            ]) ?>>
</div>