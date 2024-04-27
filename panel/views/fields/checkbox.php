<div>
    <label class="form-label form-checkbox-label">
        <input <?= $this->attr([
                    'type'     => 'checkbox',
                    'class'    => ['form-input', 'form-checkbox'],
                    'id'       => $field->name(),
                    'name'     => $field->formName(),
                    'checked'  => $field->value() == true,
                    'required' => $field->isRequired(),
                    'disabled' => $field->isDisabled(),
                    'hidden'   => $field->isHidden(),
                ]) ?>>
        <span class="form-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
<?php $this->insert('fields.partials.description') ?>