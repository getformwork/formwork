<div>
    <label class="checkbox-label">
        <input <?= $this->attr([
            'type'     => 'checkbox',
            'class'    => 'custom-checkbox',
            'id'       => $field->name(),
            'name'     => $field->formName(),
            'checked'  => $field->value() == true,
            'required' => $field->get('required'),
            'disabled' => $field->get('disabled')
        ]) ?>>
        <span class="custom-checkbox-text"><?= $field->label() ?></span>
    </label>
</div>
