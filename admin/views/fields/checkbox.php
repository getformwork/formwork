<div>
    <label class="checkbox-label">
        <input <?= $this->attr([
            'type'     => 'checkbox',
            'class'    => 'custom-checkbox',
            'id'       => $field->name(),
            'name'     => $field->formName(),
            'checked'  => $field->value() == true,
            'required' => $field->isRequired(),
            'disabled' => $field->isDisabled()
        ]) ?>>
        <span class="custom-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
