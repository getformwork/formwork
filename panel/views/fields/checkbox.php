<div>
    <label class="form-label form-checkbox-label">
        <input <?= $this->attr([
                    'type'             => 'checkbox',
                    'class'            => $this->classes(['form-input', 'form-checkbox', 'is-invalid' => ($field->isValidated() && !$field->isValid()), $field->get('class')]),
                    'id'               => $field->name(),
                    'aria-describedby' => $field->has('description') ? "{$field->name()}-description" : null,
                    'name'             => $field->formName(),
                    'checked'          => $field->value() == true,
                    'required'         => $field->isRequired(),
                    'disabled'         => $field->isDisabled(),
                    'hidden'           => $field->isHidden(),
                ]) ?>>
        <span class="form-checkbox-text"><?= $this->escape($field->label()) ?></span>
    </label>
</div>
<?php $this->insert('@panel.fields.partials.description') ?>
